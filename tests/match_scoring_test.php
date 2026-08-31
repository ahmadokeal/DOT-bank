<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$passed = 0;
$failed = 0;
$check = static function (bool $condition, string $name) use (&$passed, &$failed): void {
    echo ($condition ? '[PASS] ' : '[FAIL] ') . $name . PHP_EOL;
    $condition ? $passed++ : $failed++;
};

Database::reset();
$pdo = Database::getInstance();
$pdo->exec(file_get_contents(DATABASE_PATH . '/schema.sql'));
$pdo->exec('DELETE FROM quiz_answers; DELETE FROM quiz_questions; DELETE FROM quizzes; DELETE FROM question_sources; DELETE FROM questions; DELETE FROM subjects; DELETE FROM modules; DELETE FROM users;');

$now = date('Y-m-d H:i:s');
$pdo->prepare('INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?)')
    ->execute(['match_scoring_student', password_hash('MatchPass123', PASSWORD_DEFAULT), 'student', 'active', $now]);
$studentId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO modules (name, created_at) VALUES (?, ?)')->execute(['Match Module', $now]);
$moduleId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO subjects (module_id, name, created_at) VALUES (?, ?, ?)')->execute([$moduleId, 'Match Subject', $now]);
$subjectId = (int)$pdo->lastInsertId();

$matchData = [
    'left_items' => ['A1', 'A2', 'A3', 'A4'],
    'right_items' => ['B1', 'B2', 'B3', 'B4'],
    'matches' => ['A1' => 'B1', 'A2' => 'B2', 'A3' => 'B3', 'A4' => 'B4'],
];
$pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$subjectId, 'match', 'Match four items', json_encode($matchData), 'available', 'manual', 1, $now, $now]);
$matchQuestionId = (int)$pdo->lastInsertId();

$submitMatch = static function (array $answer) use ($pdo, $studentId, $moduleId, $subjectId, $matchQuestionId): array {
    $plan = ['module_id' => $moduleId, 'subject_ids' => [$subjectId], 'question_ids' => [$matchQuestionId]];
    $created = Quiz::create($studentId, $plan);
    $quiz = Quiz::getForStudent($created['id'], $studentId);
    $quizQuestionId = (int)$quiz['questions'][0]['quiz_question_id'];
    $result = Quiz::submit($created['id'], $studentId, [$quizQuestionId => $answer]);
    return [$result, $quizQuestionId];
};
$expectedScores = [
    ['A1' => 'B1', 'A2' => 'B2', 'A3' => 'B3', 'A4' => 'B4'],
    ['A1' => 'B1', 'A2' => 'B2', 'A3' => 'B4', 'A4' => 'B4'],
    ['A1' => 'B1', 'A2' => 'B3', 'A3' => 'B4', 'A4' => 'B4'],
    ['A1' => 'B1', 'A2' => 'B3', 'A3' => 'B4', 'A4' => 'B2'],
    ['A1' => 'B2', 'A2' => 'B3', 'A3' => 'B4', 'A4' => 'B1'],
];
foreach ($expectedScores as $index => $answer) {
    [$result] = $submitMatch($answer);
    $expectedCorrect = 4 - $index;
    $check($result['success'] === true && $result['correct'] === $expectedCorrect && $result['auto_graded'] === 4, "Match {$expectedCorrect}/4 awards pair-level scoring units");
    $check(($result['questions'][0]['match_correct_pairs'] ?? null) === $expectedCorrect && ($result['questions'][0]['match_total_pairs'] ?? null) === 4, "Match {$expectedCorrect}/4 exposes pair score in result payload");
}

[$partial] = $submitMatch(['A1' => 'B1', 'A2' => 'B3']);
$check($partial['correct'] === 1 && $partial['incorrect'] === 1 && $partial['unanswered'] === 2, 'Partially unanswered Match credits correct pairs and counts missing pairs as zero');
[$empty] = $submitMatch([]);
$check($empty['correct'] === 0 && $empty['incorrect'] === 0 && $empty['unanswered'] === 4 && $empty['score'] === 0.0, 'Completely unanswered Match receives zero score across four pairs');

$invalid = Question::validate([
    'subject_id' => $subjectId, 'type' => 'match', 'question_text' => 'Invalid partial mapping',
    'answer_status' => 'available', 'frequency' => 1,
    'left_items' => ['A1', 'A2'], 'right_items' => ['B1', 'B2'], 'matches' => ['A1' => 'B1'],
]);
$check(in_array('Every left item must have exactly one correct match.', $invalid, true), 'Manual Match validation rejects an incomplete correct mapping');

$quizPlan = Quiz::plan(['module_id' => $moduleId, 'subject_ids' => [$subjectId], 'total_questions' => 1, 'type_percentages' => ['match' => 100, 'mcq' => 0, 'complete' => 0, 'compare' => 0, 'essay' => 0]]);
$created = Quiz::create($studentId, $quizPlan);
$taken = Quiz::getForStudent($created['id'], $studentId);
$check(count($taken['questions']) === 1 && $taken['questions'][0]['type'] === 'match', 'Match remains one question in quiz question count');

$pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([$subjectId, 'mcq', 'MCQ scoring control', '{"options":["Yes","No"],"correct_answer":"Yes"}', 'available', 'manual', 1]);
$mcqId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency) VALUES (?, ?, ?, ?, ?, ?, ?)')
    ->execute([$subjectId, 'true_false', 'True false scoring control', '{"answer":"true"}', 'available', 'manual', 1]);
$trueFalseId = (int)$pdo->lastInsertId();
$mixedPlan = Quiz::plan(['module_id' => $moduleId, 'subject_ids' => [$subjectId], 'total_questions' => 3, 'type_percentages' => ['match' => 33.333333, 'mcq' => 33.333334, 'true_false' => 33.333333, 'complete' => 0, 'compare' => 0, 'essay' => 0]]);
$mixedPlan['question_ids'] = [$matchQuestionId, $mcqId, $trueFalseId];
$mixed = Quiz::create($studentId, $mixedPlan);
$mixedQuiz = Quiz::getForStudent($mixed['id'], $studentId);
$mixedAnswers = [];
foreach ($mixedQuiz['questions'] as $question) {
    $mixedAnswers[(int)$question['quiz_question_id']] = $question['type'] === 'match' ? ['A1' => 'B1', 'A2' => 'B2', 'A3' => 'B4', 'A4' => 'B4'] : ($question['type'] === 'mcq' ? 'Yes' : 'false');
}
$mixedResult = Quiz::submit($mixed['id'], $studentId, $mixedAnswers);
$check($mixedResult['total_questions'] === 3 && $mixedResult['auto_graded'] === 6 && $mixedResult['correct'] === 4 && $mixedResult['score'] === 66.67, 'Match contributes proportionally alongside MCQ and True/False scoring');
$mixedMatch = array_values(array_filter($mixedResult['questions'], static fn(array $q): bool => $q['type'] === 'match'))[0];
ob_start();
$result = $mixedResult;
include dirname(__DIR__) . '/views/student/quizzes/result.php';
$resultHtml = ob_get_clean();
$check(str_contains($resultHtml, '3 / 4 correct') && str_contains($resultHtml, 'A1') && str_contains($resultHtml, 'Correct: B3'), 'Match result review exposes one question with pair-level correctness');

$validJson = json_encode(['questions' => [[
    'type' => 'match', 'question' => 'Imported match',
    'pairs' => [['left' => 'A1', 'right' => 'B1'], ['left' => 'A2', 'right' => 'B2']],
    'answer' => 'provided', 'frequency' => 1,
]]]);
$parsed = JsonImporter::parse($validJson, $moduleId, $subjectId);
$check($parsed['summary']['valid'] === 1 && $parsed['valid'][0]['answer_data']['matches'] === ['A1' => 'B1', 'A2' => 'B2'], 'JSON importer accepts canonical Match pairs and complete mapping');
$invalidJson = json_encode(['questions' => [[
    'type' => 'match', 'question' => 'Malformed match',
    'pairs' => [['left' => 'A1', 'right' => 'B1'], ['left' => 'A1', 'right' => 'B2']],
    'answer' => 'provided', 'frequency' => 1,
]]]);
$invalidParsed = JsonImporter::parse($invalidJson, $moduleId, $subjectId);
$check($invalidParsed['summary']['valid'] === 0 && $invalidParsed['summary']['invalid'] === 1, 'JSON importer rejects malformed Match structures');

$check((int)$pdo->query('SELECT COUNT(*) FROM quizzes')->fetchColumn() === 1, 'Submitted Match quizzes are cleaned up while the intentionally active quiz remains');
$pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$created['id']]);
$check((int)$pdo->query('SELECT COUNT(*) FROM pragma_foreign_key_check')->fetchColumn() === 0, 'Match scoring tests leave no foreign-key violations');

echo "Match Scoring Test Results: {$passed} Passed, {$failed} Failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
