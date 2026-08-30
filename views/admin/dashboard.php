<div class="admin-dashboard-container dashboard-page">
    <div class="dashboard-hero">
        <div>
            <h1>Admin Dashboard</h1>
            <p style="color: var(--text-muted);">Welcome back, <strong><?= e($user['username'] ?? 'Admin') ?></strong>. Platform management overview.</p>
        </div>
        <div class="dashboard-actions">
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm">+ Add Question</a>
            <a href="<?= url('admin/module-form.php') ?>" class="btn btn-secondary btn-sm">+ Add Module</a>
            <a href="<?= url('admin/subject-form.php') ?>" class="btn btn-secondary btn-sm">+ Add Subject</a>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <a href="<?= url('admin/modules.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title">Modules</span>
            <span class="metric-value"><?= (int)($stats['modules'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/subjects.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title">Subjects</span>
            <span class="metric-value"><?= (int)($stats['subjects'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/questions.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title">Questions</span>
            <span class="metric-value"><?= (int)($stats['questions'] ?? 0) ?></span>
        </a>
        <div class="metric-card dashboard-metric">
            <span class="metric-title">Registered Students</span>
            <span class="metric-value"><?= (int)($stats['students'] ?? 0) ?></span>
        </div>
        <div class="metric-card dashboard-metric">
            <span class="metric-title">Quizzes Generated</span>
            <span class="metric-value"><?= (int)($stats['quizzes'] ?? 0) ?></span>
        </div>
        <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title">Unanswered Questions</span>
            <span class="metric-value" style="color: var(--warning);"><?= (int)($stats['unanswered'] ?? 0) ?></span>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Question Bank</h2>
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm">+ Add Question</a>
        </div>
        <p style="color: var(--text);">
            Manage medical exam questions across all modules and subjects. All 5 question types are supported: MCQ, Complete, Match, Compare, and Essay.
        </p>
        <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?= url('admin/questions.php') ?>" class="btn btn-primary">Browse Question Bank (Choose a Module)</a>
            <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="btn btn-secondary">Unanswered (<?= (int)($stats['unanswered'] ?? 0) ?>)</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Academic Hierarchy</h2>
            <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary btn-sm">Manage Modules &rarr;</a>
        </div>
        <p style="color: var(--text);">
            Academic hierarchy is established: <strong>Module &rarr; Subject &rarr; Questions</strong>.
        </p>
        <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary">View All Modules (<?= (int)($stats['modules'] ?? 0) ?>)</a>
            <a href="<?= url('admin/subjects.php') ?>" class="btn btn-secondary">View All Subjects (<?= (int)($stats['subjects'] ?? 0) ?>)</a>
        </div>
    </div>
</div>
