<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Module Create & Edit Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isEdit = $id !== null && $id > 0;
$module = null;

if ($isEdit) {
    $module = Academic::getModuleById($id);
    if (!$module) {
        View::flash('error', 'Module not found.');
        header('Location: ' . url('admin/modules.php'));
        exit;
    }
}

$name = $module['name'] ?? '';
$description = $module['description'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($isEdit) {
            $result = Academic::updateModule($id, $name, $description);
        } else {
            $result = Academic::createModule($name, $description);
        }

        if ($result['success']) {
            View::flash('success', $result['message']);
            header('Location: ' . url('admin/modules.php'));
            exit;
        } else {
            View::flash('error', $result['message']);
        }
    }
}

View::render('admin/modules/form', [
    'pageTitle'   => $isEdit ? 'Edit Module' : 'Create Module',
    'isEdit'      => $isEdit,
    'module'      => $module,
    'name'        => $name,
    'description' => $description,
], 'main');
