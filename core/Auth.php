<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Authentication & Authorization Service
 */

declare(strict_types=1);

class Auth {
    private const SESSION_USER_ID = 'auth_user_id';
    private const SESSION_USER_ROLE = 'auth_user_role';
    private const SESSION_USER_NAME = 'auth_user_name';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 300; // 5 minutes lockout

    /**
     * Get the current authenticated user's ID
     */
    public static function id(): ?int {
        return isset($_SESSION[self::SESSION_USER_ID]) ? (int)$_SESSION[self::SESSION_USER_ID] : null;
    }

    /**
     * Check if a user is logged in
     */
    public static function check(): bool {
        return self::id() !== null;
    }

    /**
     * Get the current authenticated user record
     */
    public static function user(): ?array {
        $id = self::id();
        if (!$id) {
            return null;
        }

        return Database::fetchOne('SELECT id, username, role, status, created_at FROM users WHERE id = ?', [$id]);
    }

    /**
     * Get user role
     */
    public static function role(): ?string {
        return $_SESSION[self::SESSION_USER_ROLE] ?? null;
    }

    /**
     * Get username
     */
    public static function username(): ?string {
        return $_SESSION[self::SESSION_USER_NAME] ?? null;
    }

    /**
     * Check if logged in user is admin
     */
    public static function isAdmin(): bool {
        return self::check() && self::role() === 'admin';
    }

    /**
     * Check if logged in user is student
     */
    public static function isStudent(): bool {
        return self::check() && self::role() === 'student';
    }

    /**
     * Check rate limiting for login attempts
     */
    private static function checkRateLimit(): bool {
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttemptTime = $_SESSION['last_login_attempt'] ?? 0;

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            if (time() - $lastAttemptTime < self::LOCKOUT_TIME) {
                return false;
            }
            // Reset attempts after lockout period passes
            $_SESSION['login_attempts'] = 0;
        }

        return true;
    }

    /**
     * Record a failed login attempt
     */
    private static function recordFailedAttempt(): void {
        $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['last_login_attempt'] = time();
    }

    /**
     * Reset login attempts on successful login
     */
    private static function resetLoginAttempts(): void {
        unset($_SESSION['login_attempts'], $_SESSION['last_login_attempt']);
    }

    /**
     * Attempt login with username and password
     */
    public static function attempt(string $username, string $password): array {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $username = trim($username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Username and password are required.'];
        }

        if (!self::checkRateLimit()) {
            $remaining = self::LOCKOUT_TIME - (time() - ($_SESSION['last_login_attempt'] ?? 0));
            $minutes = max(1, ceil($remaining / 60));
            return ['success' => false, 'message' => "Too many failed attempts. Please try again in {$minutes} minute(s)."];
        }

        $user = Database::fetchOne('SELECT * FROM users WHERE username = ? COLLATE NOCASE', [$username]);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::recordFailedAttempt();
            return ['success' => false, 'message' => 'Invalid username or password.'];
        }

        if (($user['status'] ?? 'active') !== 'active') {
            return ['success' => false, 'message' => 'This account has been disabled. Please contact the administrator.'];
        }

        // Login successful - prevent session fixation safely
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        self::resetLoginAttempts();

        $_SESSION[self::SESSION_USER_ID] = (int)$user['id'];
        $_SESSION[self::SESSION_USER_ROLE] = $user['role'];
        $_SESSION[self::SESSION_USER_NAME] = $user['username'];

        // Regenerate CSRF token
        CSRF::regenerate();

        return ['success' => true, 'user' => $user];
    }

    /**
     * Register a new student account
     */
    public static function registerStudent(string $username, string $password, string $confirmPassword): array {
        $username = trim($username);

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (strlen($username) < 3 || strlen($username) > 30) {
            return ['success' => false, 'message' => 'Username must be between 3 and 30 characters.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
            return ['success' => false, 'message' => 'Username may only contain letters, numbers, dots, hyphens, and underscores.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters long.'];
        }

        if ($password !== $confirmPassword) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        // Check if username is already taken
        $existing = Database::fetchOne('SELECT id FROM users WHERE username = ? COLLATE NOCASE', [$username]);
        if ($existing) {
            return ['success' => false, 'message' => 'Username is already taken. Please choose another.'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $now = date('Y-m-d H:i:s');

        Database::query(
            'INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?)',
            [$username, $hash, 'student', 'active', $now]
        );

        return ['success' => true, 'message' => 'Account created successfully! You can now log in.'];
    }

    /**
     * Log out current user
     */
    public static function logout(): void {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!headers_sent() && ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            @session_destroy();
        }
    }

    /**
     * Guard: Require user to be logged in
     */
    public static function requireLogin(): void {
        if (!self::check()) {
            View::flash('error', 'Please log in to access this page.');
            header('Location: ' . url('login.php'));
            exit;
        }
    }

    /**
     * Guard: Require user to be admin
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            View::flash('error', 'Access denied. Administrator privilege required.');
            header('Location: ' . url('student/dashboard.php'));
            exit;
        }
    }

    /**
     * Guard: Require user to be student
     */
    public static function requireStudent(): void {
        self::requireLogin();
        if (!self::isStudent()) {
            // If admin accesses student area, redirect to admin dashboard
            header('Location: ' . url('admin/dashboard.php'));
            exit;
        }
    }
}
