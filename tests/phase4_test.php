<?php
declare(strict_types=1);
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

$passed = 0; $failed = 0;
$assert = function (bool $ok, string $name) use (&$passed, &$failed): void {
    echo ($ok ? "[PASS] " : "[FAIL] ") . $name . PHP_EOL;
    $ok ? $passed++ : $failed++;
};

$suffix = (string)random_int(100000, 999999);
$module = Academic::createModule("Phase 4 Test Module {$suffix}", null);
$moduleId = (int)($module['id'] ?? 0);
$subject = Academic::createSubject($moduleId, "Phase 4 Test Subject {$suffix}", null);
$subjectId = (int)($subject['id'] ?? 0);
$json = json_encode(['questions' => [
    ['type' => 'mcq', 'question' => 'Phase 4 imported enzyme question.', 'choices' => ['A' => 'Choice A', 'B' => 'Choice B'], 'answer' => 'B', 'frequency' => 1, 'source' => 'Final Exam 2025'],
    ['type' => 'essay', 'question' => 'Phase 4 unanswered essay.', 'frequency' => 2],
]], JSON_THROW_ON_ERROR);

$before = (int)Database::fetchOne('SELECT COUNT(*) c FROM questions WHERE subject_id = ?', [$subjectId])['c'];
$parsed = JsonImporter::parse($json, $moduleId, $subjectId);
$assert($parsed['success'] === true && $parsed['summary']['valid'] === 2, 'Valid JSON parses and validates');
$confirmationView = file_get_contents(ROOT_PATH . '/views/admin/import.php');
$assert(str_contains($confirmationView, 'CSRF::getToken()') && !str_contains($confirmationView, 'CSRF::token()'), 'Final confirmation form uses the existing CSRF token API');
$importController = file_get_contents(ROOT_PATH . '/public/admin/import.php');
$assert(str_contains($importController, "\$_GET['module_id']") && str_contains($importController, 'Academic::getAllSubjects($selectedModuleId)') && str_contains($confirmationView, 'window.location.href'), 'Import module selection reloads module-scoped subjects');
$assert((int)Database::fetchOne('SELECT COUNT(*) c FROM questions WHERE subject_id = ?', [$subjectId])['c'] === $before, 'Validation performs no database writes');
$assert($parsed['summary']['with_answers'] === 1 && $parsed['summary']['without_answers'] === 1, 'Answer availability is summarized');
$assert($parsed['summary']['new_questions'] === 2 && $parsed['summary']['merge_records'] === 0 && $parsed['summary']['duplicate_appearances'] === 0, 'Import preview reports new rows, merges, and duplicate appearances');
$assert(str_contains($confirmationView, 'What will happen') && str_contains($confirmationView, 'Valid Records'), 'Import preview clearly explains what will be imported');
$assert($parsed['valid'][0]['answer_data']['correct_answer'] === 'Choice B', 'MCQ answer label normalizes to choice text');

$invalid = JsonImporter::parse('{"questions":[{"type":"mcq","question":"Bad","frequency":0}]}', $moduleId, $subjectId);
$assert($invalid['success'] === true && $invalid['summary']['invalid'] === 1, 'Invalid question is reported before import');
$wrongTarget = JsonImporter::parse($json, $moduleId + 999999, $subjectId);
$assert($wrongTarget['success'] === false, 'Mismatched module and subject are rejected');

$imported = JsonImporter::import($parsed['valid']);
$assert($imported['success'] === true && $imported['imported'] === 2, 'Validated records import successfully');
$rows = Question::getQuestions(['module_id' => $moduleId, 'subject_id' => $subjectId], 10, 0);
$assert(count($rows) === 2, 'Imported rows are retrieved by Question Bank query');
$mcq = Question::getQuestionById((int)$rows[1]['id']);
$essay = Question::getQuestionById((int)$rows[0]['id']);
$all = array_merge([$mcq], [$essay]);
$assert(count(array_filter($all, fn($q) => $q['answer_origin'] === 'json_import')) === 2, 'Imported rows carry json_import origin');
$assert(count(array_filter($all, fn($q) => $q['answer_status'] === 'unavailable')) === 1, 'Missing answer remains unavailable');
$assert((int)Database::fetchOne('SELECT COUNT(*) c FROM question_sources WHERE question_id IN (SELECT id FROM questions WHERE subject_id = ?)', [$subjectId])['c'] === 1, 'Source maps to question_sources');

Database::execute('DELETE FROM modules WHERE id = ?', [$moduleId]);
echo "Phase 4 Test Results: {$passed} Passed, {$failed} Failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
