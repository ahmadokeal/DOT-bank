<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Main Entry Point
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (!is_installed()) {
    header('Location: ' . url('setup.php'));
    exit;
}

if (Auth::check()) {
    if (Auth::isAdmin()) {
        header('Location: ' . url('admin/dashboard.php'));
    } else {
        header('Location: ' . url('student/dashboard.php'));
    }
    exit;
}

header('Location: ' . url('login.php'));
exit;
