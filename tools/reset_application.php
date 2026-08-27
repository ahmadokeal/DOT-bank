<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This reset is CLI-only.\n");
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

if (!in_array('--apply', $argv, true)) {
    fwrite(STDERR, "No records were changed. Re-run with --apply to reset the application.\n");
    exit(1);
}

$pdo = Database::getInstance();
$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Unable to create the protected backup directory.');
}
$backupPath = $backupDir . '/dot_bank.sqlite.before_full_reset_' . date('Ymd_His') . '.sqlite';
$pdo->exec('VACUUM INTO ' . $pdo->quote($backupPath));

Database::transaction(function (PDO $transactionPdo): void {
    foreach (['quiz_answers', 'quiz_questions', 'quizzes', 'question_conflicts', 'question_sources', 'questions', 'subjects', 'modules', 'users', 'app_config'] as $table) {
        $transactionPdo->exec('DELETE FROM ' . $table);
    }
    $transactionPdo->exec("DELETE FROM sqlite_sequence");
});

if (file_exists(LOCK_FILE) && !unlink(LOCK_FILE)) {
    throw new RuntimeException('Database records were cleared, but the installation lock could not be removed.');
}

$remaining = [];
foreach (['users', 'modules', 'subjects', 'questions', 'question_sources', 'question_conflicts', 'quizzes', 'quiz_questions', 'quiz_answers', 'app_config'] as $table) {
    $remaining[$table] = (int)$pdo->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn();
}
$remaining['foreign_keys'] = count($pdo->query('PRAGMA foreign_key_check')->fetchAll());
$remaining['setup_lock_exists'] = file_exists(LOCK_FILE) ? 1 : 0;

echo 'Backup created: ' . $backupPath . PHP_EOL;
echo json_encode($remaining) . PHP_EOL;

if (array_sum(array_slice($remaining, 0, 10)) !== 0 || $remaining['foreign_keys'] !== 0 || $remaining['setup_lock_exists'] !== 0) {
    fwrite(STDERR, "Reset verification failed. Restore the backup before further use.\n");
    exit(1);
}
