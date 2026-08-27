<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Question Delete Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$question = Question::getQuestionById($id);

if (!$question) {
    View::flash('error', 'Question not found.');
    header('Location: ' . url('admin/questions.php'));
    exit;
}

// Handle POST: perform deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CSRF::verify();

    $result = Question::deleteQuestion($id);

    if ($result['success']) {
        View::flash('success', 'Question #' . $id . ' has been permanently deleted.');
    } else {
        View::flash('error', $result['message'] ?? 'Deletion failed.');
    }

    header('Location: ' . url('admin/questions.php'));
    exit;
}

// GET: show confirmation page
View::render('admin/questions/delete', [
    'pageTitle' => 'Delete Question',
    'question'  => $question,
], 'main');
