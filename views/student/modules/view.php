<div class="student-module-view-container">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('student/modules.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Modules
        </a>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><?= e($module['name']) ?></h1>
                <p style="color: var(--text-muted);"><?= !empty($module['description']) ? e($module['description']) : 'Module details and affiliated subjects.' ?></p>
            </div>
            <span class="badge badge-student" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                <?= (int)$module['subject_count'] ?> Subject<?= (int)$module['subject_count'] === 1 ? '' : 's' ?>
            </span>
        </div>
    </div>

    <div class="card" style="margin-bottom: 1.5rem;">
        <div class="card-header">
            <h2 class="card-title">Curriculum Subjects</h2>
        </div>

        <?php if (empty($module['subjects'])): ?>
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
                <p>No subjects have been registered under this module yet.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php foreach ($module['subjects'] as $sub): ?>
                    <div style="padding: 1rem 1.25rem; background: var(--bg-page); border: 1px solid var(--border); border-radius: var(--radius-sm); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem;">
                        <div>
                            <h3 style="font-size: 1.05rem; margin-bottom: 0.25rem; color: var(--dark);">
                                <?= e($sub['name']) ?>
                            </h3>
                            <p style="font-size: 0.875rem; color: var(--text-muted); margin: 0;">
                                <?= !empty($sub['description']) ? e($sub['description']) : '<em>No description available</em>' ?>
                            </p>
                        </div>
                        <div>
                            <a href="<?= url('student/questions.php?subject_id=' . (int)$sub['id']) ?>" class="btn btn-primary btn-sm">
                                Browse Questions &rarr;
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
