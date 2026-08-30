<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Question Management Service
 */

declare(strict_types=1);

class Question {
    /**
     * Get list of questions with filtering, search, and pagination
     */
    public static function getQuestions(array $filters = [], int $limit = 50, int $offset = 0): array {
        if ((int)($filters['module_id'] ?? 0) <= 0 && (int)($filters['subject_id'] ?? 0) <= 0) return [];
        [$where,$params]=self::filterSql($filters);
        $limit=max(1,min(100,$limit));$offset=max(0,$offset);$whereClause=$where?'WHERE '.implode(' AND ',$where):'';
        $orderBy=($filters['sort_by']??'')==='frequency_desc'?'q.frequency DESC,q.created_at DESC':(($filters['sort_by']??'')==='frequency_asc'?'q.frequency ASC,q.created_at DESC':'q.created_at DESC');
        return Database::fetchAll("SELECT q.id,q.subject_id,q.type,q.question_text,q.answer_data,q.answer_status,q.answer_origin,q.frequency,q.created_at,q.updated_at,s.name subject_name,s.module_id,m.name module_name,MIN(qs.source_name) source_name,MIN(qs.exam_year) exam_year,MIN(qs.exam_term) exam_term,COUNT(qs.id) appearance_count FROM questions q JOIN subjects s ON s.id=q.subject_id JOIN modules m ON m.id=s.module_id LEFT JOIN question_sources qs ON qs.question_id=q.id $whereClause GROUP BY q.id ORDER BY $orderBy LIMIT $limit OFFSET $offset",$params);
    }

    /**
     * Get total count of questions matching filters
     */
    public static function getQuestionsCount(array $filters = []): int {
        if ((int)($filters['module_id'] ?? 0) <= 0 && (int)($filters['subject_id'] ?? 0) <= 0) return 0;
        [$where,$params]=self::filterSql($filters);$res=Database::fetchOne('SELECT COUNT(*) cnt FROM questions q JOIN subjects s ON s.id=q.subject_id '.($where?'WHERE '.implode(' AND ',$where):''),$params);
        return (int)($res['cnt'] ?? 0);
    }

    /**
     * Get single question details by ID
     */
    public static function getQuestionById(int $id): ?array {
        $question=Database::fetchOne('SELECT q.id,q.subject_id,q.type,q.question_text,q.answer_data,q.answer_status,q.answer_origin,q.frequency,q.created_at,q.updated_at,s.name subject_name,s.module_id,m.name module_name FROM questions q JOIN subjects s ON s.id=q.subject_id JOIN modules m ON m.id=s.module_id WHERE q.id=?',[$id]);
        if ($question) {
            $question['answer_data_decoded'] = json_decode($question['answer_data'] ?? '', true);
            $question['appearances']=self::getSources($id);
            $first=$question['appearances'][0]??[];$question['source_name']=$first['source_name']??null;$question['exam_year']=isset($first['exam_year'])?(string)$first['exam_year']:null;$question['exam_term']=$first['exam_term']??null;
        }
        return $question;
    }

    public static function getSources(int $questionId): array { return Database::fetchAll('SELECT id,question_id,source_name,exam_year,exam_term,created_at FROM question_sources WHERE question_id=? ORDER BY exam_year,exam_term,source_name,id',[$questionId]); }

    private static function filterSql(array $filters): array {
        $where=[];$params=[];
        if(($filters['module_id']??'')!==''){$where[]='s.module_id=?';$params[]=(int)$filters['module_id'];}
        if(($filters['subject_id']??'')!==''){$where[]='q.subject_id=?';$params[]=(int)$filters['subject_id'];}
        if(in_array(($filters['type']??''),['mcq','complete','match','compare','essay','true_false'],true)){$where[]='q.type=?';$params[]=$filters['type'];}
        if(in_array(($filters['answer_status']??''),['available','unavailable'],true)){$where[]='q.answer_status=?';$params[]=$filters['answer_status'];}
        if(($filters['search']??'')!==''){$v='%'.trim((string)$filters['search']).'%';$where[]='(q.question_text LIKE ? OR q.answer_data LIKE ? OR EXISTS (SELECT 1 FROM question_sources qss WHERE qss.question_id=q.id AND qss.source_name LIKE ?))';array_push($params,$v,$v,$v);}
        $source=$filters['source_names']??[];if(is_string($source))$source=[$source];$source=array_values(array_intersect((array)$source,['final','end_module']));
        $sourceWhere=[];$sourceParams=[];if($source){$sourceWhere[]='qsf.source_name IN ('.implode(',',array_fill(0,count($source),'?')).')';$sourceParams=array_merge($sourceParams,$source);}
        if(($filters['exam_year']??'')!==''){if(filter_var($filters['exam_year'],FILTER_VALIDATE_INT)===false)$where[]='0=1';else{$sourceWhere[]='qsf.exam_year=?';$sourceParams[]=(int)$filters['exam_year'];}}
        if(in_array(($filters['exam_term']??''),['first','second'],true)){$sourceWhere[]='qsf.exam_term=?';$sourceParams[]=$filters['exam_term'];}
        if($sourceWhere){$where[]='EXISTS (SELECT 1 FROM question_sources qsf WHERE qsf.question_id=q.id AND '.implode(' AND ',$sourceWhere).')';$params=array_merge($params,$sourceParams);}
        return [$where,$params];
    }

    /**
     * Validate question input data
     */
    public static function validate(array $data): array {
        $errors = [];

        // Required common fields
        if (empty($data['subject_id']) || (int)$data['subject_id'] <= 0) {
            $errors[] = 'Subject selection is required.';
        } else {
            $sub = Academic::getSubjectById((int)$data['subject_id']);
            if (!$sub) {
                $errors[] = 'Selected subject does not exist.';
            }
        }

        $type = $data['type'] ?? '';
        $allowedTypes = ['mcq', 'complete', 'match', 'compare', 'essay', 'true_false'];
        if (empty($type) || !in_array($type, $allowedTypes, true)) {
            $errors[] = 'Valid question type is required.';
        }

        if (empty(trim($data['question_text'] ?? ''))) {
            $errors[] = 'Question text cannot be empty.';
        }

        $frequency = isset($data['frequency']) ? (int)$data['frequency'] : 1;
        if ($frequency < 1) {
            $errors[] = 'Frequency must be a positive integer (minimum 1).';
        }

        $answerStatus = $data['answer_status'] ?? 'available';
        if (!in_array($answerStatus, ['available', 'unavailable'], true)) {
            $errors[] = 'Invalid answer status.';
        }

        // Type-specific logic if answer is available
        if ($answerStatus === 'available') {
            if ($type === 'mcq') {
                $options = $data['options'] ?? [];
                if (!is_array($options)) {
                    $options = [];
                }
                $options = array_filter(array_map('trim', $options));
                
                if (count($options) < 2) {
                    $errors[] = 'MCQ questions require at least 2 non-empty options.';
                }

                $correct = trim($data['correct_answer'] ?? '');
                if (empty($correct)) {
                    $errors[] = 'Correct answer is required for MCQ.';
                } elseif (!in_array($correct, $options, true)) {
                    $errors[] = 'Correct answer must match one of the provided options.';
                }
            } elseif ($type === 'match') {
                $left = $data['left_items'] ?? [];
                $right = $data['right_items'] ?? [];
                if (!is_array($left)) { $left = []; }
                if (!is_array($right)) { $right = []; }
                $left = array_filter(array_map('trim', $left));
                $right = array_filter(array_map('trim', $right));

                if (count($left) < 1 || count($right) < 1) {
                    $errors[] = 'Match questions require at least one item on both the left and right sides.';
                }

                $matches = $data['matches'] ?? [];
                if (!is_array($matches) || empty($matches)) {
                    $errors[] = 'Correct matches configuration is required.';
                } else {
                    foreach ($matches as $lItem => $rItem) {
                        if (!in_array(trim((string)$lItem), $left, true)) {
                            $errors[] = "Match key \"{$lItem}\" is not present in the left items list.";
                        }
                        if (!in_array(trim((string)$rItem), $right, true)) {
                            $errors[] = "Match value \"{$rItem}\" is not present in the right items list.";
                        }
                    }
                }
            } elseif ($type === 'true_false') {
                $answer = strtolower(trim((string)($data['answer'] ?? '')));
                if (!in_array($answer, ['true', 'false'], true)) {
                    $errors[] = 'True/False answer must be true or false.';
                }
            } else {
                // complete, compare, essay
                $answer = trim($data['answer'] ?? '');
                if (empty($answer)) {
                    $errors[] = 'Answer/model answer is required when status is available.';
                }
            }
        }

        $appearanceErrors = self::normalizeAppearances($data);
        foreach ($appearanceErrors as $appearanceError) $errors[]=$appearanceError;
        if (array_key_exists('appearances', $data) && !$appearanceErrors) {
            $appearanceCount = count(self::appearanceRows($data));
            if ($appearanceCount > 0 && $frequency !== $appearanceCount) {
                $errors[] = 'Frequency must equal the number of exam appearances when exam appearances are recorded.';
            }
        }

        return $errors;
    }

    private static function normalizeAppearances(array $data): array {
        if (!array_key_exists('appearances',$data) && (($data['source_name']??'')!==''||($data['exam_year']??'')!==''||($data['exam_term']??'')!=='')) return [];
        if (array_key_exists('appearances', $data) && !is_array($data['appearances'])) return ['Exam appearances must be an array.'];
        $items = array_key_exists('appearances', $data) ? $data['appearances'] : ((($data['source_name']??'')!==''||($data['exam_year']??'')!==''||($data['exam_term']??'')!=='') ? [['source_name'=>$data['source_name']??'','exam_year'=>$data['exam_year']??null,'exam_term'=>$data['exam_term']??null]] : []);
        $errors=[]; $seen=[];
        foreach($items as $index => $appearance){
            $number = (int)$index + 1;
            if(!is_array($appearance)){$errors[]='Exam appearance #'.$number.' must be an object.';continue;}
            $source=trim((string)($appearance['source_name']??$appearance['source']??''));
            $year=$appearance['exam_year']??$appearance['year']??null;
            $term=trim((string)($appearance['exam_term']??$appearance['term']??''));
            if($source==='') $errors[]='Exam appearance #'.$number.' source is required.';
            elseif(!in_array($source,['final','end_module'],true))$errors[]='Exam source must be final or end_module.';
            if($year===null||$year==='')$errors[]='Exam appearance #'.$number.' year is required.';
            elseif(filter_var($year,FILTER_VALIDATE_INT)===false)$errors[]='Exam year must be an integer.';
            if($term==='')$errors[]='Exam appearance #'.$number.' term is required.';
            elseif(!in_array($term,['first','second'],true))$errors[]='Exam term must be first or second.';
            if($source!=='' && in_array($source,['final','end_module'],true) && $year!==null && $year!=='' && filter_var($year,FILTER_VALIDATE_INT)!==false && in_array($term,['first','second'],true)){
                $key=$source.'|'.(int)$year.'|'.$term;
                if(isset($seen[$key])) $errors[]='Exam appearance #'.$number.' duplicates an earlier appearance.';
                $seen[$key]=true;
            }
        }
        return $errors;
    }

    private static function appearanceRows(array $data): array {
        $items=array_key_exists('appearances',$data)?(array)$data['appearances']:((($data['source_name']??'')!==''||($data['exam_year']??'')!==''||($data['exam_term']??'')!=='')?[['source_name'=>$data['source_name']??'','exam_year'=>$data['exam_year']??null,'exam_term'=>$data['exam_term']??null]]:[]);$out=[];$seen=[];foreach($items as $appearance){if(!is_array($appearance))continue;$row=['source_name'=>(string)($appearance['source_name']??$appearance['source']??''),'exam_year'=>($appearance['exam_year']??$appearance['year']??null)!==''?(int)($appearance['exam_year']??$appearance['year']??0):null,'exam_term'=>($appearance['exam_term']??$appearance['term']??null)?:null];$key=$row['source_name'].'|'.($row['exam_year']??'').'|'.($row['exam_term']??'');if($row['source_name']!==''&&!isset($seen[$key])){$seen[$key]=true;$out[]=$row;}}return $out;
    }

    /**
     * Create a new question
     */
    public static function createQuestion(array $data): array {
        $errors = self::validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return Database::transaction(function (PDO $pdo) use ($data): array {
            $subjectId = (int)$data['subject_id'];
            $type = $data['type'];
            $questionText = trim($data['question_text']);
            $answerStatus = $data['answer_status'] ?? 'available';
            $frequency = isset($data['frequency']) ? (int)$data['frequency'] : 1;
            
            // Build structured answer_data
            $answerData = null;
            if ($answerStatus === 'available') {
                if ($type === 'mcq') {
                    $options = array_filter(array_map('trim', $data['options'] ?? []));
                    $correct = trim($data['correct_answer'] ?? '');
                    $answerData = json_encode([
                        'options' => array_values($options),
                        'correct_answer' => $correct
                    ]);
                } elseif ($type === 'match') {
                    $left = array_values(array_filter(array_map('trim', $data['left_items'] ?? [])));
                    $right = array_values(array_filter(array_map('trim', $data['right_items'] ?? [])));
                    $matches = [];
                    foreach ($data['matches'] ?? [] as $k => $v) {
                        $matches[trim((string)$k)] = trim((string)$v);
                    }
                    $answerData = json_encode([
                        'left_items' => $left,
                        'right_items' => $right,
                        'matches' => $matches
                    ]);
                } elseif ($type === 'true_false') {
                    $answerData = json_encode(['answer' => strtolower(trim((string)($data['answer'] ?? '')))]);
                } else {
                    $answerData = json_encode([
                        'answer' => trim($data['answer'] ?? '')
                    ]);
                }
            } else {
                // If answer unavailable, store structure with null values
                if ($type === 'mcq') {
                    $options = array_filter(array_map('trim', $data['options'] ?? []));
                    $answerData = json_encode([
                        'options' => array_values($options),
                        'correct_answer' => null
                    ]);
                } elseif ($type === 'match') {
                    $left = array_values(array_filter(array_map('trim', $data['left_items'] ?? [])));
                    $right = array_values(array_filter(array_map('trim', $data['right_items'] ?? [])));
                    $answerData = json_encode([
                        'left_items' => $left,
                        'right_items' => $right,
                        'matches' => null
                    ]);
                } elseif ($type === 'true_false') {
                    $answerData = json_encode(['answer' => null]);
                } else {
                    $answerData = json_encode([
                        'answer' => null
                    ]);
                }
            }

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('
                INSERT INTO questions (subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $subjectId, $type, $questionText, $answerData, $answerStatus, 'manual', $frequency, $now, $now
            ]);

            $questionId = (int)$pdo->lastInsertId();

            $stmtSource=$pdo->prepare('INSERT OR IGNORE INTO question_sources (question_id,source_name,exam_year,exam_term,created_at) VALUES (?,?,?,?,?)');foreach(self::appearanceRows($data) as $appearance)$stmtSource->execute([$questionId,$appearance['source_name'],$appearance['exam_year'],$appearance['exam_term'],$now]);

            return ['success' => true, 'id' => $questionId, 'message' => 'Question created successfully.'];
        });
    }

    /**
     * Update an existing question
     */
    public static function updateQuestion(int $id, array $data): array {
        $existingQ = self::getQuestionById($id);
        if (!$existingQ) {
            return ['success' => false, 'errors' => ['Question not found.']];
        }

        $errors = self::validate($data);
        if (empty($errors)) {
            $appearanceCount = array_key_exists('appearances', $data) ? count(self::appearanceRows($data)) : count(self::getSources($id));
            $frequency = isset($data['frequency']) ? (int)$data['frequency'] : 1;
            if ($appearanceCount > 0 && $frequency !== $appearanceCount) {
                $errors[] = 'Frequency must equal the number of exam appearances when exam appearances are recorded.';
            }
        }
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        return Database::transaction(function (PDO $pdo) use ($id, $data): array {
            $subjectId = (int)$data['subject_id'];
            $type = $data['type'];
            $questionText = trim($data['question_text']);
            $answerStatus = $data['answer_status'] ?? 'available';
            $frequency = isset($data['frequency']) ? (int)$data['frequency'] : 1;

            // Build structured answer_data
            $answerData = null;
            if ($answerStatus === 'available') {
                if ($type === 'mcq') {
                    $options = array_filter(array_map('trim', $data['options'] ?? []));
                    $correct = trim($data['correct_answer'] ?? '');
                    $answerData = json_encode([
                        'options' => array_values($options),
                        'correct_answer' => $correct
                    ]);
                } elseif ($type === 'match') {
                    $left = array_values(array_filter(array_map('trim', $data['left_items'] ?? [])));
                    $right = array_values(array_filter(array_map('trim', $data['right_items'] ?? [])));
                    $matches = [];
                    foreach ($data['matches'] ?? [] as $k => $v) {
                        $matches[trim((string)$k)] = trim((string)$v);
                    }
                    $answerData = json_encode([
                        'left_items' => $left,
                        'right_items' => $right,
                        'matches' => $matches
                    ]);
                } elseif ($type === 'true_false') {
                    $answerData = json_encode(['answer' => strtolower(trim((string)($data['answer'] ?? '')))]);
                } else {
                    $answerData = json_encode([
                        'answer' => trim($data['answer'] ?? '')
                    ]);
                }
            } else {
                if ($type === 'mcq') {
                    $options = array_filter(array_map('trim', $data['options'] ?? []));
                    $answerData = json_encode([
                        'options' => array_values($options),
                        'correct_answer' => null
                    ]);
                } elseif ($type === 'match') {
                    $left = array_values(array_filter(array_map('trim', $data['left_items'] ?? [])));
                    $right = array_values(array_filter(array_map('trim', $data['right_items'] ?? [])));
                    $answerData = json_encode([
                        'left_items' => $left,
                        'right_items' => $right,
                        'matches' => null
                    ]);
                } elseif ($type === 'true_false') {
                    $answerData = json_encode(['answer' => null]);
                } else {
                    $answerData = json_encode([
                        'answer' => null
                    ]);
                }
            }

            $now = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare('
                UPDATE questions 
                SET subject_id = ?, type = ?, question_text = ?, answer_data = ?, answer_status = ?, frequency = ?, updated_at = ?
                WHERE id = ?
            ');
            $stmt->execute([
                $subjectId, $type, $questionText, $answerData, $answerStatus, $frequency, $now, $id
            ]);

            if (array_key_exists('appearances',$data)) { $pdo->prepare('DELETE FROM question_sources WHERE question_id = ?')->execute([$id]);$stmtSource=$pdo->prepare('INSERT OR IGNORE INTO question_sources (question_id,source_name,exam_year,exam_term,created_at) VALUES (?,?,?,?,?)');foreach(self::appearanceRows($data) as $appearance)$stmtSource->execute([$id,$appearance['source_name'],$appearance['exam_year'],$appearance['exam_term'],$now]); }

            return ['success' => true, 'message' => 'Question updated successfully.'];
        });
    }

    /**
     * Delete a question
     */
    public static function deleteQuestion(int $id): array {
        $q = self::getQuestionById($id);
        if (!$q) {
            return ['success' => false, 'message' => 'Question not found.'];
        }

        $usage = Database::fetchOne(
            'SELECT COUNT(DISTINCT quiz_id) AS cnt FROM quiz_questions WHERE question_id = ?',
            [$id]
        );
        $quizCount = (int)($usage['cnt'] ?? 0);
        if ($quizCount > 0) {
            return ['success' => false, 'message' => "Question cannot be deleted because it is used in {$quizCount} active quiz record(s). Finish or cancel the active quiz first."];
        }

        try {
            return Database::transaction(function (PDO $pdo) use ($id): array {
                // Delete source information cascade
                $pdo->prepare('DELETE FROM question_sources WHERE question_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM questions WHERE id = ?')->execute([$id]);
                return ['success' => true, 'message' => 'Question deleted successfully.'];
            });
        } catch (Throwable $e) {
            error_log('Question::deleteQuestion failed for question ' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'The question could not be deleted because a database error occurred. No changes were made.'];
        }
    }
}
