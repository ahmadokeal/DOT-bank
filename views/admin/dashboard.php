<div class="admin-dashboard-container dashboard-page">
    <div class="dashboard-hero">
        <div>
            <h1>Admin Dashboard</h1>
            <p style="color: var(--text-muted);">Welcome back, <strong><?= e($user['username'] ?? 'Admin') ?></strong>. Platform management overview.</p>
        </div>
        <div class="dashboard-actions">
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Question</span></a>
            <a href="<?= url('admin/module-form.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Module</span></a>
            <a href="<?= url('admin/subject-form.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Subject</span></a>
        </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
        <a href="<?= url('admin/modules.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title"><i class="fa-solid fa-layer-group metric-icon" aria-hidden="true"></i>Modules</span>
            <span class="metric-value"><?= (int)($stats['modules'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/subjects.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title"><i class="fa-solid fa-book-open metric-icon" aria-hidden="true"></i>Subjects</span>
            <span class="metric-value"><?= (int)($stats['subjects'] ?? 0) ?></span>
        </a>
        <a href="<?= url('admin/questions.php') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
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
        <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="metric-card dashboard-metric" style="text-decoration: none;">
            <span class="metric-title"><i class="fa-solid fa-triangle-exclamation metric-icon" aria-hidden="true"></i>Unanswered Questions</span>
            <span class="metric-value" style="color: var(--warning);"><?= (int)($stats['unanswered'] ?? 0) ?></span>
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Question Bank</h2>
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Question</span></a>
        </div>
        <p style="color: var(--text);">
            Manage medical exam questions across all modules and subjects. All 6 question types are supported: MCQ, Complete, Match, Compare, Essay, and True / False.
        </p>
        <div style="margin-top: 1rem; display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <a href="<?= url('admin/questions.php') ?>" class="btn btn-primary"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Browse Question Bank (Choose a Module)</span></a>
            <a href="<?= url('admin/questions.php?answer_status=unavailable') ?>" class="btn btn-secondary">Unanswered (<?= (int)($stats['unanswered'] ?? 0) ?>)</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Academic Hierarchy</h2>
            <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i><span>Manage Modules</span></a>
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

<style>
    .admin-dashboard-container .dashboard-hero {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        min-height: 190px;
        margin-bottom: 2.25rem !important;
        padding: 2rem 2.25rem !important;
    }

    .admin-dashboard-container .dashboard-hero > div:first-child {
        min-width: 0;
    }

    .admin-dashboard-container .dashboard-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: .75rem;
        flex-wrap: wrap;
    }

    @media (max-width: 700px) {
        .admin-dashboard-container .dashboard-hero {
            align-items: flex-start;
            flex-direction: column;
            gap: 1.5rem;
            padding: 1.5rem !important;
        }

        .admin-dashboard-container .dashboard-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }

    @media (max-width: 480px) {
        .admin-dashboard-container .dashboard-actions,
        .admin-dashboard-container .dashboard-actions .btn {
            width: 100%;
        }
    }
</style>
