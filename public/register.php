<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Student Registration Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

// If not installed, redirect to setup wizard
if (!is_installed()) {
    header('Location: ' . url('setup.php'));
    exit;
}

// If already logged in, redirect to dashboard
if (Auth::check()) {
    if (Auth::isAdmin()) {
        header('Location: ' . url('admin/dashboard.php'));
    } else {
        header('Location: ' . url('student/dashboard.php'));
    }
    exit;
}

$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $result = Auth::registerStudent($username, $password, $confirmPassword);

        if ($result['success']) {
            View::flash('success', $result['message']);
            header('Location: ' . url('login.php'));
            exit;
        } else {
            View::flash('error', $result['message']);
        }
    }
}

View::render('auth/register', [
    'pageTitle' => 'Student Registration',
    'username'  => $username,
], 'auth');
