<?php
/**
 * Shared disposable test environment.
 *
 * Every test suite runs from a temporary application copy so its SQLite
 * database and installation lock can never point at the developer instance.
 */

declare(strict_types=1);

$sourceRoot = dirname(__DIR__);
$testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dot-bank-test-' . bin2hex(random_bytes(8));

$copyTree = static function (string $source, string $destination) use (&$copyTree): void {
    if (is_dir($source)) {
        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            throw new RuntimeException('Unable to create disposable test directory.');
        }

        foreach (scandir($source) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $copyTree($source . DIRECTORY_SEPARATOR . $entry, $destination . DIRECTORY_SEPARATOR . $entry);
        }
        return;
    }

    if (!copy($source, $destination)) {
        throw new RuntimeException('Unable to copy disposable test fixture.');
    }
};

if (!mkdir($testRoot, 0755, true) && !is_dir($testRoot)) {
    throw new RuntimeException('Unable to create disposable test root.');
}

foreach (scandir($sourceRoot) ?: [] as $entry) {
    if ($entry === '.' || $entry === '..' || $entry === '.git' || $entry === 'storage' || $entry === 'tests') {
        continue;
    }
    $copyTree($sourceRoot . DIRECTORY_SEPARATOR . $entry, $testRoot . DIRECTORY_SEPARATOR . $entry);
}

mkdir($testRoot . DIRECTORY_SEPARATOR . 'storage', 0755, true);
copy($sourceRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.htaccess', $testRoot . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.htaccess');

define('ROOT_PATH', $testRoot);
require_once ROOT_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

// Some legacy suites assume an earlier suite initialized the schema. Make
// each isolated process self-contained without touching the real database.
$pdo = Database::getInstance();
$pdo->exec(file_get_contents(DATABASE_PATH . DIRECTORY_SEPARATOR . 'schema.sql'));

register_shutdown_function(static function () use ($testRoot): void {
    $removeTree = static function (string $path) use (&$removeTree): void {
        if (is_dir($path)) {
            foreach (scandir($path) ?: [] as $entry) {
                if ($entry !== '.' && $entry !== '..') {
                    $removeTree($path . DIRECTORY_SEPARATOR . $entry);
                }
            }
            @rmdir($path);
            return;
        }
        @unlink($path);
    };

    $removeTree($testRoot);
});
