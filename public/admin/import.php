<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/config.php';
Auth::requireAdmin();

$modules = Academic::getAllModules();
$errors = []; $preview = null; $result = null;
if (isset($_GET['cancel'])) { unset($_SESSION['_import_preview']); header('Location: ' . url('admin/import.php')); exit; }
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) { $errors[]='Your form session expired. Please try again.'; }
    elseif (isset($_POST['confirm_import'])) {
        $stored = $_SESSION['_import_preview'] ?? null;
        unset($_SESSION['_import_preview']);
        if (!$stored || empty($stored['valid'])) { $errors[] = 'The import preview expired or contains no valid questions.'; }
        else {
            try {
                $result = JsonImporter::import($stored['valid']);
                if (empty($result['success'])) {
                    $errors = $result['errors'] ?? ['The import could not be completed. No changes were saved.'];
                    $result = null;
                }
            }
            catch (Throwable $e) { error_log('DOT Bank JSON import failed: ' . $e->getMessage()); $errors[] = 'The import could not be completed. No changes were saved.'; }
        }
    } else {
        $moduleId = (int)($_POST['module_id'] ?? 0); $subjectId = (int)($_POST['subject_id'] ?? 0);
        if (!isset($_FILES['json_file']) || $_FILES['json_file']['error'] !== UPLOAD_ERR_OK) $errors[] = 'Please select a valid JSON file.';
        elseif (strtolower(pathinfo($_FILES['json_file']['name'], PATHINFO_EXTENSION)) !== 'json') $errors[] = 'Only .json files are accepted.';
        elseif ($_FILES['json_file']['size'] > JsonImporter::MAX_FILE_SIZE) $errors[] = 'The JSON file exceeds the 2 MB limit.';
        else {
            $parsed = JsonImporter::parse((string)file_get_contents($_FILES['json_file']['tmp_name']), $moduleId, $subjectId);
            if (!$parsed['success']) $errors = $parsed['errors'];
            else { $preview = $parsed; $_SESSION['_import_preview'] = $parsed; }
        }
    }
}
$selectedModuleId = (int)($_POST['module_id'] ?? $_GET['module_id'] ?? 0);
$subjects = $selectedModuleId > 0 ? Academic::getAllSubjects($selectedModuleId) : [];
$errorGroups = ['file' => [], 'json' => [], 'structure' => [], 'validation' => []];
foreach ($errors as $error) {
    $message = (string)$error;
    if (str_contains($message, 'JSON file') || str_contains($message, 'uploaded file') || str_contains($message, 'UTF-8') || str_contains($message, 'form session')) {
        $group = 'file';
    } elseif (str_contains($message, 'valid JSON')) {
        $group = 'json';
    } elseif (str_contains($message, 'top-level') || str_contains($message, 'selected subject')) {
        $group = 'structure';
    } else {
        $group = 'validation';
    }
    $errorGroups[$group][] = $message;
}
View::render('admin/import', compact('modules', 'subjects', 'errors', 'errorGroups', 'preview', 'result', 'selectedModuleId'), 'main');
