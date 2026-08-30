<div class="admin-import-container">
    <div class="admin-import-header">
        <div>
            <h1>Import Questions from JSON</h1>
            <p class="text-muted">Choose a module and subject, upload your JSON file, then review the import before saving.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="admin-import-feedback-stack">
            <?php foreach ([
                'file' => ['title' => 'File and upload issue', 'class' => 'alert-error'],
                'json' => ['title' => 'Invalid JSON', 'class' => 'alert-error'],
                'structure' => ['title' => 'Invalid file structure', 'class' => 'alert-error'],
                'validation' => ['title' => 'Validation issues', 'class' => 'alert-warning'],
            ] as $group => $meta): ?>
                <?php if (!empty($errorGroups[$group])): ?>
                    <div class="alert <?= $meta['class'] ?>" role="alert">
                        <strong><?= e($meta['title']) ?></strong>
                        <ul class="admin-import-feedback-list">
                            <?php foreach ($errorGroups[$group] as $error): ?><li><?= e($error) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($result): ?>
        <div class="alert alert-success admin-import-success" role="status">
            <strong>Import completed successfully.</strong>
            <span><?= (int)($result['new_questions'] ?? $result['imported'] ?? 0) ?> new question(s) imported, <?= (int)($result['added_appearances'] ?? 0) ?> exam appearance(s) added, and <?= (int)($result['duplicate_appearances'] ?? 0) ?> duplicate appearance(s) skipped.</span>
            <?php if (($result['conflicts'] ?? 0) > 0): ?><span><?= (int)$result['conflicts'] ?> answer conflict(s) were recorded for review.</span><?php endif; ?>
            <a href="<?= url('admin/questions.php') ?>">Open Question Bank</a>
        </div>
    <?php endif; ?>

    <?php if ($preview): ?>
        <section class="card admin-import-card">
            <div class="admin-import-section-heading">
                <div><span class="eyebrow">Review before saving</span><h2>Import Preview</h2></div>
                <span class="badge badge-admin">Ready for review</span>
            </div>
            <p class="admin-import-target">Target: <strong><?= e($preview['module']) ?></strong> <span aria-hidden="true">→</span> <?= e($preview['subject']) ?></p>
            <h3 class="admin-import-subheading">What will happen</h3>
            <div class="admin-import-summary-grid">
                <div><span>Total records</span><strong><?= (int)$preview['summary']['total'] ?></strong></div>
                <div><span>Valid to process</span><strong><?= (int)$preview['summary']['valid'] ?></strong></div>
                <div><span>Invalid records</span><strong><?= (int)$preview['summary']['invalid'] ?></strong></div>
                <div><span>New questions</span><strong><?= (int)($preview['summary']['new_questions'] ?? 0) ?></strong></div>
                <div><span>Existing matches</span><strong><?= (int)($preview['summary']['merge_records'] ?? 0) ?></strong></div>
                <div><span>Review warnings</span><strong><?= (int)($preview['summary']['warning_count'] ?? count($preview['warnings'])) ?></strong></div>
            </div>
            <p class="admin-import-answer-summary">With answers: <?= (int)$preview['summary']['with_answers'] ?> <span aria-hidden="true">·</span> Without answers: <?= (int)$preview['summary']['without_answers'] ?></p>
            <?php if ($preview['warnings']): ?><div class="admin-import-detail-block"><h3>Review warnings</h3><ul><?php foreach ($preview['warnings'] as $item): ?><li>Question <?= (int)$item['number'] ?>: <?= e(implode(' ', $item['warnings'])) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($preview['invalid']): ?><div class="admin-import-detail-block"><h3>Invalid records</h3><ul><?php foreach ($preview['invalid'] as $item): ?><li>Question <?= (int)$item['number'] ?>: <?= e(implode(' ', $item['errors'])) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if ($preview['duplicates']): ?><div class="admin-import-detail-block"><h3>Duplicate candidates</h3><ul><?php foreach ($preview['duplicates'] as $item): ?><li>Question <?= (int)$item['number'] ?>: <?= e($item['reason']) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <div class="admin-import-actions">
                <?php if ((int)$preview['summary']['valid'] > 0): ?>
                    <form method="POST"><input type="hidden" name="csrf_token" value="<?= e(CSRF::getToken()) ?>"><button name="confirm_import" value="1" class="btn btn-primary">Import <?= (int)$preview['summary']['valid'] ?> Valid Records</button></form>
                <?php else: ?><p class="alert alert-warning">There are no valid records to import.</p><?php endif; ?>
                <a class="btn btn-secondary" href="<?= url('admin/import.php?cancel=1') ?>">Cancel</a>
            </div>
        </section>
    <?php else: ?>
        <section class="card admin-import-card">
            <div class="admin-import-section-heading"><div><span class="eyebrow">Upload and validate</span><h2>Select an import file</h2></div></div>
            <form method="POST" enctype="multipart/form-data" class="admin-import-form">
                <?= CSRF::field() ?>
                <div class="form-group"><label class="form-label" for="module_id">Module</label><select class="form-control" id="module_id" name="module_id" onchange="window.location.href='<?= e(url('admin/import.php')) ?>?module_id='+encodeURIComponent(this.value)" required><option value="">-- Select Module --</option><?php foreach ($modules as $m): ?><option value="<?= (int)$m['id'] ?>" <?= ((int)($selectedModuleId ?? 0) === (int)$m['id']) ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label" for="subject_id">Subject</label><select class="form-control" id="subject_id" name="subject_id" required><option value="">-- Select Subject --</option><?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" data-module-id="<?= (int)$s['module_id'] ?>" <?= ((int)($_POST['subject_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label" for="json_file">JSON file</label><input class="form-control" type="file" id="json_file" name="json_file" accept=".json,application/json" required><small class="form-help">Maximum file size: 2 MB.</small></div>
                <button class="btn btn-primary" type="submit">Validate and Preview</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<script>const importModule=document.getElementById('module_id'),importSubject=document.getElementById('subject_id');if(importModule&&importSubject){const filter=()=>{[...importSubject.options].forEach(o=>o.hidden=o.value!==''&&o.dataset.moduleId!==importModule.value);if(importSubject.selectedOptions[0]?.hidden)importSubject.value='';};importModule.addEventListener('change',filter);filter();}</script>
