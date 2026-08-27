<?php
declare(strict_types=1);

/**
 * Phase 5 quiz planner. Percentage counts use largest-remainder allocation:
 * floor each exact quota, then assign remaining questions by descending
 * remainder (stable by ID). Exact two-dimensional plans use max-flow over
 * subject -> type cells. Closest plans allocate one question at a time to the
 * available cell with the lowest deterministic squared deviation score.
 */
class Quiz {
    public const TYPES = ['mcq', 'complete', 'match', 'compare', 'essay'];

    public static function availability(int $moduleId, array $subjectIds): array {
        $subjectIds = self::subjectScope($moduleId, $subjectIds);
        if (!$subjectIds) return ['subjects' => [], 'types' => [], 'cells' => [], 'total' => 0];
        $marks = implode(',', array_fill(0, count($subjectIds), '?'));
        $rows = Database::fetchAll("SELECT subject_id, type, COUNT(*) count FROM questions WHERE subject_id IN ($marks) GROUP BY subject_id, type", $subjectIds);
        $subjects = array_fill_keys($subjectIds, 0); $types = array_fill_keys(self::TYPES, 0); $cells = [];
        foreach ($rows as $row) {
            $s=(int)$row['subject_id']; $t=$row['type']; $n=(int)$row['count'];
            $cells[$s][$t]=$n; $subjects[$s]+=$n; $types[$t]+=$n;
        }
        return ['subjects'=>$subjects,'types'=>$types,'cells'=>$cells,'total'=>array_sum($subjects)];
    }

    public static function plan(array $input): array {
        $moduleId=(int)($input['module_id']??0); $subjectIds=array_values(array_unique(array_map('intval', $input['subject_ids']??[])));
        $total=filter_var($input['total_questions']??null, FILTER_VALIDATE_INT);
        if ($total===false || $total<1) return self::fail('Choose a whole number of at least 1 question.');
        $subjectIds=self::subjectScope($moduleId,$subjectIds);
        if (!$subjectIds) return self::fail('Choose a module and at least one subject from that module.');
        $availability=self::availability($moduleId,$subjectIds);
        if ($availability['total']===0) return self::fail('The selected subjects do not contain any questions.');
        $typePct=self::percentages($input['type_percentages']??[], self::TYPES);
        $subjectPct=self::percentages($input['subject_percentages']??[], array_map('strval',$subjectIds));
        if ($typePct['error']) return self::fail($typePct['error']);
        if ($subjectPct['error']) return self::fail($subjectPct['error']);
        $requestedTypes=$typePct['specified'] ? self::allocate($total,$typePct['values']) : self::allocateAvailable($total,$availability['types']);
        $subjectWeights=$subjectPct['specified'] ? $subjectPct['values'] : $availability['subjects'];
        $requestedSubjects=self::allocate($total,$subjectWeights);
        /*
         * Only an explicitly configured dimension is a hard constraint.
         * A 100% Essay quiz must therefore be able to use the subjects that
         * have essays, instead of being blocked by a display-only default
         * proportional subject allocation.
         */
        $rowTargets=$subjectPct['specified'] ? $requestedSubjects : $availability['subjects'];
        $columnTargets=$typePct['specified'] ? $requestedTypes : $availability['types'];
        $matrix=self::flowMatrix($rowTargets,$columnTargets,$availability['cells'],$total);
        $exact=$matrix!==null;
        if (!$exact) $matrix=self::closestMatrix(
            $total,
            $requestedSubjects,
            $requestedTypes,
            $availability['cells'],
            $subjectPct['specified'],
            $typePct['specified']
        );
        $actualTotal=array_sum(array_map('array_sum',$matrix));
        $actualSubjects=array_fill_keys($subjectIds,0); $actualTypes=array_fill_keys(self::TYPES,0);
        foreach($matrix as $s=>$row) foreach($row as $t=>$n){$actualSubjects[$s]+= $n; $actualTypes[$t]+= $n;}
        $reasons=[];
        if($typePct['specified']) foreach($requestedTypes as $t=>$n) if(($actualTypes[$t]??0)!==$n) $reasons[] = strtoupper($t).": requested $n, proposed ".($actualTypes[$t]??0).".";
        if($subjectPct['specified']) foreach($requestedSubjects as $s=>$n) if(($actualSubjects[$s]??0)!==$n) $reasons[] = "Subject #$s: requested $n, proposed ".($actualSubjects[$s]??0).".";
        if($actualTotal<$total) $reasons[]="Only $actualTotal of $total questions are available in the selected scope.";
        $questionIds=self::selectQuestions($matrix);
        $subjectNames=[];
        $subjectMarks=implode(',',array_fill(0,count($subjectIds),'?'));
        $subjectRows=Database::fetchAll("SELECT id,name FROM subjects WHERE module_id=? AND id IN ($subjectMarks)",array_merge([$moduleId],$subjectIds));
        foreach($subjectRows as $subject)$subjectNames[(int)$subject['id']]=$subject['name'];
        return ['success'=>true,'exact'=>$exact,'requires_confirmation'=>true,'module_id'=>$moduleId,'subject_ids'=>$subjectIds,'subject_names'=>$subjectNames,
            'requested_total'=>$total,'generated_total'=>$actualTotal,'requested_types'=>$requestedTypes,'requested_subjects'=>$requestedSubjects,
            'actual_types'=>$actualTypes,'actual_subjects'=>$actualSubjects,'availability'=>$availability,'matrix'=>$matrix,
            'question_ids'=>$questionIds,'reasons'=>$reasons];
    }

    public static function create(int $userId, array $plan): array {
        if(empty($plan['question_ids']) || empty($plan['module_id'])) return self::fail('The quiz plan has expired or contains no questions.');
        $ids=array_values(array_unique(array_map('intval',$plan['question_ids'])));
        $valid=self::validQuestionIds((int)$plan['module_id'],$plan['subject_ids'],$ids);
        if(count($valid)!==count($ids)) return self::fail('Quiz questions are no longer available in the selected subjects.');
        return Database::transaction(function(PDO $pdo) use($userId,$plan,$ids){
            $now=date('Y-m-d H:i:s');
            $pdo->prepare('INSERT INTO quizzes (user_id,module_id,total_questions,created_at) VALUES (?,?,?,?)')
                ->execute([$userId,(int)$plan['module_id'],count($ids),$now]);
            $quizId=(int)$pdo->lastInsertId();
            $stmt=$pdo->prepare('INSERT INTO quiz_questions (quiz_id,question_id,question_order) VALUES (?,?,?)');
            foreach($ids as $i=>$id) $stmt->execute([$quizId,$id,$i+1]);
            return ['success'=>true,'id'=>$quizId];
        });
    }

    public static function getForStudent(int $quizId,int $userId): ?array {
        $quiz=Database::fetchOne('SELECT * FROM quizzes WHERE id=? AND user_id=?',[$quizId,$userId]);
        if(!$quiz) return null;
        $quiz['questions']=Database::fetchAll('SELECT qq.id quiz_question_id,qq.question_order,q.* FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? ORDER BY qq.question_order',[$quizId]);
        return $quiz;
    }

    public static function submit(int $quizId,int $userId,array $answers): array {
        try {
            return Database::transaction(function(PDO $pdo) use($quizId,$userId,$answers){
                $quiz=Database::fetchOne('SELECT * FROM quizzes WHERE id=? AND user_id=?',[$quizId,$userId]);
                if(!$quiz || $quiz['completed_at']) return self::fail('This quiz is unavailable for submission.');
                $questions=Database::fetchAll('SELECT qq.id quiz_question_id,qq.question_order,q.* FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id WHERE qq.quiz_id=? ORDER BY qq.question_order',[$quizId]);
                if(!$questions) return self::fail('This quiz contains no questions.');
                $existing=Database::fetchOne('SELECT 1 FROM quiz_answers WHERE quiz_question_id IN (SELECT id FROM quiz_questions WHERE quiz_id=?) LIMIT 1',[$quizId]);
                if($existing) return self::fail('This quiz has already been submitted.');
                $correct=0;$auto=0;$self=0;$unanswered=0;$reviewQuestions=[];$stmt=$pdo->prepare('INSERT INTO quiz_answers (quiz_question_id,student_answer,is_correct) VALUES (?,?,?)');
                foreach($questions as $q){
                    $raw=$answers[(int)$q['quiz_question_id']]??null;$data=json_decode($q['answer_data']??'',true)?:[];$isCorrect=null;$stored='';
                    if($q['type']==='mcq'){
                        if(is_array($raw)) throw new InvalidArgumentException('Invalid MCQ answer format.');$stored=trim((string)($raw??''));$options=$data['options']??[];if($stored!==''&&!in_array($stored,$options,true))throw new InvalidArgumentException('Invalid MCQ answer.');$isCorrect=($stored!==''&&$stored===($data['correct_answer']??null))?1:0;$auto++;if($isCorrect===0)$unanswered+=($stored==='')?1:0;else $correct++;
                    } elseif($q['type']==='match'){
                        if($raw===null||$raw==='')$raw=[];if(!is_array($raw))throw new InvalidArgumentException('Invalid Match answer format.');if($raw!==[]&&count(array_filter($raw,fn($value)=>trim((string)$value)!==''))===0)$raw=[];$left=$data['left_items']??[];$right=$data['right_items']??[];foreach($raw as $key=>$value)if(!in_array((string)$key,$left,true)||!in_array((string)$value,$right,true))throw new InvalidArgumentException('Invalid Match answer.');$stored=json_encode($raw,JSON_UNESCAPED_UNICODE);$expected=$data['matches']??null;if(is_array($expected)){$a=$raw;$b=$expected;ksort($a);ksort($b);$isCorrect=($raw!==[]&&$a===$b)?1:0;}else{$isCorrect=0;}$auto++;if($isCorrect===1)$correct++;elseif($raw===[])$unanswered++;
                    } else {
                        if(is_array($raw))throw new InvalidArgumentException('Invalid self-graded answer format.');$stored=trim((string)($raw??''));$self++;
                    }
                    $stmt->execute([(int)$q['quiz_question_id'],$stored,$isCorrect]);
                    $q['student_answer']=$stored;$q['is_correct']=$isCorrect;$q['answer_data_decoded']=$data;$reviewQuestions[]=$q;
                }
                $score=$auto>0?round(($correct/$auto)*100,2):null;
                $module=Database::fetchOne('SELECT name FROM modules WHERE id=?',[(int)$quiz['module_id']]);
                $result=['success'=>true,'id'=>$quizId,'module_name'=>$module['name']??'','total_questions'=>count($reviewQuestions),'auto_graded'=>$auto,'correct'=>$correct,'incorrect'=>$auto-$correct-$unanswered,'unanswered'=>$unanswered,'self_graded'=>$self,'score'=>$score,'questions'=>$reviewQuestions];
                // Submitted quizzes are transient. Their answer rows and references are
                // deleted with the quiz after the immediate result payload is prepared.
                $pdo->prepare('DELETE FROM quizzes WHERE id=?')->execute([$quizId]);
                return $result;
            });
        } catch (InvalidArgumentException $e) { return self::fail($e->getMessage()); }
    }

    public static function discard(int $quizId,int $userId): array {
        try {
            return Database::transaction(function(PDO $pdo) use($quizId,$userId){
                $stmt=$pdo->prepare('DELETE FROM quizzes WHERE id=? AND user_id=? AND completed_at IS NULL');
                $stmt->execute([$quizId,$userId]);
                return $stmt->rowCount()===1?['success'=>true,'message'=>'Quiz discarded successfully.']:self::fail('This quiz is unavailable or has already been completed.');
            });
        } catch(Throwable $e) { error_log('Quiz discard failed: '.$e->getMessage()); return self::fail('The quiz could not be discarded.'); }
    }

    private static function percentages(array $raw,array $keys): array {
        $values=[];$specified=false;$sum=0;
        foreach($keys as $key){$v=$raw[$key]??''; if($v===''){$values[$key]=0;continue;} if(!is_numeric($v)||(float)$v<0) return ['error'=>'Percentages must be non-negative numbers.']; $specified=true;$values[$key]=(float)$v;$sum+=(float)$v;}
        if($specified && abs($sum-100)>0.00001) return ['error'=>'Configured percentages must add up to exactly 100%.'];
        return ['error'=>null,'specified'=>$specified,'values'=>$values];
    }
    private static function allocate(int $total,array $weights): array {
        $sum=array_sum($weights); if($sum<=0) return array_fill_keys(array_keys($weights),0);
        $out=[];$remainders=[];$used=0; foreach($weights as $key=>$weight){$exact=$total*$weight/$sum;$out[$key]=(int)floor($exact);$used+=$out[$key];$remainders[]=['key'=>$key,'r'=>$exact-$out[$key]];}
        usort($remainders,fn($a,$b)=>$b['r']<=>$a['r'] ?: strcmp((string)$a['key'],(string)$b['key']));
        for($i=0;$i<$total-$used;$i++)$out[$remainders[$i]['key']]++;
        return $out;
    }
    private static function allocateAvailable(int $total,array $available): array {return self::allocate(min($total,array_sum($available)),$available);}
    private static function subjectScope(int $moduleId,array $ids): array {
        if($moduleId<1||!$ids)return[];$marks=implode(',',array_fill(0,count($ids),'?'));
        return array_map('intval',array_column(Database::fetchAll("SELECT id FROM subjects WHERE module_id=? AND id IN ($marks)",array_merge([$moduleId],$ids)),'id'));
    }
    private static function exactMatrix(array $rows,array $cols,array $caps): ?array {
        if(array_sum($rows)!==array_sum($cols))return null;
        return self::flowMatrix($rows,$cols,$caps,array_sum($rows));
    }
    private static function flowMatrix(array $rows,array $cols,array $caps,int $target): ?array {
        if($target<1)return [];
        $source='source';$sink='sink';$residual=[];$original=[];
        $add=function(string $from,string $to,int $capacity)use(&$residual,&$original):void{
            $residual[$from][$to]=($residual[$from][$to]??0)+$capacity;
            $residual[$to][$from]=$residual[$to][$from]??0;
            $original[$from][$to]=($original[$from][$to]??0)+$capacity;
        };
        foreach($rows as $s=>$n)$add($source,'s'.$s,$n);
        foreach($rows as $s=>$_)foreach($cols as $t=>$_)$add('s'.$s,'t'.$t,(int)($caps[$s][$t]??0));
        foreach($cols as $t=>$n)$add('t'.$t,$sink,$n);
        $flow=0;
        while($flow<$target){
            $queue=[$source];$parent=[$source=>null];
            for($i=0;$i<count($queue)&&!isset($parent[$sink]);$i++){
                $node=$queue[$i];foreach($residual[$node]??[] as $next=>$capacity)if($capacity>0&&!array_key_exists($next,$parent)){$parent[$next]=$node;$queue[]=$next;}
            }
            if(!isset($parent[$sink]))break;
            $amount=$target-$flow;for($node=$sink;$parent[$node]!==null;$node=$parent[$node])$amount=min($amount,$residual[$parent[$node]][$node]);
            for($node=$sink;$parent[$node]!==null;$node=$parent[$node]){$from=$parent[$node];$residual[$from][$node]-=$amount;$residual[$node][$from]=($residual[$node][$from]??0)+$amount;}
            $flow+=$amount;
        }
        if($flow!==$target)return null;
        $alloc=[];foreach($rows as $s=>$_)foreach($cols as $t=>$_){$from='s'.$s;$to='t'.$t;$alloc[$s][$t]=($original[$from][$to]??0)-($residual[$from][$to]??0);}
        return $alloc;
    }
    private static function closestMatrix(int $total,array $rows,array $cols,array $caps,bool $enforceRows=true,bool $enforceColumns=true): array {
        $out=[];$r=array_fill_keys(array_keys($rows),0);$c=array_fill_keys(array_keys($cols),0);
        $maxAvailable=0;
        foreach($caps as $s=>$cells){
            if($enforceRows&&($rows[$s]??0)<=0)continue;
            foreach($cells as $t=>$cap){
                if($enforceColumns&&($cols[$t]??0)<=0)continue;
                $maxAvailable+=(int)$cap;
            }
        }
        $max=min($total,$maxAvailable);
        for($k=0;$k<$max;$k++){ $best=null;$score=null; foreach($caps as $s=>$cells){if($enforceRows&&($rows[$s]??0)<=0)continue;foreach($cells as $t=>$cap){if($enforceColumns&&($cols[$t]??0)<=0)continue;$used=$out[$s][$t]??0;if($used>=$cap)continue;$next=0;if($enforceRows)$next+=(($r[$s]+1)-($rows[$s]??0))**2-(($r[$s])-($rows[$s]??0))**2;if($enforceColumns)$next+=(($c[$t]+1)-($cols[$t]??0))**2-(($c[$t])-($cols[$t]??0))**2; if($score===null||$next<$score||($next===$score&&"$s|$t"<"$best[0]|$best[1]")){$best=[$s,$t];$score=$next;}}} if(!$best)break;[$s,$t]=$best;$out[$s][$t]=($out[$s][$t]??0)+1;$r[$s]++;$c[$t]++; }
        return $out;
    }
    private static function selectQuestions(array $matrix): array {
        $selected=[];foreach($matrix as $s=>$row)foreach($row as $t=>$count){if(!$count)continue;$candidates=array_column(Database::fetchAll('SELECT id FROM questions WHERE subject_id=? AND type=? ORDER BY id',[(int)$s,$t]),'id');self::shuffle($candidates);$selected=array_merge($selected,array_slice($candidates,0,$count));}self::shuffle($selected);return $selected;
    }
    private static function shuffle(array &$items):void{for($i=count($items)-1;$i>0;$i--){$j=random_int(0,$i);[$items[$i],$items[$j]]=[$items[$j],$items[$i]];}}
    private static function validQuestionIds(int $moduleId,array $subjects,array $ids):array{if(!$ids)return[];$marks=implode(',',array_fill(0,count($ids),'?'));$subjectMarks=implode(',',array_fill(0,count($subjects),'?'));return array_map('intval',array_column(Database::fetchAll("SELECT q.id FROM questions q JOIN subjects s ON s.id=q.subject_id WHERE s.module_id=? AND q.subject_id IN ($subjectMarks) AND q.id IN ($marks)",array_merge([$moduleId],$subjects,$ids)),'id'));}
    private static function fail(string $message):array{return['success'=>false,'errors'=>[$message]];}
}
