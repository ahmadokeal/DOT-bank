<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Subjects Management Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$selectedModuleId = isset($_GET['module_id']) && $_GET['module_id'] !== '' ? (int)$_GET['module_id'] : null;
$modules = Academic::getAllModules();
$subjects = Academic::getAllSubjects($selectedModuleId);

$selectedModule = null;
if ($selectedModuleId) {
    $selectedModule = Academic::getModuleById($selectedModuleId);
}

View::render('admin/subjects/index', [
    'pageTitle'        => 'Academic Subjects',
    'modules'          => $modules,
    'subjects'         => $subjects,
    'selectedModuleId' => $selectedModuleId,
    'selectedModule'   => $selectedModule,
], 'main');
