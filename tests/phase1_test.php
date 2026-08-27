<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Phase 1 Foundation Automated Verification Test Suite
 */

declare(strict_types=1);

// Set test environment flags
define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/config.php';

class Phase1TestRunner {
    private int $passed = 0;
    private int $failed = 0;
    private array $errors = [];

    public function run(): void {
        echo "=======================================================\n";
        echo " DOT Bank - Phase 1 Foundation Verification Test Suite \n";
        echo " Doctors of Tomorrow Question Bank                     \n";
        echo "=======================================================\n\n";

        $this->testRequiredFiles();
        $this->testStorageSecurity();
        $this->testDatabaseSchemaAndInitialization();
        $this->testSetupLockBehavior();
        $this->testAdminCreationAndHashing();
        $this->testStudentRegistration();
        $this->testAuthenticationLogic();
        $this->testBruteForceThrottling();
        $this->testCsrfProtection();
        $this->testRoleGuardsAndSessionManagement();

        echo "\n-------------------------------------------------------\n";
        echo "Test Results: {$this->passed} Passed, {$this->failed} Failed\n";
        echo "-------------------------------------------------------\n";

        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->errors as $err) {
                echo "  [FAIL] {$err}\n";
            }
            exit(1);
        } else {
            echo "\n>>> ALL PHASE 1 TESTS PASSED SUCCESSFULLY! <<<\n\n";
            exit(0);
        }
    }

    private function assert(bool $condition, string $testName, string $failureDetails = ''): void {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$testName}\n";
        } else {
            $this->failed++;
            $msg = $testName . ($failureDetails ? " -> {$failureDetails}" : '');
            $this->errors[] = $msg;
            echo "  [FAIL] {$testName}\n";
        }
    }

    private function testRequiredFiles(): void {
        echo "--- 1. File Structure & Configuration ---\n";
        $files = [
            'config/config.php',
            'core/Database.php',
            'core/CSRF.php',
            'core/Auth.php',
            'core/View.php',
            'database/schema.sql',
            'storage/.htaccess',
            'public/assets/css/app.css',
            'public/assets/js/app.js',
            'public/setup.php',
            'public/login.php',
            'public/register.php',
            'public/logout.php',
            'public/index.php',
            'public/admin/dashboard.php',
            'public/student/dashboard.php',
            'views/layouts/main.php',
            'views/layouts/auth.php',
            'views/partials/header.php',
            'views/partials/nav.php',
            'views/partials/flash.php',
            'views/partials/footer.php',
            'views/auth/setup.php',
            'views/auth/login.php',
            'views/auth/register.php',
            'views/admin/dashboard.php',
            'views/student/dashboard.php',
        ];

        foreach ($files as $f) {
            $fullPath = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $f);
            $this->assert(file_exists($fullPath), "File exists: {$f}");
        }
    }

    private function testStorageSecurity(): void {
        echo "\n--- 2. Storage Directory Security ---\n";
        $htaccess = STORAGE_PATH . DIRECTORY_SEPARATOR . '.htaccess';
        $this->assert(file_exists($htaccess), 'storage/.htaccess exists');
        
        $content = file_get_contents($htaccess);
        $this->assert(
            str_contains($content, 'Require all denied') || str_contains($content, 'Deny from all'),
            'storage/.htaccess contains deny rules'
        );
    }

    private function testDatabaseSchemaAndInitialization(): void {
        echo "\n--- 3. SQLite Database Schema & Tables ---\n";
        
        // Reset DB connection and clean test database
        Database::reset();
        if (file_exists(DB_FILE)) {
            @unlink(DB_FILE);
        }
        if (file_exists(LOCK_FILE)) {
            @unlink(LOCK_FILE);
        }

        $schemaFile = DATABASE_PATH . '/schema.sql';
        $schemaSql = file_get_contents($schemaFile);
        $pdo = Database::getInstance();
        $pdo->exec($schemaSql);

        $tables = [
            'users',
            'modules',
            'subjects',
            'questions',
            'question_sources',
            'quizzes',
            'quiz_questions',
            'quiz_answers',
            'app_config',
        ];

        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$table}'");
            $this->assert($stmt->fetch() !== false, "Table exists: {$table}");
        }

        // Test Foreign Keys active
        $fkRes = $pdo->query('PRAGMA foreign_keys')->fetchColumn();
        $this->assert((int)$fkRes === 1, 'PRAGMA foreign_keys is enabled (= 1)');

        // Test WAL mode active
        $journalRes = strtolower((string)$pdo->query('PRAGMA journal_mode')->fetchColumn());
        $this->assert($journalRes === 'wal', "PRAGMA journal_mode is WAL (got '{$journalRes}')");
    }

    private function testSetupLockBehavior(): void {
        echo "\n--- 4. Setup Wizard & Lock System ---\n";
        $this->assert(!is_installed(), 'System reports not installed initially');

        // Create lock file
        $lockData = json_encode([
            'installed_at'   => date('c'),
            'platform_name'  => 'DOT Bank',
            'admin_username' => 'admin_test',
            'app_version'    => APP_VERSION,
        ]);
        file_put_contents(LOCK_FILE, $lockData);

        $this->assert(is_installed(), 'System reports installed when lock file and DB exist');
    }

    private function testAdminCreationAndHashing(): void {
        echo "\n--- 5. Admin Account Creation & Password Hashing ---\n";
        
        $adminPassword = 'AdminSecurePassword2026!';
        $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        
        Database::query(
            'INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?)',
            ['admin', $adminHash, 'admin', 'active', date('Y-m-d H:i:s')]
        );

        $admin = Database::fetchOne('SELECT * FROM users WHERE username = ?', ['admin']);
        $this->assert($admin !== null, 'Admin record found in users table');
        $this->assert($admin['role'] === 'admin', 'Admin role is strictly "admin"');
        $this->assert(password_verify($adminPassword, $admin['password_hash']), 'Admin password verified with password_verify()');
        $this->assert(!password_verify('WrongPassword', $admin['password_hash']), 'Wrong password rejected for admin');
    }

    private function testStudentRegistration(): void {
        echo "\n--- 6. Student Registration & Validation ---\n";
        
        // 1. Valid registration
        $res1 = Auth::registerStudent('student_jane', 'StudentPass123', 'StudentPass123');
        $this->assert($res1['success'] === true, 'Valid student registered successfully');

        $user = Database::fetchOne('SELECT * FROM users WHERE username = ?', ['student_jane']);
        $this->assert($user !== null, 'Student record exists in database');
        $this->assert($user['role'] === 'student', 'User role is strictly "student"');
        $this->assert($user['status'] === 'active', 'User status is "active"');

        // 2. Duplicate username registration attempt
        $resDup = Auth::registerStudent('student_jane', 'AnotherPass123', 'AnotherPass123');
        $this->assert($resDup['success'] === false, 'Duplicate username registration rejected');

        // 3. Case-insensitive duplicate check
        $resCaseDup = Auth::registerStudent('STUDENT_JANE', 'AnotherPass123', 'AnotherPass123');
        $this->assert($resCaseDup['success'] === false, 'Case-insensitive duplicate username rejected');

        // 4. Mismatched passwords
        $resMismatch = Auth::registerStudent('student_bob', 'Pass123', 'Pass456');
        $this->assert($resMismatch['success'] === false, 'Mismatched passwords rejected');

        // 5. Short password
        $resShortPass = Auth::registerStudent('student_bob', '12345', '12345');
        $this->assert($resShortPass['success'] === false, 'Short password (< 6 chars) rejected');

        // 6. Invalid username characters
        $resInvalidUser = Auth::registerStudent('student<script>', 'ValidPass123', 'ValidPass123');
        $this->assert($resInvalidUser['success'] === false, 'Invalid username characters rejected');
    }

    private function testAuthenticationLogic(): void {
        echo "\n--- 7. Authentication & Login Verification ---\n";
        
        // 1. Admin login
        $loginAdmin = Auth::attempt('admin', 'AdminSecurePassword2026!');
        $this->assert($loginAdmin['success'] === true, 'Admin login succeeded with valid credentials');
        $this->assert(Auth::check(), 'Auth::check() is true after login');
        $this->assert(Auth::isAdmin(), 'Auth::isAdmin() is true for admin');
        $this->assert(!Auth::isStudent(), 'Auth::isStudent() is false for admin');
        $this->assert(Auth::username() === 'admin', 'Auth::username() returns correct username');

        // 2. Logout
        Auth::logout();
        $this->assert(!Auth::check(), 'Auth::check() is false after Auth::logout()');

        // 3. Student login
        $loginStudent = Auth::attempt('student_jane', 'StudentPass123');
        $this->assert($loginStudent['success'] === true, 'Student login succeeded with valid credentials');
        $this->assert(Auth::check(), 'Auth::check() is true after student login');
        $this->assert(Auth::isStudent(), 'Auth::isStudent() is true for student');
        $this->assert(!Auth::isAdmin(), 'Auth::isAdmin() is false for student');

        // 4. Bad password attempt
        Auth::logout();
        $badPass = Auth::attempt('student_jane', 'IncorrectPass!');
        $this->assert($badPass['success'] === false, 'Login fails with wrong password');

        // 5. Nonexistent user
        $noUser = Auth::attempt('ghost_user', 'AnyPassword123');
        $this->assert($noUser['success'] === false, 'Login fails with non-existent username');

        // 6. Disabled account test
        Database::query("UPDATE users SET status = 'disabled' WHERE username = 'student_jane'");
        $disabledLogin = Auth::attempt('student_jane', 'StudentPass123');
        $this->assert($disabledLogin['success'] === false, 'Disabled account login rejected');
        $this->assert(str_contains($disabledLogin['message'], 'disabled'), 'Disabled account message returned');
        Database::query("UPDATE users SET status = 'active' WHERE username = 'student_jane'");
    }

    private function testBruteForceThrottling(): void {
        echo "\n--- 8. Brute-force Login Protection ---\n";
        
        Auth::logout();
        $_SESSION['login_attempts'] = 0;

        // Perform 5 consecutive failed attempts
        for ($i = 1; $i <= 5; $i++) {
            Auth::attempt('admin', 'WrongPassword' . $i);
        }

        // 6th attempt should be blocked by rate limiter
        $blocked = Auth::attempt('admin', 'AdminSecurePassword2026!');
        $this->assert($blocked['success'] === false, '6th login attempt throttled/locked out');
        $this->assert(str_contains($blocked['message'], 'Too many failed attempts'), 'Lockout message returned');

        // Reset for subsequent tests
        unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
    }

    private function testCsrfProtection(): void {
        echo "\n--- 9. CSRF Token Protection ---\n";
        
        $token = CSRF::getToken();
        $this->assert(!empty($token), 'CSRF token generated');
        $this->assert(strlen($token) === 64, 'CSRF token is 64 hex characters (32 bytes)');

        $field = CSRF::field();
        $this->assert(str_contains($field, 'type="hidden"') && str_contains($field, 'name="csrf_token"'), 'CSRF::field() generates valid HTML hidden input');

        $this->assert(CSRF::verify($token), 'CSRF::verify() accepts valid token');
        $this->assert(!CSRF::verify('invalid_token_12345'), 'CSRF::verify() rejects tampered token');
        $this->assert(!CSRF::verify(''), 'CSRF::verify() rejects empty token');
    }

    private function testRoleGuardsAndSessionManagement(): void {
        echo "\n--- 10. Role Guards & View Rendering ---\n";
        
        // Test flash messaging
        View::flash('success', 'Test Success Message');
        View::flash('error', 'Test Error Message');
        $flashes = View::getFlashes();
        $this->assert(count($flashes) === 2, 'View::flash() queues messages');
        $this->assert($flashes[0]['type'] === 'success', 'Flash type matches');
        
        // After getFlashes, queue must be empty
        $emptyFlashes = View::getFlashes();
        $this->assert(empty($emptyFlashes), 'Flash messages are cleared after retrieval');

        // Test HTML escape helper e()
        $xss = '<script>alert("xss")</script>&"\'';
        $escaped = e($xss);
        $this->assert(!str_contains($escaped, '<script>'), 'e() escapes HTML script tags');
        $this->assert(str_contains($escaped, '&lt;script&gt;'), 'e() converts to HTML entities');

        // Test URL generator helper url()
        $sampleUrl = url('login.php');
        $this->assert(str_ends_with($sampleUrl, '/login.php'), "url('login.php') generates valid URL: {$sampleUrl}");
    }
}

// Execute tests
$runner = new Phase1TestRunner();
$runner->run();
