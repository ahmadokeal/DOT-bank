<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::verify()) {
    View::flash('error', 'Your request could not be verified.');
    header('Location: ' . url('student/quiz-builder.php'));
    exit;
}
$quizId = (int)($_POST['quiz_id'] ?? 0);
$result = Quiz::discard($quizId, (int) Auth::id());
if (isset($_SESSION['_quiz_match_display'][$quizId])) unset($_SESSION['_quiz_match_display'][$quizId]);
View::flash($result['success'] ? 'success' : 'error', $result['message'] ?? 'The quiz could not be discarded.');
header('Location: ' . url('student/quiz-builder.php'));
exit;
