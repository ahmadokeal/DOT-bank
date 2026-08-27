<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Subject Deletion Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$subject = Academic::getSubjectById($id);

if (!$subject) {
    View::flash('error', 'Subject not found.');
    header('Location: ' . url('admin/subjects.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $result = Academic::deleteSubject($id);
        if ($result['success']) {
            View::flash('success', $result['message']);
        } else {
            View::flash('error', $result['message']);
        }
        header('Location: ' . url('admin/subjects.php?module_id=' . (int)$subject['module_id']));
        exit;
    }
}

View::render('admin/subjects/delete', [
    'pageTitle' => 'Confirm Delete Subject',
    'subject'   => $subject,
], 'main');
