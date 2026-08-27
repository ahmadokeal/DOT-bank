<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Student Single Module & Subjects Browser Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireStudent();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$module = Academic::getModuleById($id);

if (!$module) {
    View::flash('error', 'Module not found.');
    header('Location: ' . url('student/modules.php'));
    exit;
}

View::render('student/modules/view', [
    'pageTitle' => $module['name'],
    'module'    => $module,
], 'main');
