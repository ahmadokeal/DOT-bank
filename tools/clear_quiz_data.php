<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This cleanup is CLI-only.\n");
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

if (!in_array('--apply', $argv, true)) {
    fwrite(STDERR, "No records were changed. Re-run with --apply to clear all quiz data.\n");
    exit(1);
}

$pdo = Database::getInstance();
$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Unable to create the protected backup directory.');
}
$backupPath = $backupDir . '/dot_bank.sqlite.before_all_quiz_data_clear_' . date('Ymd_His') . '.sqlite';
$pdo->exec('VACUUM INTO ' . $pdo->quote($backupPath));

$before = Database::fetchOne('SELECT COUNT(*) AS quizzes FROM quizzes');
Database::transaction(function (PDO $transactionPdo): void {
    $transactionPdo->exec('DELETE FROM quizzes');
});

$after = [
    'quizzes' => (int)Database::fetchOne('SELECT COUNT(*) AS c FROM quizzes')['c'],
    'quiz_questions' => (int)Database::fetchOne('SELECT COUNT(*) AS c FROM quiz_questions')['c'],
    'quiz_answers' => (int)Database::fetchOne('SELECT COUNT(*) AS c FROM quiz_answers')['c'],
    'foreign_keys' => count($pdo->query('PRAGMA foreign_key_check')->fetchAll()),
];

echo 'Backup created: ' . $backupPath . PHP_EOL;
echo 'Quiz rows removed: ' . (int)($before['quizzes'] ?? 0) . PHP_EOL;
echo json_encode($after) . PHP_EOL;

if (array_sum([$after['quizzes'], $after['quiz_questions'], $after['quiz_answers'], $after['foreign_keys']]) !== 0) {
    fwrite(STDERR, "Verification failed. Restore the backup before further use.\n");
    exit(1);
}
