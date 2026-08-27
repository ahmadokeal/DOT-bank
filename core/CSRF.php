<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * CSRF Protection Helper
 */

declare(strict_types=1);

class CSRF {
    private const SESSION_KEY = '_csrf_token';

    /**
     * Generate or retrieve existing CSRF token
     */
    public static function getToken(): string {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Render a hidden input field with the CSRF token
     */
    public static function field(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
    }

    /**
     * Verify submitted CSRF token from POST or headers
     */
    public static function verify(?string $token = null): bool {
        if ($token === null) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerate(): string {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        return $_SESSION[self::SESSION_KEY];
    }
}
