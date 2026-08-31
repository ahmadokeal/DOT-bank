<div class="quiz-result-page">
    <div class="page-header">
        <div class="page-header-info"><h1>Quiz Result</h1><p><?=e($result['module_name'])?> · <?= (int)$result['total_questions']?> question(s)</p></div>
        <a class="btn btn-secondary" href="<?=url('student/quiz-builder.php')?>">Create Another Quiz</a>
    </div>
    <div class="card result-dashboard">
        <div class="result-score-highlight"><span class="result-score-value"><?= $result['score']===null?'—':e((string)$result['score']).'%'?></span><span class="result-score-label">Objective score · MCQ, Match, and True / False</span></div>
        <div class="result-summary">
            <div class="result-stat"><span class="result-stat-value"><?= (int)$result['auto_graded']?></span><span class="result-stat-label">Total scoring units</span></div>
            <div class="result-stat result-stat--success"><span class="result-stat-value"><?= (int)$result['correct']?></span><span class="result-stat-label">Correct</span></div>
            <div class="result-stat result-stat--error"><span class="result-stat-value"><?= (int)$result['incorrect']?></span><span class="result-stat-label">Incorrect</span></div>
            <div class="result-stat result-stat--warning"><span class="result-stat-value"><?= (int)$result['unanswered']?></span><span class="result-stat-label">Unanswered</span></div>
            <div class="result-stat result-stat--info"><span class="result-stat-value"><?= (int)$result['self_graded']?></span><span class="result-stat-label">Self-graded questions</span></div>
        </div>
        <p class="result-note"><?= (int)$result['total_questions']?> question(s) total · <?= (int)$result['auto_graded']?> auto-graded scoring units (MCQ 1, True/False 1, Match pairs). Self-graded not counted in score.</p>
    </div>
    <section class="quiz-review-section">
        <header class="quiz-review-section-header">
            <h2><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Question Review <span class="quiz-review-count"><?= (int)$result['total_questions']?> questions</span></h2>
            <p>Every question revisited — auto-graded and self-graded shown clearly.</p>
        </header>
        <div class="quiz-review-stack">
<?php foreach($result['questions'] as $i=>$q):
    $data=$q['answer_data_decoded'];
    $student=$q['student_answer']??'';
    $type=$q['type'];
    $status='self';
    $statusLabel='Self-graded';
    $statusIcon='fa-pen';
    if(in_array($type,['mcq','true_false'],true)){
        if($student==='') { $status='unanswered'; $statusLabel='Unanswered'; $statusIcon='fa-circle-question'; }
        elseif((int)$q['is_correct']===1){ $status='correct'; $statusLabel='Correct'; $statusIcon='fa-circle-check'; }
        else { $status='incorrect'; $statusLabel='Incorrect'; $statusIcon='fa-circle-xmark'; }
    } elseif($type==='match') {
        $matchPairs=$q['match_pair_results']??[];
        $matchTotal=(int)($q['match_total_pairs']??count($data['left_items']??[]));
        $matchCorrect=(int)($q['match_correct_pairs']??0);
        $matchAnswered=count(array_filter($matchPairs,fn($pair)=>($pair['student_answer']??null)!==null));
        if($matchAnswered===0){ $status='unanswered'; $statusLabel='Unanswered'; $statusIcon='fa-circle-question'; }
        elseif($matchTotal>0&&$matchCorrect===$matchTotal){ $status='correct'; $statusLabel='Correct'; $statusIcon='fa-circle-check'; }
        else { $status='incorrect'; $statusLabel='Partial credit'; $statusIcon='fa-circle-half-stroke'; }
    }
?>
            <article class="card quiz-review-card quiz-review-card--<?=e($status)?>">
                <header class="quiz-review-card-header">
                    <div class="quiz-review-card-index">
                        <span class="quiz-index-badge">Q<?= $i+1 ?></span>
                        <span class="badge quiz-type-badge"><?=strtoupper(e($type))?></span>
                    </div>
                    <span class="quiz-review-status quiz-review-status--<?=e($status)?>"><i class="fa-solid <?=e($statusIcon)?>" aria-hidden="true"></i> <?=e($statusLabel)?></span>
                </header>
                <h3 class="quiz-review-question-text"><?=e($q['question_text'])?></h3>
<?php if($q['type']==='true_false'): $correctLabel=ucfirst((string)($data['answer']??'Unavailable')); $studentLabel=$student!==''?ucfirst($student):'Unanswered'; // type']==='true_false' ?>
                <div class="quiz-review-answer-grid quiz-review-answer-grid--binary">
                    <div class="quiz-review-answer-box <?= $status==='correct'?'quiz-review-answer-box--correct':($status==='incorrect'?'quiz-review-answer-box--incorrect':'') ?>">
                        <span class="quiz-review-answer-label">Your answer</span>
                        <strong class="quiz-review-answer-value"><?=e($studentLabel)?></strong>
                    </div>
                    <div class="quiz-review-answer-box quiz-review-answer-box--correct">
                        <span class="quiz-review-answer-label">Correct answer</span>
                        <strong class="quiz-review-answer-value"><?=e($correctLabel)?></strong>
                    </div>
                </div>
<?php elseif($type==='mcq'): $correctAns=(string)($data['correct_answer']??'Unavailable'); $studentAns=$student!==''?$student:'Unanswered';?>
                <div class="quiz-review-answer-grid quiz-review-answer-grid--binary">
                    <div class="quiz-review-answer-box <?= $status==='correct'?'quiz-review-answer-box--correct':($status==='incorrect'?'quiz-review-answer-box--incorrect':($status==='unanswered'?'quiz-review-answer-box--unanswered':'')) ?>">
                        <span class="quiz-review-answer-label">Your answer</span>
                        <strong class="quiz-review-answer-value"><?=e($studentAns)?></strong>
                    </div>
                    <div class="quiz-review-answer-box quiz-review-answer-box--correct">
                        <span class="quiz-review-answer-label">Correct answer</span>
                        <strong class="quiz-review-answer-value"><?=e($correctAns)?></strong>
                    </div>
                </div>
<?php elseif($type==='match'): $matchPairs=$q['match_pair_results']??[]; $matchTotal=(int)($q['match_total_pairs']??count($data['left_items']??[])); $matchCorrect=(int)($q['match_correct_pairs']??0);?>
                <div class="quiz-review-match">
                    <div class="quiz-review-match-column">
                        <h4 class="quiz-review-match-title">Pair score</h4>
                        <div class="quiz-review-match-score"><?= $matchCorrect ?> / <?= $matchTotal ?> correct</div>
                    </div>
                    <div class="quiz-review-match-column">
                        <h4 class="quiz-review-match-title">Pair review</h4>
<?php foreach($matchPairs as $pair): $pairCorrect=(int)($pair['is_correct']??0)===1; $studentPair=$pair['student_answer']??null; $correctPair=$pair['correct_answer']??null;?>
                        <div class="quiz-review-match-item <?= $pairCorrect?'quiz-review-match-item--correct':'quiz-review-match-item--incorrect' ?>">
                            <span class="quiz-review-match-left"><?=e((string)($pair['left']??''))?></span><span class="quiz-review-match-arrow"><i class="fa-solid <?= $pairCorrect?'fa-check':'fa-xmark' ?>" aria-hidden="true"></i></span><span class="quiz-review-match-right"><?=e($studentPair===null?'Unanswered':(string)$studentPair)?></span>
                            <?php if(!$pairCorrect && $correctPair!==null): ?><span class="quiz-review-match-correction">Correct: <?=e((string)$correctPair)?></span><?php endif; ?>
                        </div>
<?php endforeach;?>
                    </div>
                </div>
<?php else: $refAnswer=$q['answer_status']==='available'?(string)($data['answer']??'Unavailable'):'Unavailable'; $studentAns=$student!==''?$student:'Unanswered';?>
                <div class="quiz-review-answer-grid">
                    <div class="quiz-review-answer-box">
                        <span class="quiz-review-answer-label">Your answer</span>
                        <div class="quiz-review-answer-value quiz-review-answer-value--prewrap"><?=e($studentAns)?></div>
                    </div>
                    <div class="quiz-review-answer-box quiz-review-answer-box--reference">
                        <span class="quiz-review-answer-label">Reference answer</span>
                        <div class="quiz-review-answer-value quiz-review-answer-value--prewrap"><?=e($refAnswer)?></div>
                    </div>
                </div>
<?php endif;?>
            </article>
<?php endforeach;?>
        </div>
    </section>
</div>
