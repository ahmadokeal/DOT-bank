<div class="admin-subject-form-container" style="max-width: 680px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/subjects.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Subjects
        </a>
        <h1><?= $isEdit ? 'Edit Subject' : 'Create Academic Subject' ?></h1>
        <p style="color: var(--text-muted);"><?= $isEdit ? 'Update details for this subject.' : 'Add a new subject to a medical module.' ?></p>
    </div>

    <div class="card">
        <form method="POST" action="<?= url('admin/subject-form.php' . ($isEdit ? '?id=' . (int)$subject['id'] : '')) ?>">
            <?= CSRF::field() ?>

            <div class="form-group">
                <label class="form-label" for="module_id">Parent Module <span style="color: var(--error);">*</span></label>
                <select id="module_id" name="module_id" class="form-control" required <?= empty($modules) ? 'disabled' : '' ?>>
                    <?php if (empty($modules)): ?>
                        <option value="">-- No modules available. Please create a module first. --</option>
                    <?php else: ?>
                        <option value="">-- Select Parent Module --</option>
                        <?php foreach ($modules as $mod): ?>
                            <?php 
                                $selected = (isset($moduleId) && (int)$moduleId === (int)$mod['id']) || 
                                           ($isEdit && (int)$subject['module_id'] === (int)$mod['id']);
                            ?>
                            <option value="<?= (int)$mod['id'] ?>" <?= $selected ? 'selected' : '' ?>>
                                <?= e($mod['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="form-help">The academic module this subject belongs to.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Subject Name <span style="color: var(--error);">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="<?= e($name ?? ($subject['name'] ?? '')) ?>" placeholder="e.g. Pathology, Physiology, Anatomy & Histology" required autofocus>
                <div class="form-help">Must be unique within the selected module, 2 to 100 characters.</div>
            </div>

            <div class="form-group">
                <label class="form-label" for="description">Description <span style="font-weight: normal; color: var(--text-muted);">(Optional)</span></label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Brief summary of syllabus or topics covered in this subject..."><?= e($description ?? ($subject['description'] ?? '')) ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 1.25rem;">
                <a href="<?= url('admin/subjects.php') ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" <?= empty($modules) ? 'disabled' : '' ?>>
                    <?= $isEdit ? 'Save Changes' : 'Create Subject' ?>
                </button>
            </div>
        </form>
    </div>
</div>
