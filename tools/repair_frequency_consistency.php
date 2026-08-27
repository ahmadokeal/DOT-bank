<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

$rows = Database::fetchAll('SELECT q.id, q.frequency, COUNT(qs.id) AS appearance_count FROM questions q JOIN question_sources qs ON qs.question_id=q.id GROUP BY q.id HAVING q.frequency <> appearance_count');
echo 'Inconsistent records found: ' . count($rows) . PHP_EOL;
if (!$rows) exit(0);

Database::transaction(function (PDO $pdo) use ($rows): void {
    $update = $pdo->prepare('UPDATE questions SET frequency=?, updated_at=? WHERE id=?');
    $now = date('Y-m-d H:i:s');
    foreach ($rows as $row) $update->execute([(int)$row['appearance_count'], $now, (int)$row['id']]);
});
echo 'Repaired records: ' . count($rows) . PHP_EOL;
