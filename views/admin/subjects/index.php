<div class="admin-subjects-container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Academic Subjects</h1>
            <p style="color: var(--text-muted);">
                <?= $selectedModule ? 'Subjects under module: <strong>' . e($selectedModule['name']) . '</strong>' : 'All subjects organized by parent module.' ?>
            </p>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <?php if ($selectedModule): ?>
                <a href="<?= url('admin/subjects.php') ?>" class="btn btn-secondary">Show All Subjects</a>
                <a href="<?= url('admin/subject-form.php?module_id=' . (int)$selectedModule['id']) ?>" class="btn btn-primary">
                    + Add Subject to <?= e($selectedModule['name']) ?>
                </a>
            <?php else: ?>
                <a href="<?= url('admin/subject-form.php') ?>" class="btn btn-primary">
                    + Add Subject
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter by Module bar -->
    <?php if (!empty($modules)): ?>
        <div class="card" style="padding: 1rem; margin-bottom: 1.25rem; background: var(--bg-card);">
            <form method="GET" action="<?= url('admin/subjects.php') ?>" style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <label for="filter_module_id" style="font-weight: 600; font-size: 0.875rem;">Filter by Module:</label>
                <select name="module_id" id="filter_module_id" class="form-control" style="width: auto; min-width: 240px;" onchange="this.form.submit()">
                    <option value="">-- All Modules (<?= count($subjects) ?> total subjects) --</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= (int)$m['id'] ?>" <?= ($selectedModuleId === (int)$m['id']) ? 'selected' : '' ?>>
                            <?= e($m['name']) ?> (<?= (int)$m['subject_count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($selectedModuleId): ?>
                    <a href="<?= url('admin/subjects.php') ?>" style="font-size: 0.85rem; color: var(--text-muted);">Clear filter</a>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if (empty($subjects)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-muted);">📖</div>
            <h3 style="margin-bottom: 0.5rem;">No Subjects Found</h3>
            <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto 1.5rem auto;">
                <?= $selectedModule ? 'No subjects have been created under ' . e($selectedModule['name']) . ' yet.' : 'No subjects have been added to any academic module yet.' ?>
            </p>
            <a href="<?= url('admin/subject-form.php' . ($selectedModuleId ? '?module_id=' . $selectedModuleId : '')) ?>" class="btn btn-primary">
                Add First Subject
            </a>
        </div>
    <?php else: ?>
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
                    <thead>
                        <tr style="background: var(--bg-page); border-bottom: 1px solid var(--border);">
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Subject Name</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Parent Module</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Description</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $sub): ?>
                            <tr style="border-bottom: 1px solid var(--border);">
                                <td style="padding: 1rem 1.25rem; font-weight: 600; color: var(--dark);">
                                    <?= e($sub['name']) ?>
                                </td>
                                <td style="padding: 1rem 1.25rem;">
                                    <a href="<?= url('admin/subjects.php?module_id=' . (int)$sub['module_id']) ?>" class="badge badge-student" style="text-decoration: none;">
                                        <?= e($sub['module_name']) ?>
                                    </a>
                                </td>
                                <td style="padding: 1rem 1.25rem; color: var(--text-muted); max-width: 300px;">
                                    <?= !empty($sub['description']) ? e($sub['description']) : '<em>No description</em>' ?>
                                </td>
                                <td style="padding: 1rem 1.25rem; text-align: right; white-space: nowrap;">
                                    <a href="<?= url('admin/subject-form.php?id=' . (int)$sub['id']) ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;">
                                        Edit
                                    </a>
                                    <a href="<?= url('admin/subject-delete.php?id=' . (int)$sub['id']) ?>" class="btn btn-sm" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border);">
                                        Delete
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
