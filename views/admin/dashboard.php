<div class="admin-dashboard-container dashboard-page">
    <div class="dashboard-hero">
        <div>
            <h1>Admin Dashboard</h1>
            <p class="dashboard-intro">Welcome back, <strong><?= e($user['username'] ?? 'Admin') ?></strong>. Platform management overview.</p>
        </div>
        <div class="dashboard-actions">
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Question</span></a>
            <a href="<?= url('admin/module-form.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Module</span></a>
            <a href="<?= url('admin/subject-form.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Subject</span></a>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <a href="<?= url('admin/modules.php') ?>" class="metric-card dashboard-metric dashboard-metric-link">
            <span class="metric-title"><i class="fa-solid fa-layer-group metric-icon" aria-hidden="true"></i>Modules</span>
            <span class="metric-value"><?= (int)($stats['modules'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/subjects.php') ?>" class="metric-card dashboard-metric dashboard-metric-link">
            <span class="metric-title"><i class="fa-solid fa-book-open metric-icon" aria-hidden="true"></i>Subjects</span>
            <span class="metric-value"><?= (int)($stats['subjects'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/questions.php') ?>" class="metric-card dashboard-metric dashboard-metric-link">
            <span class="metric-title"><i class="fa-solid fa-circle-question metric-icon" aria-hidden="true"></i>Questions</span>
            <span class="metric-value"><?= (int)($stats['questions'] ?? 0) ?></span>
        </a>
        <div class="metric-card dashboard-metric">
            <span class="metric-title"><i class="fa-solid fa-user-graduate metric-icon" aria-hidden="true"></i>Registered Students</span>
            <span class="metric-value"><?= (int)($stats['students'] ?? 0) ?></span>
        </div>
        <div class="metric-card dashboard-metric">
            <span class="metric-title"><i class="fa-solid fa-clipboard-check metric-icon" aria-hidden="true"></i>Quizzes Generated</span>
            <span class="metric-value"><?= (int)($stats['quizzes'] ?? 0) ?></span>
        </div>
        <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="metric-card dashboard-metric dashboard-metric-link">
            <span class="metric-title"><i class="fa-solid fa-triangle-exclamation metric-icon" aria-hidden="true"></i>Unanswered Questions</span>
            <span class="metric-value metric-value-warning"><?= (int)($stats['unanswered'] ?? 0) ?></span>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Question Bank</h2>
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Question</span></a>
        </div>
        <p class="dashboard-card-copy">
            Manage medical exam questions across all modules and subjects. All 6 question types are supported: MCQ, Complete, Match, Compare, Essay, and True / False.
        </p>
        <div class="dashboard-actions dashboard-card-actions">
            <a href="<?= url('admin/questions.php') ?>" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Browse Question Bank (Choose a Module)</span></a>
            <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="btn btn-secondary">Unanswered (<?= (int)($stats['unanswered'] ?? 0) ?>)</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Academic Hierarchy</h2>
            <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Manage Modules</span></a>
        </div>
        <p class="dashboard-card-copy">
            Academic hierarchy is established: <strong>Module &rarr; Subject &rarr; Questions</strong>.
        </p>
        <div class="dashboard-actions dashboard-card-actions">
            <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary">View All Modules (<?= (int)($stats['modules'] ?? 0) ?>)</a>
            <a href="<?= url('admin/subjects.php') ?>" class="btn btn-secondary">View All Subjects (<?= (int)($stats['subjects'] ?? 0) ?>)</a>
        </div>
    </div>
</div>
