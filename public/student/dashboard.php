<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Student Dashboard Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

// Route guard: Require logged-in student
Auth::requireStudent();

$user = Auth::user();

// Fetch summary metrics
$modulesCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM modules')['cnt'] ?? 0);
$questionsCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM questions')['cnt'] ?? 0);
$stats = [
    'modules'    => $modulesCount,
    'questions'  => $questionsCount,
];

View::render('student/dashboard', [
    'pageTitle' => 'Student Portal',
    'user'      => $user,
    'stats'     => $stats,
], 'main');
