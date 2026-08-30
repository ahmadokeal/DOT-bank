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
    ->execute(['deletion_test_student', password_hash('DeletionPass123', PASSWORD_DEFAULT), 'student', 'active', $now]);
$studentId = (int)$pdo->lastInsertId();

$pdo->prepare('INSERT INTO modules (name, created_at) VALUES (?, ?)')->execute(['Deletion Test Module', $now]);
$moduleId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO subjects (module_id, name, created_at) VALUES (?, ?, ?)')->execute([$moduleId, 'Deletion Test Subject', $now]);
$subjectId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$subjectId, 'mcq', 'Question referenced by a quiz', '{"options":["A","B"],"correct_answer":"A"}', 'available', 'manual', 1, $now, $now]);
$questionId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quizzes (user_id, module_id, total_questions, created_at) VALUES (?, ?, ?, ?)')->execute([$studentId, $moduleId, 1, $now]);
$quizId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_id, question_order) VALUES (?, ?, 1)')->execute([$quizId, $questionId]);
$quizQuestionId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quiz_answers (quiz_question_id, student_answer, is_correct) VALUES (?, ?, 1)')->execute([$quizQuestionId, 'A']);

$questionDelete = Question::deleteQuestion($questionId);
$check($questionDelete['success'] === false && str_contains($questionDelete['message'], 'active quiz'), 'Question deletion is rejected safely while an active quiz references it');
$check((int)Database::fetchOne('SELECT COUNT(*) AS c FROM questions WHERE id = ?', [$questionId])['c'] === 1, 'Rejected question deletion preserves the question');
$subjectDelete = Academic::deleteSubject($subjectId);
$check($subjectDelete['success'] === false && str_contains($subjectDelete['message'], 'active quiz'), 'Subject deletion is rejected safely while an active quiz references it');
$check((int)Database::fetchOne('SELECT COUNT(*) AS c FROM subjects WHERE id = ?', [$subjectId])['c'] === 1 && (int)Database::fetchOne('SELECT COUNT(*) AS c FROM quizzes WHERE id = ?', [$quizId])['c'] === 1, 'Rejected subject deletion preserves the subject and quiz');
$pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$quizId]);
$subjectDelete = Academic::deleteSubject($subjectId);
$check($subjectDelete['success'] === true, 'Subject deletion succeeds after the dependent quiz is removed');
$check((int)Database::fetchOne('SELECT COUNT(*) AS c FROM questions WHERE id = ?', [$questionId])['c'] === 0, 'Subject questions are removed by the existing cascade');
$check((int)$pdo->query('PRAGMA foreign_key_check')->fetchColumn() === 0, 'Subject deletion leaves no foreign-key violations');

$pdo->prepare('INSERT INTO subjects (module_id, name, created_at) VALUES (?, ?, ?)')->execute([$moduleId, 'Second Deletion Subject', $now]);
$secondSubjectId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
    ->execute([$secondSubjectId, 'essay', 'Second question referenced by a quiz', '{"answer":"Model"}', 'available', 'manual', 1, $now, $now]);
$secondQuestionId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quizzes (user_id, module_id, total_questions, created_at) VALUES (?, ?, ?, ?)')->execute([$studentId, $moduleId, 1, $now]);
$secondQuizId = (int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_id, question_order) VALUES (?, ?, 1)')->execute([$secondQuizId, $secondQuestionId]);

$moduleDelete = Academic::deleteModule($moduleId);
$check($moduleDelete['success'] === false && str_contains($moduleDelete['message'], 'active quiz'), 'Module deletion is rejected safely while an active quiz references its questions');
$check((int)Database::fetchOne('SELECT COUNT(*) AS c FROM modules WHERE id = ?', [$moduleId])['c'] === 1 && (int)Database::fetchOne('SELECT COUNT(*) AS c FROM quizzes WHERE id = ?', [$secondQuizId])['c'] === 1, 'Rejected module deletion preserves the module and quiz');
$pdo->prepare('DELETE FROM quizzes WHERE id = ?')->execute([$secondQuizId]);
$moduleDelete = Academic::deleteModule($moduleId);
$check($moduleDelete['success'] === true && (int)Database::fetchOne('SELECT COUNT(*) AS c FROM modules WHERE id = ?', [$moduleId])['c'] === 0, 'Module deletion succeeds after the dependent quiz is removed');
$check((int)$pdo->query('PRAGMA foreign_key_check')->fetchColumn() === 0, 'Module deletion leaves no foreign-key violations');

echo "Deletion Integrity Test Results: {$passed} Passed, {$failed} Failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
