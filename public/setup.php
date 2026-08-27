<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * Setup Wizard (Initialization)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

// Check if already installed
if (is_installed()) {
    View::flash('info', 'DOT Bank is already installed and configured.');
    header('Location: ' . url('login.php'));
    exit;
}

$errors = [];
$platformName = 'DOT Bank';
$adminUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::verify()) {
        View::flash('error', 'Security token expired. Please try again.');
    } else {
        $platformName = trim($_POST['platform_name'] ?? 'DOT Bank');
        $adminUsername = trim($_POST['admin_username'] ?? '');
        $adminPassword = $_POST['admin_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($platformName)) {
            $errors[] = 'Platform name cannot be empty.';
        }

        if (empty($adminUsername) || strlen($adminUsername) < 3 || strlen($adminUsername) > 30) {
            $errors[] = 'Admin username must be between 3 and 30 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $adminUsername)) {
            $errors[] = 'Admin username may only contain letters, numbers, dots, hyphens, and underscores.';
        }

        if (empty($adminPassword) || strlen($adminPassword) < 6) {
            $errors[] = 'Admin password must be at least 6 characters long.';
        } elseif ($adminPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            try {
                // Ensure storage directory exists
                if (!is_dir(STORAGE_PATH)) {
                    mkdir(STORAGE_PATH, 0755, true);
                }

                // Initialize Database schema
                $schemaFile = DATABASE_PATH . '/schema.sql';
                if (!file_exists($schemaFile)) {
                    throw new RuntimeException('Database schema file not found.');
                }

                $schemaSql = file_get_contents($schemaFile);
                $pdo = Database::getInstance();
                $pdo->exec($schemaSql);

                // Insert App Config & Admin User inside a transaction
                Database::transaction(function (PDO $pdo) use ($platformName, $adminUsername, $adminPassword): void {
                    // Set platform name
                    $stmtConfig = $pdo->prepare('INSERT OR REPLACE INTO app_config (key, value, updated_at) VALUES (?, ?, ?)');
                    $stmtConfig->execute(['platform_name', $platformName, date('Y-m-d H:i:s')]);
                    $stmtConfig->execute(['installed_at', date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]);

                    // Insert Admin user
                    $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmtUser = $pdo->prepare('INSERT INTO users (username, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?)');
                    $stmtUser->execute([$adminUsername, $hash, 'admin', 'active', date('Y-m-d H:i:s')]);
                });

                // Write lock file
                $lockContent = json_encode([
                    'installed_at'   => date('c'),
                    'platform_name'  => $platformName,
                    'admin_username' => $adminUsername,
                    'app_version'    => APP_VERSION,
                ], JSON_PRETTY_PRINT);
                file_put_contents(LOCK_FILE, $lockContent);

                View::flash('success', 'Installation completed successfully! Please sign in with your administrator account.');
                header('Location: ' . url('login.php'));
                exit;
            } catch (Throwable $e) {
                $errors[] = 'Installation error: ' . $e->getMessage();
            }
        }

        foreach ($errors as $error) {
            View::flash('error', $error);
        }
    }
}

View::render('auth/setup', [
    'pageTitle'     => 'Initial Setup Wizard',
    'platformName'  => $platformName,
    'adminUsername' => $adminUsername,
], 'auth');
