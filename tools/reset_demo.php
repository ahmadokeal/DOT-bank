<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';
const DEMO_MODULE = 'DOT Bank Demo Module';
$module=Database::fetchOne('SELECT id FROM modules WHERE name=?',[DEMO_MODULE]);
if(!$module) exit("No demo dataset found.\n");
$moduleId=(int)$module['id'];
$mixed=Database::fetchOne('SELECT qq.quiz_id FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id JOIN subjects s ON s.id=q.subject_id WHERE s.module_id=? GROUP BY qq.quiz_id HAVING COUNT(*) != (SELECT COUNT(*) FROM quiz_questions x WHERE x.quiz_id=qq.quiz_id)',[$moduleId]);
if($mixed) exit("Reset refused: a quiz mixes demo and non-demo questions. Remove that quiz manually first.\n");
Database::transaction(function(PDO $pdo)use($moduleId):void{
    $stmt=$pdo->prepare('SELECT DISTINCT qq.quiz_id FROM quiz_questions qq JOIN questions q ON q.id=qq.question_id JOIN subjects s ON s.id=q.subject_id WHERE s.module_id=?');$stmt->execute([$moduleId]);$quizIds=array_column($stmt->fetchAll(PDO::FETCH_ASSOC),'quiz_id');
    foreach($quizIds as $id){$pdo->prepare('DELETE FROM quiz_answers WHERE quiz_question_id IN (SELECT id FROM quiz_questions WHERE quiz_id=?)')->execute([$id]);$pdo->prepare('DELETE FROM quiz_questions WHERE quiz_id=?')->execute([$id]);$pdo->prepare('DELETE FROM quizzes WHERE id=?')->execute([$id]);}
    $pdo->prepare('DELETE FROM modules WHERE id=?')->execute([$moduleId]);
});
echo "Removed the DOT Bank demo dataset and any quizzes made exclusively from it.\n";
