<div class="quiz-wrapper quiz-taking-page">
    <!-- Modern quiz header — title, meta, progress -->
    <header class="card quiz-taking-header">
        <div class="quiz-taking-header-main">
            <div class="quiz-taking-heading">
                <span class="quiz-taking-eyebrow"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Practice Quiz</span>
                <h1>Quiz Session</h1>
                <p>Take your time and choose the best answer for each question. You can change answers before submitting.</p>
            </div>
            <div class="quiz-taking-meta">
                <div class="quiz-taking-stat">
                    <span class="quiz-taking-stat-value"><?=count($quiz['questions'])?></span>
                    <span class="quiz-taking-stat-label">Questions</span>
                </div>
                <div class="quiz-taking-stat-divider" aria-hidden="true"></div>
                <div class="quiz-taking-stat">
                    <span class="quiz-taking-stat-value"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                    <span class="quiz-taking-stat-label">Practice mode</span>
                </div>
            </div>
        </div>
        <div class="quiz-taking-progress" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100">
            <div class="quiz-taking-progress-track"><div class="quiz-taking-progress-fill" style="width: 100%"></div></div>
            <span class="quiz-taking-progress-text"><?=count($quiz['questions'])?> questions ready · Answer at your own pace</span>
        </div>
    </header>

    <form method="post" action="<?=url('student/quiz-submit.php')?>" class="quiz-taking-form">
        <?=CSRF::field()?><input type="hidden" name="quiz_id" value="<?= (int)$quiz['id']?>">
        <div class="quiz-question-stack">
<?php foreach($quiz['questions'] as $i=>$q):$data=json_decode($q['answer_data']??'',true)?:[];?>
            <article class="card quiz-question-card quiz-question-card--<?=e($q['type'])?>">
                <header class="quiz-question-header">
                    <div class="quiz-question-index">
                        <span class="quiz-index-badge">Q<?= $i+1 ?></span>
                        <span class="badge quiz-type-badge"><?=strtoupper(e($q['type']))?></span>
                    </div>
                    <span class="quiz-question-count"><?= $i+1 ?> / <?=count($quiz['questions'])?></span>
                </header>
                <div class="quiz-question-body">
                    <h2 class="quiz-question-text"><?=e($q['question_text'])?></h2>
                    <div class="quiz-answer-area">
<?php if($q['type']==='true_false'):?>
                        <div class="quiz-option-group quiz-option-group--binary">
                            <label class="quiz-option-card"><input type="radio" name="answers[<?= (int)$q['quiz_question_id']?>]" value="true"> <span class="quiz-option-label-text"><i class="fa-solid fa-circle-check quiz-option-icon" aria-hidden="true"></i> True</span></label>
                            <label class="quiz-option-card"><input type="radio" name="answers[<?= (int)$q['quiz_question_id']?>]" value="false"> <span class="quiz-option-label-text"><i class="fa-solid fa-circle-xmark quiz-option-icon" aria-hidden="true"></i> False</span></label>
                        </div>
                        <p class="quiz-answer-hint"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Choose one — auto-graded</p>
<?php elseif($q['type']==='mcq'):?>
                        <div class="quiz-option-group">
<?php foreach(($data['options']??[]) as $opt):?><label class="quiz-option-card"><input type="radio" name="answers[<?= (int)$q['quiz_question_id']?>]" value="<?=e($opt)?>"> <span class="quiz-option-label-text"><?=e($opt)?></span></label><?php endforeach;?>
                        </div>
                        <p class="quiz-answer-hint"><i class="fa-solid fa-list-ol" aria-hidden="true"></i> Select one option — auto-graded</p>
<?php elseif($q['type']==='match'): $leftDisplay = $q['display_left'] ?? $data['left_items'] ?? []; $rightDisplay = $q['display_right'] ?? $data['right_items'] ?? [];?>
                        <div class="quiz-match-group">
<?php foreach($leftDisplay as $left):?><div class="quiz-match-row"><label class="quiz-match-card"><span class="quiz-match-left"><span class="quiz-match-label"><?=e($left)?></span></span><span class="quiz-match-arrow" aria-hidden="true"><i class="fa-solid fa-arrow-right"></i></span><span class="quiz-match-select-wrap"><select class="form-control" name="answers[<?= (int)$q['quiz_question_id']?>][<?=e($left)?>]"><option value="">Select an answer</option><?php foreach($rightDisplay as $right):?><option value="<?=e($right)?>"><?=e($right)?></option><?php endforeach;?></select></span></label></div><?php endforeach;?>
                        </div>
                        <p class="quiz-answer-hint"><i class="fa-solid fa-shuffle" aria-hidden="true"></i> Match each left item — auto-graded</p>
<?php else:?>
                        <div class="quiz-textarea-group">
                            <label class="form-label quiz-textarea-label" for="answer-<?= (int)$q['quiz_question_id']?>">Your answer <span class="quiz-self-graded-badge">Self-graded</span></label>
                            <textarea id="answer-<?= (int)$q['quiz_question_id']?>" class="form-control quiz-textarea" rows="4" name="answers[<?= (int)$q['quiz_question_id']?>]" placeholder="Type your answer here..."></textarea>
                            <p class="quiz-answer-hint"><i class="fa-solid fa-pen" aria-hidden="true"></i> You will self-review this after submission</p>
                        </div>
<?php endif;?>
                    </div>
                </div>
            </article>
<?php endforeach;?>
        </div>

        <div class="card quiz-submit-card">
            <div class="quiz-submit-card-main">
                <div class="quiz-submit-copy">
                    <h3><i class="fa-solid fa-flag-checkered" aria-hidden="true"></i> Ready to submit?</h3>
                    <p>Review your answers. You can still change them until you submit. MCQ, Match and True/False are auto-graded.</p>
                </div>
                <div class="quiz-submit-actions">
                    <button class="btn btn-primary btn-lg quiz-submit-btn" type="submit"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Quiz</button>
                    <span class="quiz-submit-hint">All questions on one page · No time limit</span>
                </div>
            </div>
        </div>
    </form>

    <form method="post" action="<?=url('student/quiz-discard.php')?>" class="quiz-discard-form" onsubmit="return confirm('Discard this quiz? All current answers will be lost.');">
        <?=CSRF::field()?><input type="hidden" name="quiz_id" value="<?= (int)$quiz['id']?>">
        <div class="quiz-discard-card">
            <p class="quiz-discard-copy"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Discard this quiz and start over?</p>
            <button class="btn btn-secondary quiz-discard-btn" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i> Discard Quiz</button>
        </div>
    </form>
</div>
