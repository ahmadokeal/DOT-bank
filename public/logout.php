<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Logout Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

Auth::logout();
session_start();
View::flash('info', 'You have been successfully logged out.');
header('Location: ' . url('login.php'));
exit;
