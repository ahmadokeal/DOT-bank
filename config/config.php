<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Application Configuration & Bootstrap
 */

declare(strict_types=1);

// Prevent direct execution if script is run outside application context
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

define('CONFIG_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'config');
define('CORE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'core');
define('DATABASE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'database');
define('STORAGE_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'storage');
define('VIEWS_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'views');
define('PUBLIC_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'public');

define('DB_FILE', STORAGE_PATH . DIRECTORY_SEPARATOR . 'dot_bank.sqlite');
define('LOCK_FILE', STORAGE_PATH . DIRECTORY_SEPARATOR . 'installed.lock');

define('APP_NAME', 'DOT Bank');
define('APP_FULL_NAME', 'Doctors of Tomorrow Question Bank');
define('APP_VERSION', '1.0.0');

// Error reporting settings (can be modified for production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Determine Base URL dynamically
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', 'http://localhost/DOT%20Bank');
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        
        // Strip trailing subdirectories (public, admin, student) to reach root
        $basePath = preg_replace('#/(public|admin|student)(/.*)?$#i', '', $scriptDir);
        $basePath = rtrim($basePath, '/');
        
        define('BASE_URL', $protocol . $host . $basePath);
    }
}

// Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    if (!headers_sent()) {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_samesite', 'Lax');
    }
    @session_start();
}

// Autoload Core Classes
spl_autoload_register(function (string $class): void {
    $file = CORE_PATH . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper: Check if DOT Bank is installed
function is_installed(): bool {
    return file_exists(LOCK_FILE) && file_exists(DB_FILE);
}

// Helper: Secure URL generator
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return rtrim(BASE_URL, '/') . ($path !== '' ? '/' . $path : '');
}

// Helper: Safe HTML escape
function e(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
