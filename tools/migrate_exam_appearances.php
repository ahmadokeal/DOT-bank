<?php
declare(strict_types=1);
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH.'/config/config.php';
$pdo=Database::getInstance();
$pdo->exec('BEGIN');
try {
    $pdo->exec('ALTER TABLE question_sources RENAME TO question_sources_legacy_migration');
    $pdo->exec('CREATE TABLE question_sources (id INTEGER PRIMARY KEY AUTOINCREMENT, question_id INTEGER NOT NULL, source_name TEXT NOT NULL, exam_year INTEGER, exam_term TEXT, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE)');
    $rows=$pdo->query('SELECT * FROM question_sources_legacy_migration ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $insert=$pdo->prepare('INSERT OR IGNORE INTO question_sources(id,question_id,source_name,exam_year,exam_term,created_at) VALUES(?,?,?,?,?,?)');
    foreach($rows as $row){$name=trim((string)($row['source_name']??''));$year=is_numeric($row['exam_year']??null)?(int)$row['exam_year']:null;$term=trim((string)($row['exam_term']??''));$canonical=null;if(preg_match('/^final(?:\s+exam)?(?:\s+(\d{4}))?$/i',$name,$m)){$canonical='final';if(!$year&&!empty($m[1]))$year=(int)$m[1];}elseif(preg_match('/^end\s*module(?:\s+exam)?(?:\s+(\d{4}))?$/i',$name,$m)){$canonical='end_module';if(!$year&&!empty($m[1]))$year=(int)$m[1];}$insert->execute([(int)$row['id'],(int)$row['question_id'],$canonical??$name,$year,$term!==''?$term:null,$row['created_at']??date('Y-m-d H:i:s')]);}
    $pdo->exec('DROP TABLE question_sources_legacy_migration');
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_question_sources_question_id ON question_sources(question_id);CREATE INDEX IF NOT EXISTS idx_question_sources_source_name ON question_sources(source_name);CREATE INDEX IF NOT EXISTS idx_question_sources_exam_year ON question_sources(exam_year);CREATE INDEX IF NOT EXISTS idx_question_sources_exam_term ON question_sources(exam_term);CREATE UNIQUE INDEX IF NOT EXISTS uq_question_exam_appearance ON question_sources(question_id,source_name,COALESCE(exam_year,-1),COALESCE(exam_term,''));CREATE TABLE IF NOT EXISTS question_conflicts (id INTEGER PRIMARY KEY AUTOINCREMENT,question_id INTEGER NOT NULL,incoming_answer_data TEXT NOT NULL,incoming_appearances TEXT,status TEXT NOT NULL DEFAULT 'review' CHECK(status IN ('review','resolved')),created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(question_id) REFERENCES questions(id) ON DELETE CASCADE)");
    $pdo->exec('COMMIT');echo "Migrated ".count($rows)." question appearance rows without changing question IDs.".PHP_EOL;
}catch(Throwable $e){if($pdo->inTransaction())$pdo->exec('ROLLBACK');fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
