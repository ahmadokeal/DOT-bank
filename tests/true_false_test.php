<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$p = 0; $f = 0;
$ok = function (bool $value, string $name) use (&$p, &$f): void {
    echo ($value ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $value ? $p++ : $f++;
};

$pdo = Database::getInstance();
$pdo->exec(file_get_contents(DATABASE_PATH . '/schema.sql'));
$pdo->exec('DELETE FROM quiz_answers; DELETE FROM quiz_questions; DELETE FROM quizzes; DELETE FROM question_sources; DELETE FROM questions; DELETE FROM subjects; DELETE FROM modules; DELETE FROM users;');
$pdo->exec("INSERT INTO users(username,password_hash,role,status) VALUES('tf_student','x','student','active')");
$student = (int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO modules(name) VALUES('True False Module')");
$module = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO subjects(module_id,name) VALUES(?,?)')->execute([$module, 'True False Subject']);
$subject = (int)$pdo->lastInsertId();

$base = ['subject_id'=>$subject,'type'=>'true_false','question_text'=>'The heart has four chambers.','answer_status'=>'available','frequency'=>1,'answer'=>'true','appearances'=>[]];
$created = Question::createQuestion($base);
$qid = (int)($created['id'] ?? 0);
$row = Question::getQuestionById($qid);
$ok($created['success'] && $row['type'] === 'true_false' && $row['answer_data_decoded'] === ['answer'=>'true'], 'True/False CRUD stores the required internal answer payload');
$invalid = Question::validate(array_replace($base, ['answer'=>'yes']));
$ok(in_array('True/False answer must be true or false.', $invalid, true), 'Manual validation rejects non-boolean answer strings');
$updated = Question::updateQuestion($qid, array_replace($base, ['question_text'=>'The heart is a four-chambered organ.','answer'=>'false']));
$ok($updated['success'] && Question::getQuestionById($qid)['answer_data_decoded']['answer'] === 'false', 'True/False answer can be updated');
$filtered = Question::getQuestions(['module_id'=>$module,'type'=>'true_false'], 50, 0);
$ok(count($filtered) === 1 && $filtered[0]['type'] === 'true_false', 'Question listing filters True/False questions');

$validJson = json_encode(['questions'=>[
    ['type'=>'true_false','question'=>'Imported true statement.','answer'=>true,'frequency'=>1,'appearances'=>[]],
    ['type'=>'true_false','question'=>'Imported false statement.','answer'=>false,'frequency'=>1,'appearances'=>[]],
]], JSON_THROW_ON_ERROR);
$parsed = JsonImporter::parse($validJson, $module, $subject);
$ok($parsed['summary']['valid'] === 2 && $parsed['valid'][0]['answer_data'] === ['answer'=>'true'] && $parsed['valid'][1]['answer_data'] === ['answer'=>'false'], 'JSON import accepts real booleans and normalizes them to strings');
$badJson = JsonImporter::parse(json_encode(['questions'=>[['type'=>'true_false','question'=>'Bad answer.','answer'=>'true','frequency'=>1]]], JSON_THROW_ON_ERROR), $module, $subject);
$ok($badJson['summary']['invalid'] === 1, 'JSON import rejects string True/False answers');
$imported = JsonImporter::import($parsed['valid']);
$ok($imported['new_questions'] === 2, 'True/False JSON records import successfully');

$plan = Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subject],'total_questions'=>3,'type_percentages'=>['true_false'=>100]]);
$ok($plan['success'] && $plan['exact'] && $plan['actual_types']['true_false'] === 3, 'Quiz builder plans True/False questions');
$quizCreated = Quiz::create($student, $plan);
$quiz = Quiz::getForStudent($quizCreated['id'], $student);
$ok($quizCreated['success'] && count($quiz['questions']) === 3, 'Quiz taking includes True/False questions');
$answers = [];
foreach ($quiz['questions'] as $q) $answers[(int)$q['quiz_question_id']] = json_decode($q['answer_data'], true)['answer'];
$submitted = Quiz::submit($quizCreated['id'], $student, $answers);
$ok($submitted['success'] && $submitted['auto_graded'] === 3 && $submitted['correct'] === 3 && $submitted['score'] === 100.0, 'True/False answers are auto-graded as objective questions');
$statuses = array_column($submitted['questions'], 'is_correct');
$ok(count($statuses) === 3 && count(array_filter($statuses, fn($value) => $value === 1)) === 3, 'True/False results expose correct status');

$mixedQuizCreated = Quiz::create($student, $plan);
$mixedQuiz = Quiz::getForStudent($mixedQuizCreated['id'], $student);
$mixedAnswers = [];
foreach ($mixedQuiz['questions'] as $index => $q) {
    $expected = json_decode($q['answer_data'], true)['answer'];
    $mixedAnswers[(int)$q['quiz_question_id']] = $index === 0 ? ($expected === 'true' ? 'false' : 'true') : ($index === 1 ? '' : $expected);
}
$mixedSubmitted = Quiz::submit($mixedQuizCreated['id'], $student, $mixedAnswers);
$mixedStatuses = array_column($mixedSubmitted['questions'], 'is_correct');
$ok($mixedSubmitted['success'] && $mixedSubmitted['auto_graded'] === 3 && $mixedSubmitted['correct'] === 1 && $mixedSubmitted['incorrect'] === 1 && $mixedSubmitted['unanswered'] === 1 && $mixedSubmitted['score'] === 33.33, 'True/False wrong and unanswered responses affect objective grading correctly');
$ok(count(array_filter($mixedStatuses, fn($value) => $value === 0)) === 2 && count(array_filter($mixedSubmitted['questions'], fn($q) => ($q['student_answer'] ?? '') === '')) === 1, 'True/False results distinguish incorrect from unanswered responses');

$unavailable = Question::createQuestion(['subject_id'=>$subject,'type'=>'true_false','question_text'=>'Unknown true or false statement.','answer_status'=>'unavailable','frequency'=>1,'appearances'=>[]]);
$unavailableId = (int)($unavailable['id'] ?? 0);
$unavailableRow = Question::getQuestionById($unavailableId);
$ok($unavailable['success'] && $unavailableRow['answer_status'] === 'unavailable' && $unavailableRow['answer_data_decoded'] === ['answer'=>null], 'Unavailable True/False CRUD stores a null answer payload');
$unavailableUpdated = Question::updateQuestion($unavailableId, ['subject_id'=>$subject,'type'=>'true_false','question_text'=>'Unknown true or false statement.','answer_status'=>'available','frequency'=>1,'answer'=>'false','appearances'=>[]]);
$ok($unavailableUpdated['success'] && Question::getQuestionById($unavailableId)['answer_data_decoded'] === ['answer'=>'false'], 'Unavailable True/False question can later receive an answer');

$take = file_get_contents(ROOT_PATH . '/views/student/quizzes/take.php');
$result = file_get_contents(ROOT_PATH . '/views/student/quizzes/result.php');
$ok(strpos($take, "type']==='true_false'") !== false && strpos($result, "type']==='true_false'") !== false, 'Quiz taking and result views render True/False branches');

$pdo->prepare('INSERT INTO question_sources (question_id, source_name, exam_year, exam_term) VALUES (?, ?, ?, ?)')->execute([$unavailableId, 'final', 2025, 'first']);
$pdo->prepare('INSERT INTO quizzes (user_id, module_id, total_questions) VALUES (?, ?, ?)')->execute([$student, $module, 1]);
$migrationQuizId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_id, question_order) VALUES (?, ?, ?)')->execute([$migrationQuizId, $unavailableId, 1]);
$migrationQuizQuestionId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quiz_answers (quiz_question_id, student_answer, is_correct) VALUES (?, ?, ?)')->execute([$migrationQuizQuestionId, 'false', 1]);

ob_start();
require ROOT_PATH . '/tools/migrate_true_false.php';
$migrationOutput = ob_get_clean();
$ok(strpos($migrationOutput, 'migration completed') !== false && (int)$pdo->query('SELECT COUNT(*) FROM questions')->fetchColumn() === 4, 'SQLite migration preserves the question row count');
$migratedRow = Question::getQuestionById($unavailableId);
$ok($migratedRow['id'] === $unavailableId && $migratedRow['type'] === 'true_false' && $migratedRow['question_text'] === 'Unknown true or false statement.' && $migratedRow['answer_data_decoded'] === ['answer'=>'false'] && $migratedRow['answer_status'] === 'available', 'SQLite migration preserves the True/False ID, payload, and metadata');
$migratedSource = $pdo->query('SELECT source_name, exam_year, exam_term FROM question_sources WHERE question_id=' . $unavailableId)->fetch(PDO::FETCH_ASSOC);
$migratedQuizLink = $pdo->query('SELECT quiz_id, question_id, question_order FROM quiz_questions WHERE id=' . $migrationQuizQuestionId)->fetch(PDO::FETCH_ASSOC);
$migratedQuizAnswer = $pdo->query('SELECT student_answer, is_correct FROM quiz_answers WHERE quiz_question_id=' . $migrationQuizQuestionId)->fetch(PDO::FETCH_ASSOC);
$ok($migratedSource === ['source_name'=>'final','exam_year'=>2025,'exam_term'=>'first'] && $migratedQuizLink === ['quiz_id'=>$migrationQuizId,'question_id'=>$unavailableId,'question_order'=>1] && $migratedQuizAnswer === ['student_answer'=>'false','is_correct'=>1], 'SQLite migration preserves related sources, quiz links, and answers');
$ok(count($pdo->query('PRAGMA foreign_key_check')->fetchAll()) === 0, 'SQLite migration leaves no foreign-key violations');

echo "True/False Test Results: {$p} Passed, {$f} Failed" . PHP_EOL;
exit($f ? 1 : 0);
