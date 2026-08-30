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
            <button class="nav-toggle" type="button" aria-controls="primary-navigation" aria-expanded="false" aria-label="Open navigation">
                <span class="nav-toggle-bar" aria-hidden="true"></span>
                <span class="nav-toggle-bar" aria-hidden="true"></span>
                <span class="nav-toggle-bar" aria-hidden="true"></span>
            </button>

            <nav id="primary-navigation" aria-label="Primary navigation">
                <ul class="nav-links">
                    <?php if ($isAdmin): ?>
                        <li>
                            <a href="<?= url('admin/dashboard.php') ?>" class="nav-link <?= ($currentScript === 'dashboard.php' && str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'admin')) ? 'active' : '' ?>">
                                <i class="fa-solid fa-gauge-high nav-icon" aria-hidden="true"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/modules.php') ?>" class="nav-link <?= str_contains($currentScript, 'module') ? 'active' : '' ?>">
                                <i class="fa-solid fa-layer-group nav-icon" aria-hidden="true"></i><span>Modules</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/subjects.php') ?>" class="nav-link <?= str_contains($currentScript, 'subject') ? 'active' : '' ?>">
                                <i class="fa-solid fa-book-open nav-icon" aria-hidden="true"></i><span>Subjects</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/questions.php') ?>" class="nav-link <?= str_contains($currentScript, 'question') ? 'active' : '' ?>">
                                <i class="fa-solid fa-circle-question nav-icon" aria-hidden="true"></i><span>Questions</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('admin/import.php') ?>" class="nav-link <?= $currentScript === 'import.php' ? 'active' : '' ?>">
                                <i class="fa-solid fa-file-import nav-icon" aria-hidden="true"></i><span>JSON Import</span>
                            </a>
                        </li>
                    <?php else: ?>
                        <li>
                            <a href="<?= url('student/dashboard.php') ?>" class="nav-link <?= ($currentScript === 'dashboard.php' && str_contains($_SERVER['SCRIPT_NAME'] ?? '', 'student')) ? 'active' : '' ?>">
                                <i class="fa-solid fa-gauge-high nav-icon" aria-hidden="true"></i><span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/modules.php') ?>" class="nav-link <?= str_contains($currentScript, 'module') ? 'active' : '' ?>">
                                <i class="fa-solid fa-layer-group nav-icon" aria-hidden="true"></i><span>Curriculum Modules</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/questions.php') ?>" class="nav-link <?= str_contains($currentScript, 'question') ? 'active' : '' ?>">
                                <i class="fa-solid fa-book-medical nav-icon" aria-hidden="true"></i><span>Browse Questions</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?= url('student/quiz-builder.php') ?>" class="nav-link <?= str_contains($currentScript, 'quiz') ? 'active' : '' ?>">
                                <i class="fa-solid fa-pen-to-square nav-icon" aria-hidden="true"></i><span>Create Quiz</span>
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
                <a href="<?= url('logout.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i><span>Log Out</span></a>
            </div>
        <?php else: ?>
            <div class="user-nav-section">
                <a href="<?= url('login.php') ?>" class="btn btn-secondary btn-sm">Log In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-primary btn-sm">Register</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var navigation = document.getElementById('primary-navigation');
        var toggle = document.querySelector('.nav-toggle');
        if (!navigation || !toggle) return;
        toggle.addEventListener('click', function () {
            var open = navigation.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
        });
        navigation.querySelectorAll('.nav-link').forEach(function (link) {
            link.addEventListener('click', function () {
                navigation.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open navigation');
            });
        });
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) navigation.classList.remove('nav-open');
        });
    });
</script>
