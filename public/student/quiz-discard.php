<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireStudent();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !CSRF::verify()) {
    View::flash('error', 'Your request could not be verified.');
    header('Location: ' . url('student/quiz-builder.php'));
    exit;
}
$result = Quiz::discard((int) ($_POST['quiz_id'] ?? 0), (int) Auth::id());
View::flash($result['success'] ? 'success' : 'error', $result['message'] ?? 'The quiz could not be discarded.');
header('Location: ' . url('student/quiz-builder.php'));
exit;
