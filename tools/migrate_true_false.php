<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This migration is CLI-only.\n");
}

$pdo = Database::getInstance();
$pdo->exec('PRAGMA foreign_keys = OFF');

try {
    $pdo->beginTransaction();
    $pdo->exec("CREATE TABLE questions_new (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        subject_id INTEGER NOT NULL,
        type TEXT NOT NULL CHECK(type IN ('mcq', 'complete', 'match', 'compare', 'essay', 'true_false')),
        question_text TEXT NOT NULL,
        answer_data TEXT,
        answer_status TEXT NOT NULL DEFAULT 'available' CHECK(answer_status IN ('available', 'unavailable')),
        answer_origin TEXT NOT NULL DEFAULT 'manual' CHECK(answer_origin IN ('manual', 'json_import')),
        frequency INTEGER NOT NULL DEFAULT 1 CHECK(frequency >= 1),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )");
    $pdo->exec('INSERT INTO questions_new (id, subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at) SELECT id, subject_id, type, question_text, answer_data, answer_status, answer_origin, frequency, created_at, updated_at FROM questions');
    $pdo->exec('DROP TABLE questions');
    $pdo->exec('ALTER TABLE questions_new RENAME TO questions');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_subject_id ON questions(subject_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_type ON questions(type)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_answer_status ON questions(answer_status)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_questions_frequency ON questions(frequency)');
    $pdo->commit();
    $pdo->exec('PRAGMA foreign_keys = ON');
    echo "True/False migration completed; existing question data was preserved.\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec('PRAGMA foreign_keys = ON');
    fwrite(STDERR, "Migration failed and was rolled back: {$e->getMessage()}\n");
    exit(1);
}
