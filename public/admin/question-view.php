<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Question Detail View Controller
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

View::render('admin/questions/view', [
    'pageTitle' => 'View Question',
    'question'  => $question,
], 'main');
