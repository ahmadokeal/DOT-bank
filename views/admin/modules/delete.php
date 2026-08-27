<div class="admin-module-delete-container" style="max-width: 580px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/modules.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Modules
        </a>
        <h1 style="color: var(--error);">Confirm Module Deletion</h1>
    </div>

    <div class="card" style="border-color: var(--error-border);">
        <div style="margin-bottom: 1.25rem;">
            <p style="font-size: 1.05rem; margin-bottom: 0.75rem;">
                Are you sure you want to permanently delete the module:
                <br>
                <strong style="color: var(--dark); font-size: 1.2rem;"><?= e($module['name']) ?></strong>?
            </p>

            <?php if ($subjectCount > 0): ?>
                <div class="alert alert-warning" style="margin-top: 1rem;">
                    <div>
                        <strong style="display: block; margin-bottom: 0.25rem;">⚠️ Warning: Associated Data Affected</strong>
                        <span>This module currently contains <strong><?= $subjectCount ?> subject<?= $subjectCount === 1 ? '' : 's' ?></strong>. Deleting this module will permanently remove all associated subjects.</span>
                    </div>
                </div>

                <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: var(--bg-page); border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <p style="font-weight: 600; font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">
                        Subjects to be removed:
                    </p>
                    <ul style="margin-left: 1.25rem; font-size: 0.9rem; color: var(--text);">
                        <?php foreach ($module['subjects'] as $sub): ?>
                            <li><?= e($sub['name']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    This module currently has no associated subjects. It can be safely deleted.
                </p>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?= url('admin/module-delete.php?id=' . (int)$module['id']) ?>">
            <?= CSRF::field() ?>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 1.5rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn" style="background-color: var(--error); color: #ffffff;">
                    Permanently Delete Module
                </button>
            </div>
        </form>
    </div>
</div>
