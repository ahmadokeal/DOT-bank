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

$insertModule = static function (string $name) use ($pdo, $now): int {
    $pdo->prepare('INSERT INTO modules (name, created_at) VALUES (?, ?)')->execute([$name, $now]);
    return (int)$pdo->lastInsertId();
};
$insertSubject = static function (int $moduleId, string $name) use ($pdo, $now): int {
    $pdo->prepare('INSERT INTO subjects (module_id, name, created_at) VALUES (?, ?, ?)')->execute([$moduleId, $name, $now]);
    return (int)$pdo->lastInsertId();
};
$insertQuestion = static function (int $subjectId, string $type, string $text) use ($pdo, $now): int {
    $data = $type === 'mcq' ? '{"options":["A","B"],"correct_answer":"A"}' : '{"answer":"Model"}';
    $pdo->prepare('INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
        ->execute([$subjectId, $type, $text, $data, 'available', 'manual', 1, $now, $now]);
    return (int)$pdo->lastInsertId();
};
$insertQuiz = static function (int $moduleId, int $questionId, bool $withAnswer = true) use ($pdo, $studentId, $now): array {
    $pdo->prepare('INSERT INTO quizzes (user_id, module_id, total_questions, created_at) VALUES (?, ?, ?, ?)')->execute([$studentId, $moduleId, 1, $now]);
    $quizId = (int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO quiz_questions (quiz_id, question_id, question_order) VALUES (?, ?, 1)')->execute([$quizId, $questionId]);
    $quizQuestionId = (int)$pdo->lastInsertId();
    if ($withAnswer) {
        $pdo->prepare('INSERT INTO quiz_answers (quiz_question_id, student_answer, is_correct) VALUES (?, ?, 1)')->execute([$quizQuestionId, 'A']);
    }
    return [$quizId, $quizQuestionId];
};

// Module deletion implicitly discards its dependent quiz and preserves unrelated content.
$moduleId = $insertModule('Module With Quiz');
$subjectId = $insertSubject($moduleId, 'Module Subject');
$questionId = $insertQuestion($subjectId, 'mcq', 'Module deletion question');
[$moduleQuizId, $moduleQuizQuestionId] = $insertQuiz($moduleId, $questionId);
$unrelatedModuleId = $insertModule('Unrelated Module');
$unrelatedSubjectId = $insertSubject($unrelatedModuleId, 'Unrelated Subject');
$unrelatedQuestionId = $insertQuestion($unrelatedSubjectId, 'essay', 'Unrelated question');
[$unrelatedQuizId] = $insertQuiz($unrelatedModuleId, $unrelatedQuestionId);

$moduleDelete = Academic::deleteModule($moduleId);
$check($moduleDelete['success'] === true, 'Module deletion succeeds with a dependent in-progress quiz');
$check((int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $moduleQuizId)->fetchColumn() === 0, 'Module deletion removes the dependent quiz');
$check((int)$pdo->query('SELECT COUNT(*) FROM quiz_questions WHERE id = ' . $moduleQuizQuestionId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quiz_answers WHERE quiz_question_id = ' . $moduleQuizQuestionId)->fetchColumn() === 0, 'Module deletion removes dependent quiz links and answers');
$check((int)$pdo->query('SELECT COUNT(*) FROM modules WHERE id = ' . $unrelatedModuleId)->fetchColumn() === 1 && (int)$pdo->query('SELECT COUNT(*) FROM questions WHERE id = ' . $unrelatedQuestionId)->fetchColumn() === 1 && (int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $unrelatedQuizId)->fetchColumn() === 1, 'Module deletion preserves unrelated content and quiz');

// Subject deletion implicitly discards only quizzes containing its questions.
$subjectModuleId = $insertModule('Subject Deletion Module');
$subjectDeleteId = $insertSubject($subjectModuleId, 'Subject With Quiz');
$subjectQuestionId = $insertQuestion($subjectDeleteId, 'mcq', 'Subject deletion question');
[$subjectQuizId, $subjectQuizQuestionId] = $insertQuiz($subjectModuleId, $subjectQuestionId);
$subjectDelete = Academic::deleteSubject($subjectDeleteId);
$check($subjectDelete['success'] === true, 'Subject deletion succeeds with a dependent in-progress quiz');
$check((int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $subjectQuizId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quiz_questions WHERE id = ' . $subjectQuizQuestionId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quiz_answers WHERE quiz_question_id = ' . $subjectQuizQuestionId)->fetchColumn() === 0, 'Subject deletion removes the quiz, links, and answers');
$check((int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $unrelatedQuizId)->fetchColumn() === 1, 'Subject deletion preserves an unrelated quiz');

// Question deletion implicitly discards only quizzes referencing that question.
$questionModuleId = $insertModule('Question Deletion Module');
$questionSubjectId = $insertSubject($questionModuleId, 'Question Subject');
$questionDeleteId = $insertQuestion($questionSubjectId, 'mcq', 'Question with quiz');
[$questionQuizId, $questionQuizQuestionId] = $insertQuiz($questionModuleId, $questionDeleteId);
$questionDelete = Question::deleteQuestion($questionDeleteId);
$check($questionDelete['success'] === true, 'Question deletion succeeds with a dependent in-progress quiz');
$check((int)$pdo->query('SELECT COUNT(*) FROM questions WHERE id = ' . $questionDeleteId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $questionQuizId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quiz_questions WHERE id = ' . $questionQuizQuestionId)->fetchColumn() === 0 && (int)$pdo->query('SELECT COUNT(*) FROM quiz_answers WHERE quiz_question_id = ' . $questionQuizQuestionId)->fetchColumn() === 0, 'Question deletion removes the question and complete dependent quiz instance');
$check((int)$pdo->query('SELECT COUNT(*) FROM questions WHERE id = ' . $unrelatedQuestionId)->fetchColumn() === 1 && (int)$pdo->query('SELECT COUNT(*) FROM quizzes WHERE id = ' . $unrelatedQuizId)->fetchColumn() === 1, 'Question deletion preserves unrelated question and quiz');

$check((int)$pdo->query('SELECT COUNT(*) FROM pragma_foreign_key_check')->fetchColumn() === 0, 'Academic deletions leave no foreign-key violations');

echo "Deletion Integrity Test Results: {$passed} Passed, {$failed} Failed" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
