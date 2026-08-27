<div class="admin-module-form-container" style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/modules.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Modules
        </a>
        <h1><?= $isEdit ? 'Edit Module' : 'Create Academic Module' ?></h1>
        <p style="color: var(--text-muted);"><?= $isEdit ? 'Update details for this medical module.' : 'Add a new medical system or subject module.' ?></p>
    </div>

    <div class="card">
        <form method="POST" action="<?= url('admin/module-form.php' . ($isEdit ? '?id=' . (int)$module['id'] : '')) ?>">
            <?= CSRF::field() ?>

            <div class="form-group">
                <label class="form-label" for="name">Module Name <span style="color: var(--error);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($name ?? ($module['name'] ?? '')) ?>" placeholder="e.g. Cardiovascular System, Respiratory System" required autofocus>
                <div class="form-help">Must be unique, 2 to 100 characters.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span style="font-weight: normal; color: var(--text-muted);">(Optional)</span></label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Brief summary of the module topics and medical curriculum..."><?= e($description ?? ($module['description'] ?? '')) ?></textarea>
                <div class="form-help">Optional overview for students.</div>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/modules.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Save Changes' : 'Create Module' ?>
                </button>
            </div>
        </form>
    </div>
</div>
