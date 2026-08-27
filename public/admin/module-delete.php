<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Module Safe Deletion Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$module = Academic::getModuleById($id);

if (!$module) {
    View::flash('error', 'Module not found.');
    header('Location: ' . url('admin/modules.php'));
    exit;
}

$subjectCount = (int)($module['subject_count'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $result = Academic::deleteModule($id);
        if ($result['success']) {
            View::flash('success', $result['message']);
        } else {
            View::flash('error', $result['message']);
        }
        header('Location: ' . url('admin/modules.php'));
        exit;
    }
}

View::render('admin/modules/delete', [
    'pageTitle'    => 'Confirm Delete Module',
    'module'       => $module,
    'subjectCount' => $subjectCount,
], 'main');
