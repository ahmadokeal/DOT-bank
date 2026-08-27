<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Admin Dashboard Controller
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/config.php';

// Route guard: Only Admin can access
Auth::requireAdmin();

$user = Auth::user();

// Fetch summary metrics safely
$modulesCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM modules')['cnt'] ?? 0);
$subjectsCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM subjects')['cnt'] ?? 0);
$questionsCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM questions')['cnt'] ?? 0);
$studentsCount = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM users WHERE role = 'student'")['cnt'] ?? 0);
$quizzesCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM quizzes')['cnt'] ?? 0);
$unansweredCount = (int)(Database::fetchOne("SELECT COUNT(*) as cnt FROM questions WHERE answer_status = 'unavailable'")['cnt'] ?? 0);

$stats = [
    'modules'    => $modulesCount,
    'subjects'   => $subjectsCount,
    'questions'  => $questionsCount,
    'students'   => $studentsCount,
    'quizzes'    => $quizzesCount,
    'unanswered' => $unansweredCount,
];

View::render('admin/dashboard', [
    'pageTitle' => 'Admin Dashboard',
    'user'      => $user,
    'stats'     => $stats,
], 'main');
