<div class="student-dashboard-container student-dashboard-page">
    <section class="student-dashboard-hero" aria-labelledby="student-dashboard-title">
        <div class="student-dashboard-hero-copy">
            <p class="student-dashboard-eyebrow"><i class="fa-solid fa-sparkles" aria-hidden="true"></i> Your study space</p>
            <h1 id="student-dashboard-title">Student Portal</h1>
            <p class="student-dashboard-welcome">Welcome, <strong><?= e($user['username'] ?? 'Student') ?></strong>. Keep your revision moving with a focused question-bank workspace.</p>
            <div class="student-dashboard-hero-actions">
                <a href="<?= url('student/quiz-builder.php') ?>" class="btn btn-primary"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i><span>Create a practice quiz</span></a>
                <a href="<?= url('student/modules.php') ?>" class="student-dashboard-text-link"><span>Explore the curriculum</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
        <div class="student-dashboard-hero-art" aria-hidden="true">
            <span class="student-dashboard-orbit student-dashboard-orbit-one"></span>
            <span class="student-dashboard-orbit student-dashboard-orbit-two"></span>
            <span class="student-dashboard-hero-icon"><i class="fa-solid fa-book-medical"></i></span>
            <span class="student-dashboard-hero-cross">+</span>
        </div>
    </section>

    <section class="student-dashboard-overview" aria-labelledby="overview-title">
        <div class="student-dashboard-section-heading">
            <div><p class="student-dashboard-kicker">At a glance</p><h2 id="overview-title">Your question bank</h2></div>
            <span class="student-dashboard-status"><i class="fa-solid fa-circle" aria-hidden="true"></i> Ready to study</span>
        </div>
        <div class="student-dashboard-metrics">
            <a href="<?= url('student/modules.php') ?>" class="student-dashboard-stat">
                <span class="student-dashboard-stat-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                <span class="student-dashboard-stat-copy"><span class="student-dashboard-stat-label">Curriculum modules</span><strong><?= (int)($stats['modules'] ?? 0) ?></strong><small>Organized medical systems</small></span>
                <i class="fa-solid fa-arrow-up-right-from-square student-dashboard-stat-arrow" aria-hidden="true"></i>
            </a>
            <div class="student-dashboard-stat student-dashboard-stat-warm">
                <span class="student-dashboard-stat-icon"><i class="fa-solid fa-stethoscope" aria-hidden="true"></i></span>
                <span class="student-dashboard-stat-copy"><span class="student-dashboard-stat-label">Available questions</span><strong><?= (int)($stats['questions'] ?? 0) ?></strong><small>Questions ready for practice</small></span>
            </div>
        </div>
    </section>

    <section class="student-dashboard-actions" aria-labelledby="actions-title">
        <div class="student-dashboard-section-heading"><div><p class="student-dashboard-kicker">Choose your next move</p><h2 id="actions-title">Study tools</h2></div></div>
        <div class="student-dashboard-tool-grid">
            <article class="student-dashboard-tool student-dashboard-tool-curriculum">
                <div class="student-dashboard-tool-topline"><span class="student-dashboard-tool-number">01</span><span class="student-dashboard-tool-icon"><i class="fa-solid fa-compass" aria-hidden="true"></i></span></div>
                <h3>Academic Modules</h3><p>Browse your medical subjects and curriculum structure, organized by system modules.</p>
                <a href="<?= url('student/modules.php') ?>" class="student-dashboard-tool-link"><span>Browse Modules &amp; Subjects</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </article>
            <article class="student-dashboard-tool student-dashboard-tool-quiz">
                <div class="student-dashboard-tool-topline"><span class="student-dashboard-tool-number">02</span><span class="student-dashboard-tool-icon"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span></div>
                <h3>Practice Quiz</h3><p>Build a focused quiz from existing questions using your subjects and preferred distributions.</p>
                <a href="<?= url('student/quiz-builder.php') ?>" class="student-dashboard-tool-link"><span>Create a Quiz</span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
            </article>
        </div>
    </section>
</div>
