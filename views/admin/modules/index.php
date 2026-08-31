<div class="admin-modules-container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Academic Modules</h1>
            <p style="color: var(--text-muted);">Manage high-level academic modules and their associated subjects.</p>
        </div>
        <div>
            <a href="<?= url('admin/module-form.php') ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Module</span>
            </a>
        </div>
    </div>

    <?php if (empty($modules)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-muted);">📚</div>
            <h3 style="margin-bottom: 0.5rem;">No Modules Found</h3>
            <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.5rem auto;">
                No academic modules have been created yet. Get started by creating your first medical module.
            </p>
            <a href="<?= url('admin/module-form.php') ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Create First Module</span>
            </a>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
                    <thead>
                        <tr style="background: var(--bg-page); border-bottom: 1px solid var(--border);">
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Module Name</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Description</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: center;">Subjects</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modules as $mod): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem 1.25rem; font-weight: 600; color: var(--dark);">
                                    <a href="<?= url('admin/subjects.php?module_id=' . (int)$mod['id']) ?>" style="color: var(--primary);">
                                        <?= e($mod['name']) ?>
                                    </a>
                                </td>
                                <td style="padding: 1rem 1.25rem; color: var(--text-muted); max-width: 350px;">
                                    <?= !empty($mod['description']) ? e($mod['description']) : '<em>No description provided</em>' ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; text-align: center;">
                                    <a href="<?= url('admin/subjects.php?module_id=' . (int)$mod['id']) ?>" class="badge badge-student" style="text-decoration: none;">
                                        <?= (int)$mod['subject_count'] ?> Subject<?= (int)$mod['subject_count'] === 1 ? '' : 's' ?>
                                    </a>
                                </td>
                                <td style="padding: 1rem 1.25rem; text-align: right; white-space: nowrap;">
                                    <a href="<?= url('admin/subjects.php?module_id=' . (int)$mod['id']) ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;">
                                        <i class="fa-solid fa-book-open" aria-hidden="true"></i><span>View Subjects</span>
                                    </a>
                                    <a href="<?= url('admin/module-form.php?id=' . (int)$mod['id']) ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;">
                                        <i class="fa-solid fa-pen" aria-hidden="true"></i><span>Edit</span>
                                    </a>
                                    <a href="<?= url('admin/module-delete.php?id=' . (int)$mod['id']) ?>" class="btn btn-sm" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border);">
                                        <i class="fa-solid fa-trash" aria-hidden="true"></i><span>Delete</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
