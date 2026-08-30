<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Academic Structure Management Service (Modules & Subjects)
 */

declare(strict_types=1);

class Academic {
    // ==========================================
    // MODULE OPERATIONS
    // ==========================================

    /**
     * Get all modules with subject counts
     */
    public static function getAllModules(): array {
        $sql = "
            SELECT 
                m.id,
                m.name,
                m.description,
                m.created_at,
                COUNT(s.id) AS subject_count
            FROM modules m
            LEFT JOIN subjects s ON s.module_id = m.id
            GROUP BY m.id
            ORDER BY m.name ASC
        ";
        return Database::fetchAll($sql);
    }

    /**
     * Get single module by ID with subject list
     */
    public static function getModuleById(int $id): ?array {
        $module = Database::fetchOne('SELECT * FROM modules WHERE id = ?', [$id]);
        if (!$module) {
            return null;
        }

        $module['subjects'] = Database::fetchAll(
            'SELECT * FROM subjects WHERE module_id = ? ORDER BY name ASC',
            [$id]
        );
        $module['subject_count'] = count($module['subjects']);

        return $module;
    }

    /**
     * Create a new module
     */
    public static function createModule(string $name, ?string $description = null): array {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'Module name is required.'];
        }

        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'message' => 'Module name must be between 2 and 100 characters.'];
        }

        // Check uniqueness (case-insensitive)
        $existing = Database::fetchOne('SELECT id FROM modules WHERE name = ? COLLATE NOCASE', [$name]);
        if ($existing) {
            return ['success' => false, 'message' => 'A module with this name already exists.'];
        }

        $now = date('Y-m-d H:i:s');
        Database::query(
            'INSERT INTO modules (name, description, created_at) VALUES (?, ?, ?)',
            [$name, $description, $now]
        );

        $id = (int)Database::lastInsertId();
        return ['success' => true, 'id' => $id, 'message' => 'Module created successfully.'];
    }

    /**
     * Update an existing module
     */
    public static function updateModule(int $id, string $name, ?string $description = null): array {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'Module name is required.'];
        }

        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'message' => 'Module name must be between 2 and 100 characters.'];
        }

        $module = Database::fetchOne('SELECT id FROM modules WHERE id = ?', [$id]);
        if (!$module) {
            return ['success' => false, 'message' => 'Module not found.'];
        }

        // Check uniqueness excluding current module
        $existing = Database::fetchOne(
            'SELECT id FROM modules WHERE name = ? COLLATE NOCASE AND id != ?',
            [$name, $id]
        );
        if ($existing) {
            return ['success' => false, 'message' => 'Another module with this name already exists.'];
        }

        Database::query(
            'UPDATE modules SET name = ?, description = ? WHERE id = ?',
            [$name, $description, $id]
        );

        return ['success' => true, 'message' => 'Module updated successfully.'];
    }

    /**
     * Delete a module and all associated subjects safely
     */
    public static function deleteModule(int $id): array {
        $module = Database::fetchOne('SELECT id, name FROM modules WHERE id = ?', [$id]);
        if (!$module) {
            return ['success' => false, 'message' => 'Module not found.'];
        }

        $subjectCount = (int)(Database::fetchOne('SELECT COUNT(*) as cnt FROM subjects WHERE module_id = ?', [$id])['cnt'] ?? 0);
        $dependentQuizCount = self::countActiveQuizDependenciesForModule($id);
        if ($dependentQuizCount > 0) {
            return ['success' => false, 'message' => "Module cannot be deleted because {$dependentQuizCount} in-progress quiz instance(s) still reference its questions or module. Submit or discard the in-progress quiz first."];
        }

        try {
            Database::transaction(function (PDO $pdo) use ($id): void {
                // Questions and subjects are removed by the existing SQLite cascades.
                $pdo->prepare('DELETE FROM subjects WHERE module_id = ?')->execute([$id]);
                $pdo->prepare('DELETE FROM modules WHERE id = ?')->execute([$id]);
            });
        } catch (Throwable $e) {
            error_log('Academic::deleteModule failed for module ' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'The module could not be deleted because a database error occurred. No changes were made.'];
        }

        return [
            'success' => true,
            'message' => "Module \"{$module['name']}\" and {$subjectCount} associated subject(s) were deleted successfully."
        ];
    }

    /**
     * Get subject count for a module
     */
    public static function getModuleSubjectCount(int $moduleId): int {
        $res = Database::fetchOne('SELECT COUNT(*) as cnt FROM subjects WHERE module_id = ?', [$moduleId]);
        return (int)($res['cnt'] ?? 0);
    }

    // ==========================================
    // SUBJECT OPERATIONS
    // ==========================================

    /**
     * Get all subjects, optionally filtered by module ID
     */
    public static function getAllSubjects(?int $moduleId = null): array {
        if ($moduleId !== null && $moduleId > 0) {
            $sql = "
                SELECT 
                    s.id,
                    s.module_id,
                    s.name,
                    s.description,
                    s.created_at,
                    m.name AS module_name
                FROM subjects s
                JOIN modules m ON m.id = s.module_id
                WHERE s.module_id = ?
                ORDER BY s.name ASC
            ";
            return Database::fetchAll($sql, [$moduleId]);
        }

        $sql = "
            SELECT 
                s.id,
                s.module_id,
                s.name,
                s.description,
                s.created_at,
                m.name AS module_name
            FROM subjects s
            JOIN modules m ON m.id = s.module_id
            ORDER BY m.name ASC, s.name ASC
        ";
        return Database::fetchAll($sql);
    }

    /**
     * Get a single subject by ID
     */
    public static function getSubjectById(int $id): ?array {
        $sql = "
            SELECT 
                s.id,
                s.module_id,
                s.name,
                s.description,
                s.created_at,
                m.name AS module_name
            FROM subjects s
            JOIN modules m ON m.id = s.module_id
            WHERE s.id = ?
        ";
        return Database::fetchOne($sql, [$id]);
    }

    /**
     * Create a new subject within a module
     */
    public static function createSubject(int $moduleId, string $name, ?string $description = null): array {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'Subject name is required.'];
        }

        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'message' => 'Subject name must be between 2 and 100 characters.'];
        }

        // Verify parent module exists
        $module = Database::fetchOne('SELECT id, name FROM modules WHERE id = ?', [$moduleId]);
        if (!$module) {
            return ['success' => false, 'message' => 'Selected parent module does not exist.'];
        }

        // Check duplicate name within the same module (case-insensitive)
        $existing = Database::fetchOne(
            'SELECT id FROM subjects WHERE module_id = ? AND name = ? COLLATE NOCASE',
            [$moduleId, $name]
        );
        if ($existing) {
            return ['success' => false, 'message' => "A subject named \"{$name}\" already exists in {$module['name']}."];
        }

        $now = date('Y-m-d H:i:s');
        Database::query(
            'INSERT INTO subjects (module_id, name, description, created_at) VALUES (?, ?, ?, ?)',
            [$moduleId, $name, $description, $now]
        );

        $id = (int)Database::lastInsertId();
        return ['success' => true, 'id' => $id, 'message' => 'Subject created successfully.'];
    }

    /**
     * Update an existing subject
     */
    public static function updateSubject(int $id, int $moduleId, string $name, ?string $description = null): array {
        $name = trim($name);
        $description = $description !== null ? trim($description) : null;
        if ($description === '') {
            $description = null;
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'Subject name is required.'];
        }

        if (strlen($name) < 2 || strlen($name) > 100) {
            return ['success' => false, 'message' => 'Subject name must be between 2 and 100 characters.'];
        }

        $subject = Database::fetchOne('SELECT id FROM subjects WHERE id = ?', [$id]);
        if (!$subject) {
            return ['success' => false, 'message' => 'Subject not found.'];
        }

        // Verify parent module exists
        $module = Database::fetchOne('SELECT id, name FROM modules WHERE id = ?', [$moduleId]);
        if (!$module) {
            return ['success' => false, 'message' => 'Selected parent module does not exist.'];
        }

        // Check duplicate name within the target module excluding current subject
        $existing = Database::fetchOne(
            'SELECT id FROM subjects WHERE module_id = ? AND name = ? COLLATE NOCASE AND id != ?',
            [$moduleId, $name, $id]
        );
        if ($existing) {
            return ['success' => false, 'message' => "Another subject named \"{$name}\" already exists in {$module['name']}."];
        }

        Database::query(
            'UPDATE subjects SET module_id = ?, name = ?, description = ? WHERE id = ?',
            [$moduleId, $name, $description, $id]
        );

        return ['success' => true, 'message' => 'Subject updated successfully.'];
    }

    /**
     * Delete a subject
     */
    public static function deleteSubject(int $id): array {
        $subject = Database::fetchOne('SELECT id, name FROM subjects WHERE id = ?', [$id]);
        if (!$subject) {
            return ['success' => false, 'message' => 'Subject not found.'];
        }
        $dependentQuizCount = self::countActiveQuizDependenciesForSubject($id);
        if ($dependentQuizCount > 0) {
            return ['success' => false, 'message' => "Subject cannot be deleted because {$dependentQuizCount} in-progress quiz instance(s) still reference its questions. Submit or discard the in-progress quiz first."];
        }

        try {
            Database::transaction(function (PDO $pdo) use ($id): void {
                // Questions belonging to the subject are removed by the existing SQLite cascade.
                $pdo->prepare('DELETE FROM subjects WHERE id = ?')->execute([$id]);
            });
        } catch (Throwable $e) {
            error_log('Academic::deleteSubject failed for subject ' . $id . ': ' . $e->getMessage());
            return ['success' => false, 'message' => 'The subject could not be deleted because a database error occurred. No changes were made.'];
        }
        return ['success' => true, 'message' => "Subject \"{$subject['name']}\" was deleted successfully."];
    }

    /**
     * Persisted quiz rows are in-progress by definition. Submission and
     * discard delete the quiz and all dependent rows transactionally.
     */
    private static function countActiveQuizDependenciesForSubject(int $subjectId): int {
        $row = Database::fetchOne(
            'SELECT COUNT(DISTINCT qq.quiz_id) AS cnt
             FROM quiz_questions qq
             JOIN quizzes qz ON qz.id = qq.quiz_id
             JOIN questions q ON q.id = qq.question_id
             WHERE q.subject_id = ?',
            [$subjectId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    private static function countActiveQuizDependenciesForModule(int $moduleId): int {
        $row = Database::fetchOne(
            'SELECT COUNT(DISTINCT qz.id) AS cnt
             FROM quizzes qz
             LEFT JOIN quiz_questions qq ON qq.quiz_id = qz.id
             LEFT JOIN questions q ON q.id = qq.question_id
             LEFT JOIN subjects s ON s.id = q.subject_id
             WHERE qz.module_id = ? OR s.module_id = ?',
            [$moduleId, $moduleId]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
