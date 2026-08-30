<?php $hasSubject = is_array($subject ?? null) && isset($subject['id'], $subject['name'], $subject['module_id'], $subject['module_name']); $hasModule=(int)($filters['module_id']??0)>0; ?>
<div class="student-questions-container">
    <div style="margin-bottom: 1.5rem;">
        <?php if ($hasSubject): ?>
            <a href="<?= url('student/module-view.php?id=' . (int)$subject['module_id']) ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
                &larr; Back to <?= e($subject['module_name']) ?>
            </a>
        <?php endif; ?>
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1><?= $hasSubject ? e($subject['name']) : 'Question Bank' ?></h1>
                <p style="color: var(--text-muted);"><?= $hasSubject ? 'Explore and study previous exam questions for this subject.' : ($hasModule ? 'Browse questions within this module.' : 'Select a module to browse its questions.') ?></p>
            </div>
            <span class="badge badge-student" style="font-size: 0.85rem; padding: 0.35rem 0.75rem;">
                Question Bank
            </span>
        </div>
    </div>

    <!-- Student Filters Card -->
    <div class="card" style="padding: 1rem; margin-bottom: 1.5rem; background: var(--bg-card);">
        <form method="GET" action="<?= url('student/questions.php') ?>" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
            <?php if ($hasSubject): ?><input type="hidden" name="subject_id" value="<?= (int)$subject['id'] ?>"><?php else: ?><div class="form-group" style="margin-bottom:0"><label class="form-label" for="module_id">Module</label><select id="module_id" name="module_id" class="form-control" onchange="this.form.submit()"><option value="">-- Select Module --</option><?php foreach($modules as $m):?><option value="<?= (int)$m['id']?>" <?= $hasModule&&(int)$filters['module_id']===(int)$m['id']?'selected':''?>><?=e($m['name'])?></option><?php endforeach;?></select></div><?php endif; ?>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="search" style="font-size: 0.8rem;">Search Question Text</label>
                <input type="text" id="search" name="search" class="form-control" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search keyword...">
            </div>

            <div class="form-group" style="margin-bottom: 0;"><label class="form-label" for="answer_status">Answer Status</label><select id="answer_status" name="answer_status" class="form-control"><option value="">-- All Statuses --</option><option value="available" <?= ($filters['answer_status']??'')==='available'?'selected':'' ?>>Available</option><option value="unavailable" <?= ($filters['answer_status']??'')==='unavailable'?'selected':'' ?>>Unavailable</option></select></div>
            <div class="form-group" style="margin-bottom: 0;"><label class="form-label" for="exam_year">Exam Year</label><input id="exam_year" name="exam_year" type="number" min="1900" max="2200" class="form-control" value="<?= e((string)($filters['exam_year']??'')) ?>"></div>
            <div class="form-group" style="margin-bottom: 0;"><label class="form-label" for="exam_term">Exam Term</label><select id="exam_term" name="exam_term" class="form-control"><option value="">-- Any Term --</option><option value="first" <?= ($filters['exam_term']??'')==='first'?'selected':'' ?>>First</option><option value="second" <?= ($filters['exam_term']??'')==='second'?'selected':'' ?>>Second</option></select></div>
            <fieldset class="form-group" style="margin:0;border:0;padding:0"><legend class="form-label">Exam Source</legend><label><input type="checkbox" name="source_names[]" value="final" <?= in_array('final',$filters['source_names']??[],true)?'checked':'' ?>> Final</label> <label><input type="checkbox" name="source_names[]" value="end_module" <?= in_array('end_module',$filters['source_names']??[],true)?'checked':'' ?>> End Module</label></fieldset>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="type" style="font-size: 0.8rem;">Question Type</label>
                <select id="type" name="type" class="form-control" onchange="this.form.submit()">
                    <option value="">-- All Types --</option>
                    <option value="mcq" <?= ($filters['type'] === 'mcq') ? 'selected' : '' ?>>Multiple Choice (MCQ)</option>
                    <option value="complete" <?= ($filters['type'] === 'complete') ? 'selected' : '' ?>>Complete</option>
                    <option value="match" <?= ($filters['type'] === 'match') ? 'selected' : '' ?>>Matching</option>
                    <option value="compare" <?= ($filters['type'] === 'compare') ? 'selected' : '' ?>>Compare</option>
                    <option value="essay" <?= ($filters['type'] === 'essay') ? 'selected' : '' ?>>Essay</option>
                    <option value="true_false" <?= ($filters['type'] === 'true_false') ? 'selected' : '' ?>>True / False</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="sort_by" style="font-size: 0.8rem;">Sort By</label>
                <select id="sort_by" name="sort_by" class="form-control" onchange="this.form.submit()">
                    <option value="newest" <?= ($filters['sort_by'] === 'newest') ? 'selected' : '' ?>>Newest</option>
                    <option value="frequency_desc" <?= ($filters['sort_by'] === 'frequency_desc') ? 'selected' : '' ?>>Frequency (High &rarr; Low)</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary btn-block">Search / Filter</button>
                <a href="<?= url('student/questions.php' . ($hasSubject ? '?subject_id=' . (int)$subject['id'] : '')) ?>" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Questions Display -->
    <?php if (empty($questions)): ?>
        <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem; color: var(--text-muted);">📝</div>
            <h3 style="margin-bottom: 0.5rem;">No Questions Available</h3>
            <p style="color: var(--text-muted); max-width: 420px; margin: 0 auto;">
                There are no questions listed matching your current criteria for this subject.
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($questions as $idx => $q): 
                $decoded = json_decode($q['answer_data'] ?? '', true);
                $type = $q['type'];
                $qNum = $offset + $idx + 1;
            ?>
                <div class="card" style="margin-bottom: 0; position: relative;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid var(--border); padding-bottom: 0.5rem;">
                        <span style="font-weight: bold; color: var(--primary);">Question <?= $qNum ?></span>
                        <div style="display: flex; gap: 0.35rem; align-items: center;">
                            <span class="badge" style="background-color: var(--primary-light); color: var(--primary); font-size: 0.65rem; font-weight: 700;">
                                <?= strtoupper(e($type)) ?>
                            </span>
                            <?php if ((int)$q['frequency'] > 1): ?>
                                <span class="badge" style="background-color: #fef3c7; color: #92400e; font-size: 0.65rem; font-weight: 700;">
                                    Freq: <?= (int)$q['frequency'] ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Question prompt -->
                    <div style="font-size: 1.05rem; font-weight: 600; color: var(--dark); margin-bottom: 1.25rem; white-space: pre-wrap; line-height: 1.5;"><?= e($q['question_text']) ?></div>

                    <!-- Type Specific Renderings -->
                    <?php if ($type === 'true_false'): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem; max-width: 600px;"><div style="padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff;">True</div><div style="padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff;">False</div></div>
                    <?php elseif ($type === 'mcq' && !empty($decoded['options'])): ?>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1.25rem; max-width: 600px;">
                            <?php foreach ($decoded['options'] as $oIdx => $opt): ?>
                                <div style="padding: 0.6rem 0.85rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: #ffffff;">
                                    <strong><?= chr(65 + $oIdx) ?>.</strong> <?= e($opt) ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif ($type === 'match' && !empty($decoded['left_items']) && !empty($decoded['right_items'])): ?>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.25rem; max-width: 700px;">
                            <div>
                                <strong style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Left Column</strong>
                                <ul style="list-style: none; padding-left: 0;">
                                    <?php foreach ($decoded['left_items'] as $item): ?>
                                        <li style="padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 0.35rem; background: #ffffff; font-weight: 600;"><?= e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; text-transform: uppercase;">Right Column</strong>
                                <ul style="list-style: none; padding-left: 0;">
                                    <?php foreach ($decoded['right_items'] as $item): ?>
                                        <li style="padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: var(--radius-sm); margin-bottom: 0.35rem; background: #ffffff; font-weight: 600;"><?= e($item) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                        <strong>Exam appearances:</strong>
                        <?php if (!empty($q['appearances'])): ?><ul style="margin:.25rem 0 0;padding-left:1.1rem"><?php foreach ($q['appearances'] as $appearance): ?><li><?= e(ucwords(str_replace('_',' ',$appearance['source_name']))) ?> — <?= e(ucwords($appearance['exam_term'] ?? '')) ?> Term <?= (int)($appearance['exam_year'] ?? 0) ?></li><?php endforeach; ?></ul><?php else: ?><span>No exam appearances recorded.</span><?php endif; ?>
                    </div>

                    <!-- Answer reveal drawer -->
                    <div style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem;">
                        <?php if ($q['answer_status'] === 'unavailable'): ?>
                            <div style="font-size: 0.9rem; color: var(--warning); font-style: italic;">
                                ⚠️ Answer unavailable for this question.
                            </div>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-sm toggle-answer-btn" onclick="toggleAnswer(this)" style="font-weight: 600;">
                                Show Answer
                            </button>

                            <div class="answer-drawer" style="display: none; margin-top: 1rem; padding: 1rem; border-radius: var(--radius-sm); background-color: var(--success-bg); border: 1px solid var(--success-border); color: #14532d;">
                                <strong style="display: block; font-size: 0.8rem; text-transform: uppercase; color: var(--success); margin-bottom: 0.35rem;">Correct Answer Definition:</strong>
                                
                                <?php if ($type === 'true_false'): ?>
                                    <div style="font-weight: 700; font-size: 1.05rem;">Correct Answer: <?= e(ucfirst((string)$decoded['answer'])) ?></div>
                                <?php elseif ($type === 'mcq'): ?>
                                    <div style="font-weight: 700; font-size: 1.05rem;">
                                        Correct Option: <?= e($decoded['correct_answer']) ?>
                                    </div>
                                <?php elseif ($type === 'match'): ?>
                                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                        <?php foreach ($decoded['matches'] as $leftItem => $rightItem): ?>
                                            <div>
                                                <strong><?= e($leftItem) ?></strong> &rarr; <span style="font-weight: 700;"><?= e($rightItem) ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="white-space: pre-wrap; font-weight: 600; line-height: 1.4;"><?= e($decoded['answer']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.875rem; color: var(--text-muted); flex-wrap: wrap; gap: 1rem; margin-top: 1.5rem;">
                <div>
                    Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalQuestions) ?> of <?= $totalQuestions ?> questions
                </div>
                <div style="display: flex; gap: 0.35rem;">
                    <?php if ($page > 1): ?>
                        <a href="<?= url('student/questions.php?' . http_build_query(array_merge($filters, ['page' => $page - 1]))) ?>" class="btn btn-secondary btn-sm">&larr; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="<?= url('student/questions.php?' . http_build_query(array_merge($filters, ['page' => $i]))) ?>" class="btn btn-sm <?= ($i === $page) ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= url('student/questions.php?' . http_build_query(array_merge($filters, ['page' => $page + 1]))) ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleAnswer(btn) {
    const drawer = btn.nextElementSibling;
    if (drawer.style.display === 'none') {
        drawer.style.display = 'block';
        btn.textContent = 'Hide Answer';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-primary');
    } else {
        drawer.style.display = 'none';
        btn.textContent = 'Show Answer';
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    }
}
</script>
