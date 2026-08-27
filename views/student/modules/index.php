<div class="student-modules-container">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h1>Academic Modules</h1>
            <p style="color: var(--text-muted);">Explore medical curriculum modules and their subjects.</p>
        </div>
        <div>
            <span class="badge badge-student" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">Medical Curriculum</span>
        </div>
    </div>

    <?php if (empty($modules)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-muted);">📚</div>
            <h3 style="margin-bottom: 0.5rem;">No Modules Available</h3>
            <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto;">
                Academic modules are currently being prepared by the faculty administrator. Please check back shortly.
            </p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php foreach ($modules as $mod): ?>
                <div class="card" style="display: flex; flex-direction: column; height: 100%;">
                    <div style="margin-bottom: 0.75rem;">
                        <h3 style="font-size: 1.2rem; color: var(--dark); margin-bottom: 0.35rem;">
                            <?= e($mod['name']) ?>
                        </h3>
                        <span class="badge badge-student">
                            <?= (int)$mod['subject_count'] ?> Subject<?= (int)$mod['subject_count'] === 1 ? '' : 's' ?>
                        </span>
                    </div>

                    <p style="color: var(--text-muted); font-size: 0.9rem; flex: 1; margin-bottom: 1.25rem;">
                        <?= !empty($mod['description']) ? e($mod['description']) : '<em>No detailed description provided for this module.</em>' ?>
                    </p>

                    <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: auto;">
                        <a href="<?= url('student/module-view.php?id=' . (int)$mod['id']) ?>" class="btn btn-secondary btn-block">
                            Browse Module Subjects &rarr;
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
