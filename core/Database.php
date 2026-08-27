<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * SQLite Database Connection & Query Wrapper
 */

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    /**
     * Get or create PDO SQLite connection singleton
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $storageDir = dirname(DB_FILE);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $dsn = 'sqlite:' . DB_FILE;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            self::$instance = new PDO($dsn, null, null, $options);
            
            // Enforce foreign key constraints and WAL journal mode for performance
            self::$instance->exec('PRAGMA foreign_keys = ON;');
            self::$instance->exec('PRAGMA journal_mode = WAL;');
        }

        return self::$instance;
    }

    /**
     * Execute a prepared query with parameters
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row
     */
    public static function fetchOne(string $sql, array $params = []): ?array {
        $stmt = self::query($sql, $params);
        $result = $stmt->fetch();
        return $result === false ? null : $result;
    }

    /**
     * Fetch all rows
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::query($sql, $params);
        return $stmt->fetchAll();
    }

    /**
     * Execute an INSERT/UPDATE/DELETE statement and return affected rows
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::query($sql, $params);
        return $stmt->rowCount();
    }

    /**
     * Get the ID of the last inserted row
     */
    public static function lastInsertId(): string {
        return self::getInstance()->lastInsertId();
    }

    /**
     * Execute callback within a database transaction
     */
    public static function transaction(callable $callback): mixed {
        $pdo = self::getInstance();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reset connection instance (useful for testing or re-initialization)
     */
    public static function reset(): void {
        self::$instance = null;
    }
}
