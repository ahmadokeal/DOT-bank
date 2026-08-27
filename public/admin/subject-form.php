<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Subject Create & Edit Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null && $id > 0;
$subject = null;

if ($isEdit) {
    $subject = Academic::getSubjectById($id);
    if (!$subject) {
        View::flash('error', 'Subject not found.');
        header('Location: ' . url('admin/subjects.php'));
        exit;
    }
}

$modules = Academic::getAllModules();
$moduleId = isset($_GET['module_id']) ? (int)$_GET['module_id'] : ($subject['module_id'] ?? null);
$name = $subject['name'] ?? '';
$description = $subject['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $moduleId = (int)($_POST['module_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($isEdit) {
            $result = Academic::updateSubject($id, $moduleId, $name, $description);
        } else {
            $result = Academic::createSubject($moduleId, $name, $description);
        }

        if ($result['success']) {
            View::flash('success', $result['message']);
            header('Location: ' . url('admin/subjects.php?module_id=' . $moduleId));
            exit;
        } else {
            View::flash('error', $result['message']);
        }
    }
}

View::render('admin/subjects/form', [
    'pageTitle'   => $isEdit ? 'Edit Subject' : 'Create Subject',
    'isEdit'      => $isEdit,
    'subject'     => $subject,
    'modules'     => $modules,
    'moduleId'    => $moduleId,
    'name'        => $name,
    'description' => $description,
], 'main');
