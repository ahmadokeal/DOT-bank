<div class="admin-question-view-container" style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="<?= url('admin/questions.php') ?>" style="font-size: 0.875rem; color: var(--text-muted); display: inline-flex; align-items: center; gap: 0.25rem; margin-bottom: 0.5rem;">
            &larr; Back to Question Bank
        </a>
        <h1>Question Details</h1>
        <p style="color: var(--text-muted);">Detailed metadata and answer definitions for Question ID #<?= (int)$question['id'] ?></p>
    </div>

    <div class="card">
        <div class="card-header" style="flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <span class="badge" style="background-color: var(--primary-light); color: var(--primary); font-weight: 700;">
                    <?= strtoupper(e($question['type'])) ?>
                </span>
                <span class="badge badge-student">
                    <?= e($question['module_name']) ?> &rarr; <?= e($question['subject_name']) ?>
                </span>
            </div>
            <div style="display: flex; gap: 0.35rem;">
                <a href="<?= url('admin/question-form.php?id=' . (int)$question['id']) ?>" class="btn btn-secondary btn-sm">Edit</a>
                <a href="<?= url('admin/question-delete.php?id=' . (int)$question['id']) ?>" class="btn btn-sm" style="background-color: var(--error-bg); color: var(--error); border-color: var(--error-border);">Delete</a>
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <p style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Question Text</p>
            <div style="font-size: 1.2rem; font-weight: 600; color: var(--dark); line-height: 1.5; background: var(--bg-page); padding: 1.25rem; border-radius: var(--radius-sm); border: 1px solid var(--border); white-space: pre-wrap;"><?= e($question['question_text']) ?></div>
        </div>

        <!-- Answer / Structure Details Section -->
        <div style="margin-bottom: 2rem;">
            <p style="font-size: 0.85rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem;">Answer Configuration</p>
            
            <div style="border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden;">
                <div style="padding: 0.75rem 1rem; background: var(--bg-page); border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.9rem; font-weight: 600; color: var(--dark);">
                        Status: 
                        <?php if ($question['answer_status'] === 'available'): ?>
                            <span style="color: var(--success);">Available</span>
                        <?php else: ?>
                            <span style="color: var(--warning);">Unavailable</span>
                        <?php endif; ?>
                    </span>
                    <span style="font-size: 0.8rem; color: var(--text-muted);">Origin: <?= e($question['answer_origin']) ?></span>
                </div>

                <div style="padding: 1.25rem;">
                    <?php if ($question['answer_status'] === 'unavailable'): ?>
                        <p style="color: var(--warning); font-style: italic; margin: 0;">Answer unavailable for this question. It must be manually edited or updated via import.</p>
                    <?php else: ?>
                        <?php 
                        $decoded = $question['answer_data_decoded'];
                        $type = $question['type'];
                        ?>
                        
                        <?php if ($type === 'mcq'): ?>
                            <p style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Options:</p>
                            <ul style="list-style: none; margin-bottom: 1rem; padding-left: 0;">
                                <?php foreach ($decoded['options'] ?? [] as $idx => $opt): 
                                    $isCorrect = ($opt === $decoded['correct_answer']);
                                ?>
                                    <li style="padding: 0.5rem 0.75rem; margin-bottom: 0.35rem; border-radius: var(--radius-sm); border: 1px solid <?= $isCorrect ? 'var(--success-border)' : 'var(--border)' ?>; background-color: <?= $isCorrect ? 'var(--success-bg)' : '#ffffff' ?>; display: flex; align-items: center; justify-content: space-between;">
                                        <span>
                                            <strong><?= chr(65 + $idx) ?>.</strong> <?= e($opt) ?>
                                        </span>
                                        <?php if ($isCorrect): ?>
                                            <span class="badge" style="background-color: var(--success); color: #ffffff; font-size: 0.65rem;">Correct Option</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        <?php elseif ($type === 'match'): ?>
                            <p style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Items Matching:</p>
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 0.75rem; align-items: center; background: #ffffff; padding: 1rem; border-radius: var(--radius-sm);">
                                <?php foreach ($decoded['matches'] ?? [] as $leftItem => $rightItem): ?>
                                    <div style="padding: 0.5rem; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--bg-page); font-weight: 600;"><?= e($leftItem) ?></div>
                                    <div style="color: var(--text-muted); font-weight: bold;">&rarr;</div>
                                    <div style="padding: 0.5rem; border: 1px solid var(--success-border); border-radius: var(--radius-sm); background: var(--success-bg); color: #14532d; font-weight: 600;"><?= e($rightItem) ?></div>
                                <?php endforeach; ?>
                            </div>

                        <?php else: // complete, compare, essay ?>
                            <p style="font-weight: 600; margin-bottom: 0.5rem; font-size: 0.9rem;">Model/Correct Answer:</p>
                            <div style="padding: 1rem; background: var(--success-bg); border: 1px solid var(--success-border); border-radius: var(--radius-sm); color: #14532d; font-size: 0.95rem; white-space: pre-wrap; font-weight: 600;"><?= e($decoded['answer'] ?? '') ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Question metadata -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; border-top: 1px solid var(--border); padding-top: 1.5rem; font-size: 0.875rem; color: var(--text-muted);">
            <div>
                <strong>Frequency / Appeared Count:</strong>
                <div style="font-size: 1.1rem; font-weight: 700; color: var(--dark); margin-top: 0.15rem;"><?= (int)$question['frequency'] ?> time(s) in past exams</div>
            </div>
            <div>
                <strong>Exam Appearances (<?= count($question['appearances'] ?? []) ?>):</strong>
                <?php if (!empty($question['appearances'])): ?><ul style="margin:.35rem 0 0;padding-left:1.1rem"><?php foreach ($question['appearances'] as $appearance): ?><li><?= e(ucwords(str_replace('_',' ',$appearance['source_name']))) ?> — <?= e(ucwords($appearance['exam_term'])) ?> Term <?= (int)$appearance['exam_year'] ?></li><?php endforeach; ?></ul><?php else: ?><div style="margin-top:.15rem"><em>No exam appearances recorded.</em></div><?php endif; ?>
            </div>
            <div>
                <strong>Timestamps:</strong>
                <div style="margin-top: 0.15rem;">Created: <?= e($question['created_at']) ?></div>
                <div>Updated: <?= e($question['updated_at']) ?></div>
            </div>
        </div>
    </div>
</div>
