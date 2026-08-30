<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$passed = 0;
$failed = 0;
$ok = function (bool $condition, string $label) use (&$passed, &$failed): void {
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $label . PHP_EOL;
    $condition ? $passed++ : $failed++;
};

$pdo = Database::getInstance();
$pdo->exec(file_get_contents(DATABASE_PATH . '/schema.sql'));
$pdo->exec('DELETE FROM quiz_answers; DELETE FROM quiz_questions; DELETE FROM quizzes; DELETE FROM question_conflicts; DELETE FROM question_sources; DELETE FROM questions; DELETE FROM subjects; DELETE FROM modules;');
$pdo->exec("INSERT INTO modules(name) VALUES ('Appearance Test Module')");
$moduleId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO subjects(module_id,name) VALUES(?,?)')->execute([$moduleId, 'Appearance Test Subject']);
$subjectId = (int)$pdo->lastInsertId();

$base = ['subject_id'=>$subjectId,'type'=>'essay','question_text'=>'Appearance regression question','answer_status'=>'available','frequency'=>3,'answer'=>'Reference answer'];
$created = Question::createQuestion($base + ['appearances'=>[]]);
$questionId = (int)($created['id'] ?? 0);
$ok($created['success'] && count(Question::getSources($questionId)) === 0, 'Create supports zero appearances');

$one = $base; $one['frequency']=1; $one['appearances']=[['source_name'=>'final','exam_year'=>'2025','exam_term'=>'first']];
$updated = Question::updateQuestion($questionId, $one);
$ok($updated['success'] && count(Question::getSources($questionId)) === 1, 'Edit adds one appearance');

$many = $base + ['frequency'=>3,'appearances'=>[['source_name'=>'final','exam_year'=>2025,'exam_term'=>'first'],['source_name'=>'end_module','exam_year'=>2026,'exam_term'=>'second'],['source_name'=>'final','exam_year'=>2027,'exam_term'=>'second']]];
$updated = Question::updateQuestion($questionId, $many);
$ok($updated['success'] && count(Question::getSources($questionId)) === 3, 'Edit stores multiple appearances');
$preserve = $many; unset($preserve['appearances']);
$updated = Question::updateQuestion($questionId, $preserve);
$ok($updated['success'] && count(Question::getSources($questionId)) === 3, 'Legacy callers preserve appearances when field is omitted');
$reduced = $base; $reduced['frequency']=2; $reduced['appearances']=[$many['appearances'][0], $many['appearances'][2]];
$updated = Question::updateQuestion($questionId, $reduced);
$ok($updated['success'] && count(Question::getSources($questionId)) === 2, 'Edit removes selected appearances');
$updated = Question::updateQuestion($questionId, $base + ['appearances'=>[]]);
$ok($updated['success'] && count(Question::getSources($questionId)) === 0, 'Edit removes all appearances');

$invalidCases = [
    [['source_name'=>'final','exam_year'=>2025,'exam_term'=>'first'], ['source_name'=>'final','exam_year'=>2025,'exam_term'=>'first']],
    [['source_name'=>'midterm','exam_year'=>2025,'exam_term'=>'first']],
    [['source_name'=>'final','exam_year'=>'20x5','exam_term'=>'first']],
    [['source_name'=>'final','exam_year'=>2025,'exam_term'=>'third']],
    [['source_name'=>'final','exam_year'=>'','exam_term'=>'first']],
];
foreach ($invalidCases as $index => $appearances) {
    $result = Question::createQuestion($base + ['question_text'=>'Invalid appearance '.$index,'appearances'=>$appearances]);
    $ok(!$result['success'], 'Invalid appearance case '.($index + 1).' is rejected');
}
$before = count(Question::getSources($questionId));
$result = Question::updateQuestion($questionId, $base + ['appearances'=>$invalidCases[0]]);
$ok(!$result['success'] && count(Question::getSources($questionId)) === $before, 'Rejected duplicate edit leaves existing rows unchanged');

$rollbackBefore = (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn();
try {
    Database::transaction(function (PDO $transactionPdo) use ($questionId): void {
        $transactionPdo->prepare('INSERT INTO question_sources(question_id,source_name,exam_year,exam_term) VALUES(?,?,?,?)')->execute([$questionId,'final',2030,'first']);
        throw new RuntimeException('forced rollback for regression test');
    });
} catch (RuntimeException $ignored) {
}
$ok((int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn() === $rollbackBefore && count(Question::getSources($questionId)) === $before, 'Failed transaction rolls back all writes');

$json = ['questions'=>[['type'=>'essay','question'=>'JSON appearance question','answer'=>'Answer','frequency'=>2,'appearances'=>[['source'=>'final','year'=>2028,'term'=>'first'],['source'=>'end_module','year'=>2029,'term'=>'second']]]]];
$parsed = JsonImporter::parse(json_encode($json), $moduleId, $subjectId);
$imported = JsonImporter::import($parsed['valid']);
$importedRow = Database::fetchOne('SELECT id,frequency FROM questions WHERE question_text=?', ['JSON appearance question']);
$ok($parsed['summary']['valid'] === 1 && $imported['new_questions'] === 1, 'JSON import still reaches the write stage');
$ok($importedRow !== null && (int)$importedRow['frequency'] === 2 && count(Question::getSources((int)$importedRow['id'])) === 2, 'JSON appearance rows persist with frequency');
$detail = Question::getQuestionById($questionId);
$ok($detail !== null && array_key_exists('appearances', $detail) && count($detail['appearances']) === $before, 'Question detail returns all appearances');
$ok(str_contains(file_get_contents(ROOT_PATH.'/views/admin/questions/form.php'), 'appearances[') && str_contains(file_get_contents(ROOT_PATH.'/views/admin/questions/form.php'), 'add-exam-appearance'), 'Admin form exposes repeatable appearance controls');
$studentView = file_get_contents(ROOT_PATH.'/views/student/questions/index.php');
$ok(str_contains($studentView, 'No exam appearances recorded.') && !str_contains($studentView, 'data-remove-appearance'), 'Student display is read-only and handles zero appearances');
$ok(count($pdo->query('PRAGMA foreign_key_check')->fetchAll()) === 0, 'Foreign-key integrity passes');

echo "Exam Appearances Test Results: {$passed} Passed, {$failed} Failed" . PHP_EOL;
exit($failed ? 1 : 0);
