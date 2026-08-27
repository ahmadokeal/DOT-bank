<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Modules Management Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireAdmin();

$modules = Academic::getAllModules();

View::render('admin/modules/index', [
    'pageTitle' => 'Academic Modules',
    'modules'   => $modules,
], 'main');
