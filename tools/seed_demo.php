<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit("CLI only.\n"); }
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

const DEMO_MODULE = 'DOT Bank Demo Module';
const DEMO_PREFIX = '[DEMO] ';
$matrix = [
    'Anatomy' => ['mcq'=>45,'complete'=>15,'match'=>10,'compare'=>8,'essay'=>2],
    'Physiology' => ['mcq'=>30,'complete'=>20,'match'=>5,'compare'=>10,'essay'=>15],
    'Biochemistry' => ['mcq'=>25,'complete'=>15,'match'=>10,'compare'=>5,'essay'=>5],
    'Histology' => ['mcq'=>15,'complete'=>10,'match'=>5,'compare'=>5,'essay'=>5],
    'Microbiology' => ['mcq'=>10,'complete'=>10,'match'=>5,'compare'=>7,'essay'=>8],
];
$topics = [
    'Anatomy'=>['brachial plexus','femoral triangle','portal circulation','cranial nerves','vertebral column'],
    'Physiology'=>['cardiac cycle','renal clearance','action potential','acid-base balance','respiratory mechanics'],
    'Biochemistry'=>['glycolysis','urea cycle','DNA replication','enzyme kinetics','lipid metabolism'],
    'Histology'=>['epithelial tissue','renal corpuscle','thyroid follicle','lymph node','skeletal muscle'],
    'Microbiology'=>['bacterial cell wall','viral replication','antimicrobial resistance','sterilization','host immunity'],
];
$sources=['Midterm 2024','Final Exam 2024','Midterm 2025','Final Exam 2025','Practical Exam 2025',''];

if (Database::fetchOne('SELECT id FROM modules WHERE name = ?', [DEMO_MODULE])) exit("Demo dataset already exists. Run tools/reset_demo.php before reseeding.\n");
Database::transaction(function(PDO $pdo) use($matrix,$topics,$sources): void {
    $pdo->prepare('INSERT INTO modules (name,description,created_at) VALUES (?,?,?)')->execute([DEMO_MODULE,'Development-only deterministic dataset for Phase 5 quiz testing.',date('Y-m-d H:i:s')]);
    $moduleId=(int)$pdo->lastInsertId(); $questionStmt=$pdo->prepare('INSERT INTO questions (subject_id,type,question_text,answer_data,answer_status,answer_origin,frequency,created_at,updated_at) VALUES (?,?,?,?,?,?,?, ?, ?)');
    $sourceStmt=$pdo->prepare('INSERT INTO question_sources (question_id,source_name,created_at) VALUES (?,?,?)');
    foreach($matrix as $subjectName=>$counts){
        $pdo->prepare('INSERT INTO subjects (module_id,name,description,created_at) VALUES (?,?,?,?)')->execute([$moduleId,DEMO_PREFIX.$subjectName,'Demo-only Phase 5 test subject.',date('Y-m-d H:i:s')]);
        $subjectId=(int)$pdo->lastInsertId(); $serial=0;
        foreach($counts as $type=>$count) for($n=1;$n<=$count;$n++){
            $serial++; $topic=$topics[$subjectName][($serial-1)%count($topics[$subjectName])]; $available=($serial%4!==0);
            [$text,$data]=demoQuestion($subjectName,$topic,$type,$serial,$available);
            $now=date('Y-m-d H:i:s'); $source=$sources[($serial-1)%count($sources)]; $frequency=$source!=='' ? 1 : [1,2,3,4,5,6,8,10][($serial-1)%8];
            $questionStmt->execute([$subjectId,$type,$text,json_encode($data,JSON_UNESCAPED_UNICODE),$available?'available':'unavailable','manual',$frequency,$now,$now]);
            if($source!=='')$sourceStmt->execute([(int)$pdo->lastInsertId(),$source,$now]);
        }
    }
});
echo "Seeded 1 demo module, 5 subjects, and 300 deterministic demo questions.\n";

function demoQuestion(string $subject,string $topic,string $type,int $n,bool $available): array {
    $tag=DEMO_PREFIX."$subject — $topic (set $n)";
    return match($type) {
        'mcq' => ["$tag: Which statement is most accurate in this clinical scenario?", ['options'=>["Core principle of $topic","Alternative mechanism in $topic","Unrelated finding","Incorrect interpretation"],'correct_answer'=>$available?"Core principle of $topic":null]],
        'complete' => ["$tag: Complete the statement: the key concept is ____.", ['answer'=>$available?"the core mechanism of $topic":null]],
        'match' => ["$tag: Match each structure or concept to its description.", ['left_items'=>['A: Primary concept','B: Secondary concept'],'right_items'=>["Main feature of $topic","Supporting feature of $topic"],'matches'=>$available?['A: Primary concept'=>"Main feature of $topic",'B: Secondary concept'=>"Supporting feature of $topic"]:null]],
        'compare' => ["$tag: Compare the normal and altered forms of this process.", ['answer'=>$available?"Normal $topic follows its expected regulation, whereas altered $topic disrupts that regulation.":null]],
        'essay' => ["$tag: Discuss the clinical relevance and mechanism of this topic.", ['answer'=>$available?"A complete response should explain the mechanism, clinical importance, and key applications of $topic.":null]],
    };
}
