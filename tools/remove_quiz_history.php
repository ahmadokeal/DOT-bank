<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This migration is CLI-only.\n");
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

$apply = in_array('--apply', $argv, true);
$pdo = Database::getInstance();
$before = Database::fetchOne('SELECT COUNT(*) total, SUM(completed_at IS NOT NULL) completed, SUM(completed_at IS NULL) active FROM quizzes');

echo 'Quiz rows before migration: ' . (int)($before['total'] ?? 0) . PHP_EOL;
echo 'Completed rows to remove: ' . (int)($before['completed'] ?? 0) . PHP_EOL;
echo 'Active rows to preserve: ' . (int)($before['active'] ?? 0) . PHP_EOL;

if (!$apply) {
    echo "Dry run only. Re-run with --apply to remove completed quiz history.\n";
    exit(0);
}

$backupDir = ROOT_PATH . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Unable to create the protected backup directory.');
}
$backupPath = $backupDir . '/dot_bank.sqlite.before_quiz_history_removal_' . date('Ymd_His') . '.sqlite';
$pdo->exec("VACUUM INTO " . $pdo->quote($backupPath));
echo 'Backup created: ' . $backupPath . PHP_EOL;

try {
    Database::transaction(function (PDO $transactionPdo): void {
        $transactionPdo->exec('DELETE FROM quizzes WHERE completed_at IS NOT NULL');
    });
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed and was rolled back: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$after = Database::fetchOne('SELECT COUNT(*) total, SUM(completed_at IS NOT NULL) completed, SUM(completed_at IS NULL) active FROM quizzes');
$foreignKeys = $pdo->query('PRAGMA foreign_key_check')->fetchAll();
echo 'Quiz rows after migration: ' . (int)($after['total'] ?? 0) . PHP_EOL;
echo 'Completed rows after migration: ' . (int)($after['completed'] ?? 0) . PHP_EOL;
echo 'Active rows after migration: ' . (int)($after['active'] ?? 0) . PHP_EOL;
echo 'Foreign-key violations: ' . count($foreignKeys) . PHP_EOL;

if (count($foreignKeys) > 0 || (int)($after['completed'] ?? 0) !== 0) {
    fwrite(STDERR, "Migration verification failed. Restore the backup before further use.\n");
    exit(1);
}
