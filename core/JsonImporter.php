<?php
declare(strict_types=1);

class JsonImporter {
    public const MAX_FILE_SIZE=2097152;
    private const TYPES=['mcq','complete','match','compare','essay'];
    public static function parse(string $json,int $moduleId,int $subjectId): array {
        if($json===''||strlen($json)>self::MAX_FILE_SIZE||!mb_check_encoding($json,'UTF-8'))return self::failure($json===''?'The uploaded file is empty.':(strlen($json)>self::MAX_FILE_SIZE?'The JSON file exceeds the 2 MB limit.':'The JSON file must be valid UTF-8.'));
        try{$decoded=json_decode($json,true,512,JSON_THROW_ON_ERROR);}catch(JsonException $e){return self::failure('The file is not valid JSON.');}
        if(!is_array($decoded)||!isset($decoded['questions'])||!is_array($decoded['questions']))return self::failure('The top-level questions field must be an array.');$target=Database::fetchOne('SELECT m.name module_name,s.name subject_name FROM subjects s JOIN modules m ON m.id=s.module_id WHERE s.id=? AND s.module_id=?',[$subjectId,$moduleId]);if(!$target)return self::failure('The selected subject does not belong to the selected module.');
        $valid=[];$invalid=[];$warnings=[];$types=[];$with=0;foreach($decoded['questions'] as $i=>$record){$item=self::normalize($record,(int)$i+1);if($item['errors']){$invalid[]=$item;continue;}if($item['warnings'])$warnings[]=['number'=>$item['number'],'warnings'=>$item['warnings']];$item['normalized']['subject_id']=$subjectId;$valid[]=$item['normalized'];$types[$item['normalized']['type']]=($types[$item['normalized']['type']]??0)+1;if($item['normalized']['answer_status']==='available')$with++;}
        foreach ($valid as $index => $record) { $frequencyError = self::frequencyConsistencyError($record); if ($frequencyError !== null) { $invalid[]=['number'=>$index+1,'errors'=>[$frequencyError],'warnings'=>[]]; unset($valid[$index]); } }
        $valid=array_values($valid);$types=[];$with=0;foreach($valid as $record){$types[$record['type']]=($types[$record['type']]??0)+1;if($record['answer_status']==='available')$with++;}$duplicates=self::findDuplicates($valid);$impact=self::previewImpact($valid);return ['success'=>true,'module'=>$target['module_name'],'subject'=>$target['subject_name'],'valid'=>$valid,'invalid'=>$invalid,'warnings'=>$warnings,'duplicates'=>$duplicates,'summary'=>['total'=>count($decoded['questions']),'valid'=>count($valid),'invalid'=>count($invalid),'duplicates'=>count($duplicates),'types'=>$types,'with_answers'=>$with,'without_answers'=>count($valid)-$with,'new_questions'=>$impact['new_questions'],'merge_records'=>$impact['merge_records'],'duplicate_appearances'=>$impact['duplicate_appearances'],'warning_count'=>count($warnings)]];
    }
    public static function import(array $records): array {
        try { return Database::transaction(function(PDO $pdo)use($records){$newQuestions=0;$addedAppearances=0;$with=0;$without=0;$duplicates=0;$conflicts=0;$now=date('Y-m-d H:i:s');foreach($records as $record){$existing=self::findQuestion($record);if(!$existing){$pdo->prepare('INSERT INTO questions(subject_id,type,question_text,answer_data,answer_status,answer_origin,frequency,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([(int)$record['subject_id'],$record['type'],$record['question_text'],json_encode($record['answer_data'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$record['answer_status'],'json_import',(int)$record['frequency'],$now,$now]);$questionId=(int)$pdo->lastInsertId();$newQuestions++;}else{$questionId=(int)$existing['id'];if($record['appearances'])$pdo->prepare('UPDATE questions SET frequency=?,updated_at=? WHERE id=?')->execute([(int)$record['frequency'],$now,$questionId]);if($existing['answer_status']==='unavailable'&&$record['answer_status']==='available')$pdo->prepare('UPDATE questions SET answer_data=?,answer_status=?,answer_origin=?,updated_at=? WHERE id=?')->execute([json_encode($record['answer_data'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),'available','json_import',$now,$questionId]);elseif($existing['answer_status']==='available'&&$record['answer_status']==='available'&&!self::sameAnswer($existing,$record)){$pdo->prepare('INSERT INTO question_conflicts(question_id,incoming_answer_data,incoming_appearances,created_at) VALUES(?,?,?,?)')->execute([$questionId,json_encode($record['answer_data'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),json_encode($record['appearances'],JSON_UNESCAPED_UNICODE),$now]);$conflicts++;}}
                foreach($record['appearances'] as $appearance){$pdo->prepare('INSERT OR IGNORE INTO question_sources(question_id,source_name,exam_year,exam_term,created_at) VALUES(?,?,?,?,?)')->execute([$questionId,$appearance['source_name'],$appearance['exam_year'],$appearance['exam_term'],$now]);if((int)$pdo->query('SELECT changes()')->fetchColumn()===1)$addedAppearances++;else$duplicates++;}
                $storedAppearanceCount=(int)$pdo->query('SELECT COUNT(*) FROM question_sources WHERE question_id='.(int)$questionId)->fetchColumn();if($storedAppearanceCount>0&&(int)$record['frequency']!==$storedAppearanceCount)throw new InvalidArgumentException('Frequency ('.(int)$record['frequency'].') must equal the number of recorded exam appearances ('.$storedAppearanceCount.').');
                $record['answer_status']==='available'?$with++:$without++;}
            return ['success'=>true,'imported'=>$newQuestions,'new_questions'=>$newQuestions,'added_appearances'=>$addedAppearances,'duplicate_appearances'=>$duplicates,'conflicts'=>$conflicts,'with_answers'=>$with,'without_answers'=>$without];}); } catch (InvalidArgumentException $e) { return ['success'=>false,'errors'=>[$e->getMessage()]]; }
    }
    private static function frequencyConsistencyError(array $record): ?string {
        $existing=self::findQuestion($record);$count=0;$seen=[];
        if($existing){$stored=self::getStoredAppearances((int)$existing['id']);foreach($stored as $appearance){$seen[self::appearanceKey($appearance)]=true;}$count=count($seen);}
        foreach($record['appearances'] as $appearance){$key=self::appearanceKey($appearance);if(!isset($seen[$key])){$seen[$key]=true;$count++;}}
        if($count>0&&(int)$record['frequency']!==$count)return 'Frequency ('.(int)$record['frequency'].') must equal the number of exam appearances ('.$count.').';return null;
    }
    private static function getStoredAppearances(int $questionId): array { return Database::fetchAll('SELECT source_name,exam_year,exam_term FROM question_sources WHERE question_id=?',[$questionId]); }
    private static function appearanceKey(array $appearance): string { return (string)($appearance['source_name']??$appearance['source']??'').'|'.(string)($appearance['exam_year']??$appearance['year']??'').'|'.(string)($appearance['exam_term']??$appearance['term']??''); }
    private static function normalize(mixed $record,int $number):array{$errors=[];$warnings=[];if(!is_array($record))return ['number'=>$number,'errors'=>['Question must be an object.'],'warnings'=>[]];$type=$record['type']??null;$question=$record['question']??null;$frequency=$record['frequency']??1;if(!is_string($type)||!in_array($type,self::TYPES,true))$errors[]='type must be one of mcq, complete, match, compare, essay.';if(!is_string($question)||trim($question)==='')$errors[]='question is required and cannot be empty.';if(!is_int($frequency)||$frequency<1)$errors[]='frequency must be an integer greater than or equal to 1.';$hasAnswer=array_key_exists('answer',$record)&&$record['answer']!==null&&$record['answer']!=='';$answerData=['answer'=>null];
        if($type==='mcq'){$choices=$record['choices']??null;if(!is_array($choices)||array_is_list($choices)||count($choices)<2)$errors[]='choices must be a non-empty object with at least two options.';else{$options=[];foreach($choices as $label=>$text){if(!is_string($label)||!preg_match('/^[A-Z]$/',$label))$errors[]='MCQ choice labels must be single uppercase letters.';if(!is_string($text)||trim($text)==='')$errors[]='MCQ choice text cannot be empty.';$options[]=[(string)$label,trim((string)$text)];}$answer=$record['answer']??null;$answerExists=$answer!==null&&is_string($answer)&&array_key_exists($answer,$choices);if($answer!==null&&!$answerExists)$errors[]='MCQ answer must reference an existing choice label.';$answerData=['options'=>array_column($options,1),'correct_answer'=>$answerExists?trim((string)$choices[$answer]):null];}}
        elseif($type==='match'){$pairs=$record['pairs']??null;if(!is_array($pairs)||!$pairs)$errors[]='pairs must be a non-empty array.';else{$left=[];$right=[];$matches=[];foreach($pairs as $pair){if(!is_array($pair)||!is_string($pair['left']??null)||trim($pair['left'])===''||!is_string($pair['right']??null)||trim($pair['right'])===''){$errors[]='Each match pair requires non-empty left and right strings.';continue;}$l=trim($pair['left']);$r=trim($pair['right']);if(in_array($l,$left,true))$errors[]='Match left items must be unique.';$left[]=$l;$right[]=$r;$matches[$l]=$r;}$answerData=['left_items'=>$left,'right_items'=>$right,'matches'=>$hasAnswer?$matches:null];}}
        elseif(in_array($type,['complete','compare','essay'],true))$answerData=['answer'=>$hasAnswer?trim((string)$record['answer']):null];
        $appearanceErrors=[];$appearances=self::appearances($record,$warnings,$appearanceErrors);$errors=array_merge($errors,$appearanceErrors);if($errors)return ['number'=>$number,'errors'=>$errors,'warnings'=>$warnings];return ['number'=>$number,'errors'=>[],'warnings'=>$warnings,'normalized'=>['type'=>$type,'question_text'=>trim((string)$question),'frequency'=>$frequency,'appearances'=>$appearances,'answer_status'=>$hasAnswer?'available':'unavailable','answer_data'=>$answerData]];
    }
    private static function appearances(array $record,array &$warnings,array &$errors):array
    {
        $strict=array_key_exists('appearances',$record);
        $raw=$record['appearances']??null;
        if(!$strict&&$raw===null&&array_key_exists('source',$record)){
            $source=trim((string)$record['source']);
            if(preg_match('/^final(?:\s+exam)?(?:\s+(\d{4}))?$/i',$source,$m))$raw=[['source'=>'final','year'=>isset($m[1])?(int)$m[1]:null,'term'=>null]];
            elseif(preg_match('/^end\s*module(?:\s+exam)?(?:\s+(\d{4}))?$/i',$source,$m))$raw=[['source'=>'end_module','year'=>isset($m[1])?(int)$m[1]:null,'term'=>null]];
            elseif($source!==''){$raw=[['source'=>$source,'year'=>null,'term'=>null]];$warnings[]='Legacy/unknown source retained for review: '.$source;}
        }
        $raw=$raw??[];
        if(!is_array($raw)){if($strict)$errors[]='appearances must be an array.';return[];}
        $out=[];
        foreach($raw as $item){
            if(!is_array($item)){$errors[]='Each exam appearance must be an object.';continue;}
            $source=trim((string)($item['source']??$item['source_name']??''));
            $year=$item['year']??$item['exam_year']??null;
            $term=$item['term']??$item['exam_term']??null;
            if($source===''){$errors[]='Exam source is required.';continue;}
            if(!in_array($source,['final','end_module'],true))$warnings[]='Unsupported exam source requires review: '.$source;
            $normalizedYear=null;
            if($strict){
                if(!is_int($year)||$year<1)$errors[]='Exam year must be an integer greater than zero.';else$normalizedYear=$year;
                if(!in_array($term,['first','second'],true))$errors[]='Exam term must be first or second.';
            }else{
                if($year!==null&&$year!==''&&filter_var($year,FILTER_VALIDATE_INT)===false)$warnings[]='Invalid exam year requires review.';elseif($year!==null&&$year!=='')$normalizedYear=(int)$year;
                if($term!==null&&$term!==''&&!in_array($term,['first','second'],true))$warnings[]='Unsupported exam term requires review.';
                if(in_array($source,['final','end_module'],true)&&$normalizedYear===null)$warnings[]='Legacy source has no year and requires review.';
            }
            $out[]=['source_name'=>$source,'exam_year'=>$normalizedYear,'exam_term'=>$term!==null&&$term!==''?(string)$term:null];
        }
        return$out;
    }
    private static function findQuestion(array $record):?array{$rows=Database::fetchAll('SELECT id,type,question_text,answer_data,answer_status FROM questions WHERE subject_id=? AND type=?',[(int)$record['subject_id'],$record['type']]);foreach($rows as $row)if(self::identity($row)===self::identity($record))return $row;return null;}
    private static function identity(array $record):string{$raw=$record['answer_data']??[];$data=is_string($raw)?(json_decode($raw,true)?:[]):$raw;$base=['type'=>$record['type'],'question'=>strtolower(trim((string)$record['question_text']))];if($record['type']==='mcq')$base['options']=array_map(fn($v)=>strtolower(trim((string)$v)),(array)($data['options']??[]));elseif($record['type']==='match'){$base['left']=array_map(fn($v)=>strtolower(trim((string)$v)),(array)($data['left_items']??[]));$base['right']=array_map(fn($v)=>strtolower(trim((string)$v)),(array)($data['right_items']??[]));}return sha1(json_encode($base));}
    private static function sameAnswer(array $existing,array $record):bool{$old=json_decode($existing['answer_data']??'',true)?:[];$new=$record['answer_data']??[];return ($old['correct_answer']??$old['answer']??$old['matches']??null)==($new['correct_answer']??$new['answer']??$new['matches']??null);}
    private static function previewImpact(array $records):array{$seen=[];$appearances=[];$new=0;$merge=0;$duplicate=0;foreach($records as $record){$identity=self::identity($record);$existing=self::findQuestion($record);if($existing||isset($seen[$identity]))$merge++;else$new++;$seen[$identity]=true;if(!isset($appearances[$identity])){$appearances[$identity]=[];if($existing)foreach(self::getStoredAppearances((int)$existing['id']) as $a)$appearances[$identity][self::appearanceKey($a)]=true;}foreach($record['appearances'] as $a){$key=self::appearanceKey($a);if(isset($appearances[$identity][$key]))$duplicate++;else$appearances[$identity][$key]=true;}}return ['new_questions'=>$new,'merge_records'=>$merge,'duplicate_appearances'=>$duplicate];}
    private static function findDuplicates(array $records):array{$seen=[];$out=[];foreach($records as $i=>$record){$key=self::identity($record);foreach($record['appearances'] as $a){$appearance=$key.'|'.$a['source_name'].'|'.($a['exam_year']??'').'|'.($a['exam_term']??'');if(isset($seen[$appearance]))$out[]=['number'=>$i+1,'reason'=>'Repeats the same question and exam appearance in this import.'];$seen[$appearance]=true;}}return $out;}
    private static function failure(string $message):array{return ['success'=>false,'errors'=>[$message]];}
}
