<?php
$isLoggedIn = Auth::check();
$user = Auth::user();
$isAdmin = Auth::isAdmin();

// Determine current script for active nav highlighting
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<header class="app-header">
    <div class="header-container">
        <a href="<?= url($isLoggedIn ? ($isAdmin ? 'admin/dashboard.php' : 'student/dashboard.php') : '') ?>" class="brand-wrapper">
            <div class="brand-logo-icon">DOT</div>
            <div class="brand-text">
                <span class="brand-title"><?= APP_NAME ?></span>
                <span class="brand-subtitle"><?= APP_FULL_NAME ?></span>
            </div>
        </a>

        <?php if ($isLoggedIn): ?>
            <nav>
                <ul class="nav-links">
                    <?php if ($isAdmin): ?>
                        <li>
                            <a href="<?= url('admin/dashboard.php') ?>" class="nav-link <?= ($currentScript === 'dashboard.php' && str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'admin')) ? 'active' : '' ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/modules.php') ?>" class="nav-link <?= str_contains($currentScript, 'module') ? 'active' : '' ?>">
                                Modules
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/subjects.php') ?>" class="nav-link <?= str_contains($currentScript, 'subject') ? 'active' : '' ?>">
                                Subjects
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/questions.php') ?>" class="nav-link <?= str_contains($currentScript, 'question') ? 'active' : '' ?>">
                                Questions
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/import.php') ?>" class="nav-link <?= $currentScript === 'import.php' ? 'active' : '' ?>">
                                JSON Import
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= url('student/dashboard.php') ?>" class="nav-link <?= ($currentScript === 'dashboard.php' && str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'student')) ? 'active' : '' ?>">
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/modules.php') ?>" class="nav-link <?= str_contains($currentScript, 'module') ? 'active' : '' ?>">
                                Curriculum Modules
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/questions.php') ?>" class="nav-link <?= str_contains($currentScript, 'question') ? 'active' : '' ?>">
                                Browse Questions
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/quiz-builder.php') ?>" class="nav-link <?= str_contains($currentScript, 'quiz') ? 'active' : '' ?>">
                                Create Quiz
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <div class="user-nav-section">
                <div class="user-badge-info">
                    <strong><?= e($user['username'] ?? '') ?></strong>
                    <span class="badge badge-<?= $isAdmin ? 'admin' : 'student' ?>">
                        <?= $isAdmin ? 'Admin' : 'Student' ?>
                    </span>
                </div>
                <a href="<?= url('logout.php') ?>" class="btn btn-secondary btn-sm">Log Out</a>
            </div>
        <?php else: ?>
            <div class="user-nav-section">
                <a href="<?= url('login.php') ?>" class="btn btn-secondary btn-sm">Log In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Register</a>
            </div>
        <?php endif; ?>
    </div>
</header>
