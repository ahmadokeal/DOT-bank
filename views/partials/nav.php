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

<style>
    .app-header .nav-toggle { display: none; }
    .app-header .nav-toggle-bar { display: block; width: 20px; height: 2px; margin: 3px 0; border-radius: 2px; background: var(--primary); }
    @media (max-width: 768px) {
        .app-header .header-container { display: flex; flex-wrap: wrap; gap: .5rem; }
        .app-header .nav-toggle { display: flex; margin-left: auto; width: 42px; height: 42px; padding: .55rem; flex-direction: column; align-items: center; justify-content: center; border: 1px solid var(--border); border-radius: var(--radius-md); background: transparent; cursor: pointer; }
        .app-header nav { display: none; width: 100%; flex-basis: 100%; order: 3; }
        .app-header nav.nav-open { display: block; }
        .app-header .nav-links { display: flex; flex-direction: column; align-items: stretch; width: 100%; margin: .25rem 0 0; padding: .5rem 0 0; border-top: 1px solid var(--border); gap: .2rem; }
        .app-header .nav-links li, .app-header .nav-link { width: 100%; }
        .app-header .nav-link { display: block; }
        .app-header .user-nav-section { width: 100%; order: 4; justify-content: space-between; padding-top: .5rem; border-top: 1px solid var(--border); }
    }
</style>
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
