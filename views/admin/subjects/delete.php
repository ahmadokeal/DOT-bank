<div class="admin-subject-delete-container" style="max-width: 580px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/subjects.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Subjects
        </a>
        <h1 style="color: var(--error);">Confirm Subject Deletion</h1>
    </div>

    <div class="card" style="border-color: var(--error-border);">
        <p style="font-size: 1.05rem; margin-bottom: 0.75rem;">
            Are you sure you want to permanently delete the subject:
            <br>
            <strong style="color: var(--dark); font-size: 1.2rem;"><?= e($subject['name']) ?></strong>
            <span style="color: var(--text-muted); font-size: 0.9rem;">(Module: <?= e($subject['module_name']) ?>)</span>?
        </p>

        <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.5rem;">
            This action cannot be undone.
        </p>

        <form method="POST" action="<?= url('admin/subject-delete.php?id=' . (int)$subject['id']) ?>">
            <?= CSRF::field() ?>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/subjects.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn" style="background-color: var(--error); color: #ffffff;">
                    Permanently Delete Subject
                </button>
            </div>
        </form>
    </div>
</div>
