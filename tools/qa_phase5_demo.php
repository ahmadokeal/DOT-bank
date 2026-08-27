<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

$passed=0;$failed=0;
$assert=function(bool $condition,string $name)use(&$passed,&$failed):void{echo($condition?'[PASS] ':'[FAIL] ').$name.PHP_EOL;$condition?$passed++:$failed++;};
$module=Database::fetchOne('SELECT id FROM modules WHERE name=?',['DOT Bank Demo Module']);
if(!$module) exit("Run tools/seed_demo.php first.\n");
$moduleId=(int)$module['id'];
$subjects=Database::fetchAll('SELECT id,name FROM subjects WHERE module_id=? ORDER BY id',[$moduleId]);
$ids=array_map(fn($row)=>(int)$row['id'],$subjects);[$anatomy,$physiology,$biochem,$histology,$micro]=$ids;
$username='phase5_qa_'.random_int(100000,999999);
Database::query("INSERT INTO users (username,password_hash,role,status) VALUES (?,?,'student','active')",[$username,password_hash('TemporaryPass123',PASSWORD_DEFAULT)]);
$userId=(int)Database::lastInsertId();

try {
    $build=function(array $input)use($moduleId){return Quiz::plan(array_merge(['module_id'=>$moduleId],$input));};
    foreach([1,5,10,20,300] as $total){$p=$build(['subject_ids'=>$ids,'total_questions'=>$total]);$assert($p['success']&&$p['exact']&&$p['generated_total']===$total,"Basic exact quiz: $total questions");}
    foreach(Quiz::TYPES as $type){$pct=array_fill_keys(Quiz::TYPES,0);$pct[$type]=100;$p=$build(['subject_ids'=>$ids,'total_questions'=>min(20,Quiz::availability($moduleId,$ids)['types'][$type]),'type_percentages'=>$pct]);$assert($p['success']&&$p['exact']&&$p['actual_types'][$type]===$p['generated_total'],"100% ".strtoupper($type));}
    foreach([
        ['total'=>7,'pct'=>['mcq'=>50,'essay'=>50]],
        ['total'=>9,'pct'=>['mcq'=>33,'complete'=>33,'essay'=>34]],
        ['total'=>11,'pct'=>['mcq'=>45,'complete'=>20,'match'=>15,'compare'=>10,'essay'=>10]],
    ] as $case){$pct=array_fill_keys(Quiz::TYPES,0);foreach($case['pct'] as $t=>$v)$pct[$t]=$v;$a=$build(['subject_ids'=>$ids,'total_questions'=>$case['total'],'type_percentages'=>$pct]);$b=$build(['subject_ids'=>$ids,'total_questions'=>$case['total'],'type_percentages'=>$pct]);$assert($a['success']&&$a['exact']&&array_sum($a['requested_types'])===$case['total']&&$a['requested_types']===$b['requested_types'],"Deterministic integer rounding for {$case['total']} questions");}
    $subjectPct=[$anatomy=>50,$physiology=>50];$p=$build(['subject_ids'=>[$anatomy,$physiology],'total_questions'=>20,'subject_percentages'=>$subjectPct]);$assert($p['exact']&&$p['actual_subjects'][$anatomy]===10&&$p['actual_subjects'][$physiology]===10,'Two-subject exact distribution');
    $pct=array_fill_keys(Quiz::TYPES,0);$pct['mcq']=50;$pct['essay']=50;$p=$build(['subject_ids'=>[$anatomy,$physiology],'total_questions'=>20,'subject_percentages'=>$subjectPct,'type_percentages'=>$pct]);$assert($p['exact']&&$p['actual_subjects'][$anatomy]===10&&$p['actual_types']['mcq']===10,'Combined subject/type exact matrix');
    $bad=$build(['subject_ids'=>[$histology],'total_questions'=>10,'type_percentages'=>array_replace(array_fill_keys(Quiz::TYPES,0),['essay'=>100])]);$assert($bad['success']&&!$bad['exact']&&$bad['actual_types']['essay']<=5,'Impossible type gives feasible closest plan');
    $global=$build(['subject_ids'=>$ids,'total_questions'=>301]);$assert($global['success']&&!$global['exact']&&$global['generated_total']===300,'Over-maximum total gives closest available total');
    $invalid=$build(['subject_ids'=>[$anatomy],'total_questions'=>10,'type_percentages'=>['mcq'=>60,'essay'=>60]]);$assert(!$invalid['success'],'Invalid percentage sum rejected server-side');
    $invalid=$build(['subject_ids'=>[999999],'total_questions'=>10]);$assert(!$invalid['success'],'Invalid subject rejected server-side');
    $invalid=$build(['subject_ids'=>[$anatomy],'total_questions'=>'2.5']);$assert(!$invalid['success'],'Non-integer total rejected server-side');
    $mixed=$build(['subject_ids'=>[$anatomy,$physiology],'total_questions'=>20,'subject_percentages'=>[$anatomy=>50,$physiology=>50],'type_percentages'=>array_replace(array_fill_keys(Quiz::TYPES,0),['essay'=>100])]);$assert($mixed['success']&&!$mixed['exact']&&$mixed['generated_total']===20,'Impossible combined matrix offers closest plan');
    $plan=$build(['subject_ids'=>[$anatomy,$physiology],'total_questions'=>20,'subject_percentages'=>$subjectPct,'type_percentages'=>$pct]);
    $before=(int)Database::fetchOne('SELECT COUNT(*) c FROM quizzes WHERE user_id=?',[$userId])['c'];
    $created=Quiz::create($userId,$plan);$assert($created['success'],'Exact plan persists');
    $quiz=Quiz::getForStudent((int)$created['id'],$userId);$orders=array_column($quiz['questions'],'question_order');$questionIds=array_column($quiz['questions'],'id');
    $assert(count($quiz['questions'])===20&&count(array_unique($questionIds))===20&&$orders===range(1,20),'Persisted quiz has unique IDs and contiguous order');
    $integrity=Database::fetchOne('SELECT COUNT(*) c FROM quiz_questions qq LEFT JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? AND q.id IS NULL',[$created['id']]);
    $assert((int)$integrity['c']===0,'No orphan quiz question records');
    $available=array_filter($quiz['questions'],fn($q)=>$q['answer_status']==='available');$unavailable=array_filter($quiz['questions'],fn($q)=>$q['answer_status']==='unavailable');
    $assert($available!==[]&&$unavailable!==[],'Mixed answer availability is included without modification');
    $tampered=$plan;$tampered['question_ids']=array_slice($plan['question_ids'],0,19);$tampered['question_ids'][]=999999;$assert(!Quiz::create($userId,$tampered)['success'],'Tampered question IDs are rejected');
    $assert((int)Database::fetchOne('SELECT COUNT(*) c FROM quizzes WHERE user_id=?',[$userId])['c']===$before+1,'Failed creations leave no partial quiz rows');
} finally {
    Database::execute('DELETE FROM users WHERE id=?',[$userId]);
}
echo "Phase 5 Demo QA: $passed Passed, $failed Failed".PHP_EOL;
exit($failed?1:0);
