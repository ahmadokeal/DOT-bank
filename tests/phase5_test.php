<?php
declare(strict_types=1);
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';
$pass=0;$fail=0;$ok=function(bool $v,string $n)use(&$pass,&$fail){echo($v?'[PASS] ':'[FAIL] ').$n.PHP_EOL;$v?$pass++:$fail++;};
$pdo=Database::getInstance();$pdo->exec(file_get_contents(DATABASE_PATH.'/schema.sql'));
$pdo->exec('DELETE FROM quiz_answers;DELETE FROM quiz_questions;DELETE FROM quizzes;DELETE FROM question_sources;DELETE FROM questions;DELETE FROM subjects;DELETE FROM modules;DELETE FROM users;');
$pdo->prepare("INSERT INTO users(username,password_hash,role,status) VALUES(?,?, 'student','active')")->execute(['quizstudent',password_hash('StudentPass123',PASSWORD_DEFAULT)]);$student=(int)$pdo->lastInsertId();
$pdo->prepare('INSERT INTO modules(name) VALUES(?)')->execute(['Quiz Module']);$module=(int)$pdo->lastInsertId();
foreach(['Subject A','Subject B'] as $name){$pdo->prepare('INSERT INTO subjects(module_id,name) VALUES(?,?)')->execute([$module,$name]);$subjects[]=(int)$pdo->lastInsertId();}
$types=['mcq','complete','match','compare','essay'];
foreach($subjects as $s)foreach($types as $type)for($i=0;$i<3;$i++){$data=$type==='mcq'?['options'=>['A','B'],'correct_answer'=>'A']:($type==='match'?['left_items'=>['L'],'right_items'=>['R'],'matches'=>['L'=>'R']]:['answer'=>'Model']);$pdo->prepare("INSERT INTO questions(subject_id,type,question_text,answer_data,answer_status,answer_origin,frequency) VALUES(?,?,?,?, 'available','manual',?)")->execute([$s,$type,"$type $s $i",json_encode($data),$i+1]);}
$exact=Quiz::plan(['module_id'=>$module,'subject_ids'=>$subjects,'total_questions'=>10,'type_percentages'=>['mcq'=>50,'essay'=>50,'complete'=>0,'match'=>0,'compare'=>0],'subject_percentages'=>[$subjects[0]=>50,$subjects[1]=>50]]);
$ok($exact['success']&&$exact['exact'],'Satisfiable combined matrix creates exact plan');
$ok($exact['generated_total']===10&&count(array_unique($exact['question_ids']))===10,'Exact plan has requested unique question count');
$ok($exact['actual_types']['mcq']===5&&$exact['actual_types']['essay']===5,'Type distribution is exact');
$ok($exact['actual_subjects'][$subjects[0]]===5&&$exact['actual_subjects'][$subjects[1]]===5,'Subject distribution is exact');
/* A type-only request may use an uneven subject split when no subject split was configured. */
foreach(['Uneven A','Uneven B'] as $name){$pdo->prepare('INSERT INTO subjects(module_id,name) VALUES(?,?)')->execute([$module,$name]);$unevenSubjects[]=(int)$pdo->lastInsertId();}
foreach([[10,1],[10,3]] as $index=>$counts)foreach(['mcq'=>$counts[0],'essay'=>$counts[1]] as $type=>$count)for($i=0;$i<$count;$i++)$pdo->prepare("INSERT INTO questions(subject_id,type,question_text,answer_data,answer_status,answer_origin,frequency) VALUES(?,?,?,?, 'available','manual',1)")->execute([$unevenSubjects[$index],$type,"Uneven $type $index $i",json_encode(['answer'=>'Model'])]);
$typeOnly=Quiz::plan(['module_id'=>$module,'subject_ids'=>$unevenSubjects,'total_questions'=>4,'type_percentages'=>['mcq'=>0,'essay'=>100,'complete'=>0,'match'=>0,'compare'=>0]]);
$ok($typeOnly['success']&&$typeOnly['exact']&&$typeOnly['actual_types']['essay']===4,'Type-only request remains exact when essays require an uneven subject split');
$ok($typeOnly['actual_subjects'][$unevenSubjects[0]]===1&&$typeOnly['actual_subjects'][$unevenSubjects[1]]===3,'Unspecified subject distribution does not become a hard quota');
$created=Quiz::create($student,$exact);$ok($created['success'],'Quiz persistence creates quiz row');
$quiz=Quiz::getForStudent($created['id'],$student);$ok(count($quiz['questions'])===10,'Quiz stores ordered question references');
$ok(count(array_unique(array_column($quiz['questions'],'id')))===10,'Quiz has no duplicate question IDs');
$answers=[];foreach($quiz['questions'] as $q){$data=json_decode($q['answer_data'],true)?:[];$answers[$q['quiz_question_id']]=$q['type']==='mcq'?($data['options'][0]??''):($q['type']==='match'?($data['matches']??[]):'response');}
$submitted=Quiz::submit($created['id'],$student,$answers);$ok($submitted['success'],'Student responses submit');
$ok(count($submitted['questions']??[])===10&&count(array_unique(array_column($submitted['questions']??[],'id')))===10,'Immediate grading payload contains one reviewed question per quiz question');
$rounded=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>7,'type_percentages'=>['mcq'=>50,'essay'=>50,'complete'=>0,'match'=>0,'compare'=>0]]);
$ok($rounded['success']&&array_sum($rounded['requested_types'])===7,'Largest-remainder allocation preserves requested total');
$closest=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>10,'type_percentages'=>['mcq'=>100,'essay'=>0,'complete'=>0,'match'=>0,'compare'=>0]]);
$ok($closest['success']&&!$closest['exact']&&$closest['generated_total']===3,'100% MCQ shortage reduces the proposed total');
$ok($closest['actual_types']['mcq']===3&&array_sum(array_intersect_key($closest['actual_types'],array_flip(['complete','match','compare','essay'])))===0,'Closest plan never introduces an unrequested type');
$essayClosest=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>10,'type_percentages'=>['mcq'=>0,'essay'=>100,'complete'=>0,'match'=>0,'compare'=>0]]);
$ok($essayClosest['success']&&!$essayClosest['exact']&&$essayClosest['generated_total']===3&&$essayClosest['actual_types']['essay']===3&&array_sum(array_intersect_key($essayClosest['actual_types'],array_flip(['mcq','complete','match','compare'])))===0,'100% Essay shortage reduces the proposed total without adding other types');
$mixedClosest=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>10,'type_percentages'=>['mcq'=>50,'match'=>50,'complete'=>0,'essay'=>0,'compare'=>0]]);
$ok($mixedClosest['success']&&!$mixedClosest['exact']&&$mixedClosest['generated_total']===6&&$mixedClosest['actual_types']['mcq']===3&&$mixedClosest['actual_types']['match']===3&&array_sum(array_intersect_key($mixedClosest['actual_types'],array_flip(['complete','essay','compare'])))===0,'Explicit MCQ plus Match shortage stays within requested types');
$subjectClosest=Quiz::plan(['module_id'=>$module,'subject_ids'=>$subjects,'total_questions'=>20,'subject_percentages'=>[$subjects[0]=>100,$subjects[1]=>0]]);
$ok($subjectClosest['success']&&!$subjectClosest['exact']&&$subjectClosest['generated_total']===15&&$subjectClosest['actual_subjects'][$subjects[0]]===15&&$subjectClosest['actual_subjects'][$subjects[1]]===0,'Explicit subject shortage reduces the proposal without adding another subject');
$flexibleTypes=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>10]);
$ok($flexibleTypes['success']&&$flexibleTypes['generated_total']===10&&count(array_filter($flexibleTypes['actual_types']))>1,'Unspecified type remains flexible');
$bad=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>0]);$ok(!$bad['success'],'Zero total is rejected');
$ok(!isset($bad['exact'])&&!isset($bad['requested_total']),'Failed plan does not expose success-only view fields');
$builderController=file_get_contents(ROOT_PATH.'/public/student/quiz-builder.php');$ok(strpos($builderController,'$plan=null')!==false,'Quiz builder clears failed plans before rendering');
$cross=Quiz::create($student,array_merge($exact,['subject_ids'=>[$subjects[0]],'question_ids'=>$exact['question_ids']]));$ok(!$cross['success'],'Cross-subject tampering is rejected');
$matchPlan=Quiz::plan(['module_id'=>$module,'subject_ids'=>[$subjects[0]],'total_questions'=>1,'type_percentages'=>['match'=>100,'mcq'=>0,'complete'=>0,'compare'=>0,'essay'=>0]]);
$emptyMatch=Quiz::create($student,$matchPlan);$emptyResult=Quiz::submit($emptyMatch['id'],$student,[]);$matchReview=$emptyResult['questions'][0]??[];
$ok($emptyResult['success']&&($matchReview['student_answer']??null)==='[]'&&($matchReview['is_correct']??null)===0&&$emptyResult['unanswered']===1,'Untouched Match answer is stored as unanswered and graded incorrect');
$active=Quiz::create($student,$exact);$discard=Quiz::discard($active['id'],$student);
$ok($discard['success']&&Quiz::getForStudent($active['id'],$student)===null,'Student can discard an active quiz');
$ok((int)Database::fetchOne('SELECT COUNT(*) c FROM questions WHERE id IN ('.implode(',',array_fill(0,count($exact['question_ids']),'?')).')', $exact['question_ids'])['c']===10,'Discard preserves question-bank rows');
$takeView=file_get_contents(ROOT_PATH.'/views/student/quizzes/take.php');$builderView=file_get_contents(ROOT_PATH.'/views/student/quizzes/builder.php');
$ok(strpos($takeView,'Select an answer')!==false&&strpos($takeView,'quiz-discard.php')!==false,'Quiz UI exposes Match placeholder and discard action');
$ok(strpos($builderView,'Accept and Start Quiz')!==false&&strpos($builderView,'Subject #')===false,'Closest proposal uses subject names and explicit acceptance wording');
echo "Phase 5 Test Results: $pass Passed, $fail Failed".PHP_EOL;exit($fail?1:0);
