<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * User Login Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

// If not installed, redirect to setup wizard
if (!is_installed()) {
    header('Location: ' . url('setup.php'));
    exit;
}

// If already logged in, redirect to respective dashboard
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

        $result = Auth::attempt($username, $password);

        if ($result['success']) {
            $role = $result['user']['role'];
            View::flash('success', 'Welcome back, ' . e($result['user']['username']) . '!');
            if ($role === 'admin') {
                header('Location: ' . url('admin/dashboard.php'));
            } else {
                header('Location: ' . url('student/dashboard.php'));
            }
            exit;
        } else {
            View::flash('error', $result['message']);
        }
    }
}

View::render('auth/login', [
    'pageTitle' => 'Sign In',
    'username'  => $username,
], 'auth');
