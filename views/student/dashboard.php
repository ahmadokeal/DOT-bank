<div class="student-dashboard-container dashboard-page">
    <div class="dashboard-hero">
        <div class="dashboard-actions">
            <h1>Student Portal</h1>
            <p style="color: var(--text-muted);">Welcome, <strong><?= e($user['username'] ?? 'Student') ?></strong> to Doctors of Tomorrow Question Bank.</p>
        </div>
        <div>
            <a href="<?= url('student/quiz-builder.php') ?>" class="btn btn-primary btn-sm">Create Quiz &rarr;</a>
        </div>
    </div>

    <div class="metrics-grid">
        <a href="<?= url('student/modules.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title">Curriculum Modules</span>
            <span class="metric-value"><?= (int)($stats['modules'] ?? 0) ?></span>
        </a>
        <div class="metric-card dashboard-metric">
            <span class="metric-title">Available Questions</span>
            <span class="metric-value"><?= (int)($stats['questions'] ?? 0) ?></span>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Academic Modules</h2>
            <a href="<?= url('student/modules.php') ?>" class="btn btn-secondary btn-sm">View All Modules &rarr;</a>
        </div>
        <p style="color: var(--text);">
            Browse your medical subjects and curriculum structure organized by system modules.
        </p>
        <div style="margin-top: 1rem;">
            <a href="<?= url('student/modules.php') ?>" class="btn btn-primary">Browse Modules &amp; Subjects</a>
        </div>
    </div>
    <div class="card">
        <h2 class="card-title">Practice Quiz</h2>
        <p style="color: var(--text);">Build a quiz from existing questions using your selected subjects and optional distributions.</p>
        <a href="<?= url('student/quiz-builder.php') ?>" class="btn btn-primary">Create a Quiz</a>
    </div>
</div>
