<div class="quiz-result-page">
    <div class="page-header">
        <div class="page-header-info"><h1>Quiz Result</h1><p><?=e($result['module_name'])?> · <?= (int)$result['total_questions']?> question(s)</p></div>
        <a class="btn btn-secondary" href="<?=url('student/quiz-builder.php')?>">Create Another Quiz</a>
    </div>
    <div class="card result-dashboard">
        <div class="result-score-highlight"><span class="result-score-value"><?= $result['score']===null?'—':e((string)$result['score']).'%'?></span><span class="result-score-label">Objective score · MCQ, Match, and True / False</span></div>
        <div class="result-summary">
            <div class="result-stat"><span class="result-stat-value"><?= (int)$result['total_questions']?></span><span class="result-stat-label">Total</span></div>
            <div class="result-stat result-stat--success"><span class="result-stat-value"><?= (int)$result['correct']?></span><span class="result-stat-label">Correct</span></div>
            <div class="result-stat result-stat--error"><span class="result-stat-value"><?= (int)$result['incorrect']?></span><span class="result-stat-label">Incorrect</span></div>
            <div class="result-stat result-stat--warning"><span class="result-stat-value"><?= (int)$result['unanswered']?></span><span class="result-stat-label">Unanswered</span></div>
            <div class="result-stat result-stat--info"><span class="result-stat-value"><?= (int)$result['self_graded']?></span><span class="result-stat-label">Self-graded</span></div>
        </div>
        <p class="result-note">Auto-graded: <?= (int)$result['auto_graded']?>. Self-graded questions are not counted as incorrect.</p>
    </div>
    <h2 class="section-heading">Question Review</h2>
<?php foreach($result['questions'] as $i=>$q):$data=$q['answer_data_decoded'];$student=$q['student_answer']??'';?><div class="card result-question-card"><div class="result-question-heading"><span class="badge"><?=strtoupper(e($q['type']))?></span><h3>Question <?= $i+1?></h3></div><p class="result-question-text"><?=e($q['question_text'])?></p>
<?php if($q['type']==='true_false'):?><p>Your answer: <?=e($student!==''?ucfirst($student):'Unanswered')?></p><p>Correct answer: <?=e(ucfirst((string)($data['answer']??'Unavailable')))?></p><p>Status: <strong><?= $student===''?'Unanswered':((int)$q['is_correct']===1?'Correct':'Incorrect')?></strong></p>
<?php elseif($q['type']==='mcq'):?><p>Your answer: <?=e($student!==''?$student:'Unanswered')?></p><p>Correct answer: <?=e((string)($data['correct_answer']??'Unavailable'))?></p><p>Status: <strong><?= $student===''?'Unanswered':((int)$q['is_correct']===1?'Correct':'Incorrect')?></strong></p>
<?php elseif($q['type']==='match'):$submitted=json_decode($student,true)?:[];?><p>Your answer:</p><ul><?php if($submitted):foreach($submitted as $left=>$right):?><li><?=e((string)$left)?> → <?=e((string)$right)?></li><?php endforeach;else:?><li>Unanswered</li><?php endif;?></ul><p>Correct mapping:</p><ul><?php if(is_array($data['matches']??null)):foreach($data['matches'] as $left=>$right):?><li><?=e((string)$left)?> → <?=e((string)$right)?></li><?php endforeach;else:?><li>Unavailable</li><?php endif;?></ul><p>Status: <strong><?= $student===''||$submitted===[]?'Unanswered':((int)$q['is_correct']===1?'Correct':'Incorrect')?></strong></p>
<?php else:?><p>Your answer:</p><p class="result-answer-text"><?=e($student!==''?$student:'Unanswered')?></p><p>Reference answer: <?= $q['answer_status']==='available'?e((string)($data['answer']??'Unavailable')):'Unavailable'?></p><p>Status: <strong>Self-graded</strong></p><?php endif;?></div><?php endforeach;?></div>
