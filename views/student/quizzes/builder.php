<div class="quiz-builder-page">
    <div class="page-header">
        <div class="page-header-info">
            <h1>Create Quiz</h1>
            <p>Build a quiz from existing questions. Percentages use deterministic largest-remainder rounding.</p>
        </div>
        <span class="badge badge-student">Practice mode</span>
    </div>
<?php if($errors):?><div class="alert alert-error quiz-builder-errors"><?php foreach($errors as $e):?><div><?=e($e)?></div><?php endforeach;?></div><?php endif;?>
<?php if($plan):?>
<div class="card quiz-confirm-card">
    <div class="quiz-confirm-header">
        <span class="quiz-confirm-eyebrow"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> <?= $plan['exact'] ? 'Ready to start' : 'Closest match found' ?></span>
        <h2><?= $plan['exact']?'Exact quiz available':'Closest possible quiz' ?></h2>
        <p class="quiz-confirm-subtitle">We built the best match for your settings — review the summary and confirm to start.</p>
    </div>
    <div class="quiz-confirm-stats">
        <div class="quiz-confirm-stat">
            <span class="quiz-confirm-stat-value"><?= (int)$plan['requested_total'] ?></span>
            <span class="quiz-confirm-stat-label">Requested</span>
        </div>
        <div class="quiz-confirm-stat quiz-confirm-stat--proposed">
            <span class="quiz-confirm-stat-value"><?= (int)$plan['generated_total'] ?></span>
            <span class="quiz-confirm-stat-label">Proposed</span>
        </div>
        <div class="quiz-confirm-stat quiz-confirm-stat--status">
            <span class="badge <?= $plan['exact']?'badge-available':'badge-unavailable' ?>"><i class="fa-solid <?= $plan['exact']?'fa-circle-check':'fa-triangle-exclamation' ?>" aria-hidden="true"></i> <?= $plan['exact']?'Exact':'Adjusted' ?></span>
        </div>
    </div>
<?php if($plan['reasons']):?><div class="alert alert-warning quiz-confirm-reasons"><strong><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Heads up</strong><ul><?php foreach($plan['reasons'] as $reason):?><li><?=e($reason)?></li><?php endforeach;?></ul></div><?php endif;?>
    <div class="quiz-confirm-grid">
        <section class="quiz-confirm-section">
            <h3><i class="fa-solid fa-shapes" aria-hidden="true"></i> Question types</h3>
            <ul class="quiz-confirm-list">
<?php foreach($plan['actual_types'] as $type=>$n):?><li class="quiz-confirm-item"><span class="quiz-confirm-item-label"><?=strtoupper(e($type))?></span><span class="quiz-confirm-item-count"><?= (int)$n ?> <span class="quiz-confirm-item-requested">/ <?= (int)($plan['requested_types'][$type]??0) ?> requested</span></span></li><?php endforeach;?>
            </ul>
        </section>
        <section class="quiz-confirm-section">
            <h3><i class="fa-solid fa-book-open" aria-hidden="true"></i> Subjects</h3>
            <ul class="quiz-confirm-list">
<?php foreach($plan['actual_subjects'] as $id=>$n):?><li class="quiz-confirm-item"><span class="quiz-confirm-item-label"><?=e($plan['subject_names'][$id]??'Selected subject')?></span><span class="quiz-confirm-item-count"><?= (int)$n ?> <span class="quiz-confirm-item-requested">/ <?= (int)($plan['requested_subjects'][$id]??0) ?></span></span></li><?php endforeach;?>
            </ul>
        </section>
    </div>
<?php if($plan['generated_total']>0):?><form method="post" class="quiz-confirm-actions"><?=CSRF::field()?><button name="create_quiz" value="1" class="btn btn-primary btn-lg"><i class="fa-solid fa-play" aria-hidden="true"></i> <?= $plan['exact']?'Start Quiz':'Accept and Start Quiz' ?></button><a class="btn btn-secondary" href="<?=url('student/quiz-builder.php')?>">Back to builder</a></form><?php endif;?>
</div>
<?php else:?>
<form method="post" class="card quiz-builder-form"><?=CSRF::field()?>
<div class="form-group"><label class="form-label">Module</label><select id="module_id" name="module_id" class="form-control" required><option value="">-- Select Module --</option><?php foreach($modules as $m):?><option value="<?= (int)$m['id']?>" <?= $selectedModuleId === (int)$m['id'] ? 'selected' : '' ?>><?=e($m['name'])?></option><?php endforeach;?></select></div>
<div class="form-group"><label class="form-label">Subjects (select one or more)</label><div id="subject-list" class="quiz-subject-list"><?php foreach($subjects as $s):?><label class="quiz-subject-option" data-module="<?= (int)$s['module_id']?>" style="<?= $selectedModuleId === (int)$s['module_id'] ? '' : 'display:none' ?>"><input type="checkbox" name="subject_ids[]" value="<?= (int)$s['id']?>"> <span><?=e($s['name'])?></span></label><?php endforeach;?></div></div>
<div class="form-group"><label class="form-label">Total questions</label><input class="form-control" type="number" min="1" name="total_questions" required></div>
<h3 class="form-section-title">Optional question-type distribution (%)</h3>
<div class="distribution-grid"><?php foreach(Quiz::TYPES as $type):?><label class="distribution-field"><?=strtoupper($type)?><input class="form-control" type="number" min="0" max="100" name="type_percentages[<?=$type?>]" placeholder="%"></label><?php endforeach;?></div>
<h3 class="form-section-title">Optional subject distribution (%)</h3>
<p class="form-help">Leave every percentage blank to use availability.</p>
<div class="subject-distribution-list"><?php foreach($subjects as $s):?><label class="subject-distribution-field" data-subject-percent="<?= (int)$s['id']?>" data-module="<?= (int)$s['module_id']?>" style="<?= $selectedModuleId === (int)$s['module_id'] ? '' : 'display:none' ?>"><?=e($s['name'])?> <input type="number" min="0" max="100" name="subject_percentages[<?= (int)$s['id']?>]" placeholder="%"></label><?php endforeach;?></div>
<div class="quiz-builder-actions"><button class="btn btn-primary">Check availability and build quiz</button></div>
</form>
<?php endif;?></div>
<script>
(function(){
    const m=document.getElementById('module_id');
    if(!m) return;
    const sync = () => {
        document.querySelectorAll('[data-module]').forEach(x=>{
            const show = x.dataset.module===m.value;
            x.style.display = show ? '' : 'none';
            const input=x.querySelector('input');
            if(!show && input){
                if(input.type==='checkbox')input.checked=false;
                else input.value='';
            }
        });
    };
    m.addEventListener('change', sync);
})();
</script>
