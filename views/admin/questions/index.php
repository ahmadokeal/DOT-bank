<div class="admin-questions-container">
    <div class="question-page-header">
        <div>
            <h1>Question Bank</h1>
            <p>Select a module to open its bounded Question Bank.</p>
        </div>
        <div>
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary">
                <i class="fa-solid fa-plus" aria-hidden="true"></i><span>Add Question</span>
            </a>
        </div>
    </div>

    <!-- Filters & Search Card -->
    <div class="card question-filters-card">
        <form method="GET" action="<?= url('admin/questions.php') ?>" id="filter-form" class="question-filters-form">
            
            <div class="question-filters-grid">
                <!-- Search input -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search text, answers, sources...">
                </div>

                <!-- Filter by Module -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="module_id">Module</label>
                    <select id="module_id" name="module_id" class="form-control" onchange="document.getElementById('subject_id').value = ''; this.form.submit();">
                        <option value="">-- Select Module --</option>
                        <?php foreach ($modules as $m): ?>
                            <option value="<?= (int)$m['id'] ?>" <?= ($filters['module_id'] == $m['id']) ? 'selected' : '' ?>>
                                <?= e($m['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter by Subject -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="subject_id">Subject</label>
                    <select id="subject_id" name="subject_id" class="form-control" onchange="this.form.submit();">
                        <option value="">-- All Subjects --</option>
                        <?php foreach ($subjects as $s): ?>
                            <option value="<?= (int)$s['id'] ?>" <?= ($filters['subject_id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= e($s['name']) ?> (<?= e($s['module_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter by Question Type -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="type">Type</label>
                    <select id="type" name="type" class="form-control" onchange="this.form.submit();">
                        <option value="">-- All Types --</option>
                        <option value="mcq" <?= ($filters['type'] === 'mcq') ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                        <option value="complete" <?= ($filters['type'] === 'complete') ? 'selected' : '' ?>>Complete</option>
                        <option value="match" <?= ($filters['type'] === 'match') ? 'selected' : '' ?>>Match</option>
                        <option value="compare" <?= ($filters['type'] === 'compare') ? 'selected' : '' ?>>Compare</option>
                        <option value="essay" <?= ($filters['type'] === 'essay') ? 'selected' : '' ?>>Essay</option>
                        <option value="true_false" <?= ($filters['type'] === 'true_false') ? 'selected' : '' ?>>True / False</option>
                    </select>
                </div>

                <!-- Filter by Answer Status -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="answer_status">Answer Status</label>
                    <select id="answer_status" name="answer_status" class="form-control" onchange="this.form.submit();">
                        <option value="">-- All Statuses --</option>
                        <option value="available" <?= ($filters['answer_status'] === 'available') ? 'selected' : '' ?>>Answer Available</option>
                        <option value="unavailable" <?= ($filters['answer_status'] === 'unavailable') ? 'selected' : '' ?>>Answer Unavailable</option>
                    </select>
                </div>

                <!-- Sort by Frequency -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="sort_by">Sort By</label>
                    <select id="sort_by" name="sort_by" class="form-control" onchange="this.form.submit();">
                        <option value="newest" <?= ($filters['sort_by'] === 'newest') ? 'selected' : '' ?>>Newest Added</option>
                        <option value="frequency_desc" <?= ($filters['sort_by'] === 'frequency_desc') ? 'selected' : '' ?>>Frequency (High &rarr; Low)</option>
                        <option value="frequency_asc" <?= ($filters['sort_by'] === 'frequency_asc') ? 'selected' : '' ?>>Frequency (Low &rarr; High)</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 0;"><label class="form-label" for="exam_year">Exam Year</label><input id="exam_year" name="exam_year" type="number" min="1900" max="2200" class="form-control" value="<?= e((string)($filters['exam_year'] ?? '')) ?>"></div>
                <div class="form-group" style="margin-bottom: 0;"><label class="form-label" for="exam_term">Exam Term</label><select id="exam_term" name="exam_term" class="form-control"><option value="">-- Any Term --</option><option value="first" <?= ($filters['exam_term']??'')==='first'?'selected':'' ?>>First</option><option value="second" <?= ($filters['exam_term']??'')==='second'?'selected':'' ?>>Second</option></select></div>
                <fieldset class="form-group" style="margin:0;border:0;padding:0"><legend class="form-label">Exam Source</legend><label><input type="checkbox" name="source_names[]" value="final" <?= in_array('final',$filters['source_names']??[],true)?'checked':'' ?>> Final</label> <label><input type="checkbox" name="source_names[]" value="end_module" <?= in_array('end_module',$filters['source_names']??[],true)?'checked':'' ?>> End Module</label></fieldset>
            </div>

            <div class="question-filters-actions">
                <a href="<?= url('admin/questions.php') ?>" class="btn btn-secondary btn-sm"><i class="fa-solid fa-rotate-left" aria-hidden="true"></i><span>Reset Filters</span></a>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i><span>Apply Filters / Search</span></button>
            </div>
        </form>
    </div>

    <!-- Questions list -->
    <?php if (empty($questions)): ?>
        <div class="card question-empty-state">
            <div class="question-empty-icon">❓</div>
            <h3>No Questions Found</h3>
            <p>
                No questions match your current search queries or filter constraints.
            </p>
            <a href="<?= url('admin/question-form.php') ?>" class="btn btn-primary">
                Add a Question
            </a>
        </div>
    <?php else: ?>
        <div class="card admin-question-list-shell">
            <div class="admin-question-list-scroll" style="overflow-x: auto;">
                <table class="admin-question-list-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
                    <thead>
                        <tr style="background: var(--bg-page); border-bottom: 1px solid var(--border);">
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); width: 80px;">Type</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Question Text</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark);">Module &amp; Subject</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: center; width: 100px;">Freq</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: center; width: 120px;">Answer</th>
                            <th style="padding: 0.85rem 1.25rem; font-weight: 600; color: var(--dark); text-align: right; width: 180px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questions as $q): ?>
                            <tr class="admin-question-list-row" style="border-bottom: 1px solid var(--border);">
                                <td class="admin-question-list-cell admin-question-type-cell" data-label="Type" style="padding: 1rem 1.25rem; vertical-align: top;">
                                    <span class="badge" style="background-color: var(--primary-light); color: var(--primary); font-size: 0.7rem; font-weight: 700;">
                                        <?= strtoupper(e($q['type'])) ?>
                                    </span>
                                </td>
                                <td class="admin-question-list-cell admin-question-text-cell" data-label="Question" style="padding: 1rem 1.25rem; vertical-align: top;">
                                    <div class="question-list-text">
                                        <a href="<?= url('admin/question-view.php?id=' . (int)$q['id']) ?>" style="color: var(--dark);">
                                            <span class="question-list-preview"><?= e($q['question_text']) ?></span>
                                        </a>
                                        <a class="question-list-view-link" href="<?= url('admin/question-view.php?id=' . (int)$q['id']) ?>">
                                            <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i><span>View question</span>
                                        </a>
                                    </div>
                                    <?php if (!empty($q['source_name'])): ?>
                                        <div style="font-size: 0.775rem; color: var(--text-muted);">
                                            Source: <?= e($q['source_name']) ?> <?= !empty($q['exam_year']) ? '(' . e($q['exam_year']) . ')' : '' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-question-list-cell admin-question-context-cell" data-label="Module &amp; Subject" style="padding: 1rem 1.25rem; vertical-align: top; font-size: 0.85rem; color: var(--text);">
                                    <div style="font-weight: 600; color: var(--primary);"><?= e($q['module_name']) ?></div>
                                    <div style="color: var(--text-muted);"><?= e($q['subject_name']) ?></div>
                                </td>
                                <td class="admin-question-list-cell" data-label="Frequency" style="padding: 1rem 1.25rem; vertical-align: top; text-align: center; font-weight: 700; color: var(--dark);">
                                    <?= (int)$q['frequency'] ?>
                                </td>
                                <td class="admin-question-list-cell admin-question-status-cell" data-label="Answer" style="padding: 1rem 1.25rem; vertical-align: top; text-align: center;">
                                    <?php if ($q['answer_status'] === 'available'): ?>
                                        <span class="badge" style="background-color: var(--success-bg); color: var(--success); font-size: 0.725rem;">Available</span>
                                    <?php else: ?>
                                        <span class="badge" style="background-color: var(--warning-bg); color: var(--warning); font-size: 0.725rem;">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td class="admin-question-list-cell admin-question-actions-cell" data-label="Actions" style="padding: 1rem 1.25rem; vertical-align: top; text-align: right; white-space: nowrap;">
                                    <a href="<?= url('admin/question-view.php?id=' . (int)$q['id']) ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;"><i class="fa-solid fa-eye" aria-hidden="true"></i><span>View</span></a>
                                    <a href="<?= url('admin/question-form.php?id=' . (int)$q['id']) ?>" class="btn btn-secondary btn-sm" style="margin-right: 0.25rem;"><i class="fa-solid fa-pen" aria-hidden="true"></i><span>Edit</span></a>
                                    <a href="<?= url('admin/question-delete.php?id=' . (int)$q['id']) ?>" class="btn btn-sm" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border);"><i class="fa-solid fa-trash" aria-hidden="true"></i><span>Delete</span></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.875rem; color: var(--text-muted); flex-wrap: wrap; gap: 1rem;">
                <div>
                    Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalQuestions) ?> of <?= $totalQuestions ?> questions
                </div>
                <div style="display: flex; gap: 0.35rem;">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('admin/questions.php?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>" class="btn btn-secondary btn-sm">&larr; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= url('admin/questions.php?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>" class="btn btn-sm <?= ($i === $page) ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('admin/questions.php?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
