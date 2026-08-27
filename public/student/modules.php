<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Student Modules Browser Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireStudent();

$modules = Academic::getAllModules();

View::render('student/modules/index', [
    'pageTitle' => 'Academic Modules',
    'modules'   => $modules,
], 'main');
