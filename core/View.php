<?php
/**
 * DOT Bank - Doctors of Tomorrow Question Bank
 * View & Template Rendering Engine
 */

declare(strict_types=1);

class View {
    private const SESSION_FLASH_KEY = '_flash_messages';

    /**
     * Set a flash message
     */
    public static function flash(string $type, string $message): void {
        if (!isset($_SESSION[self::SESSION_FLASH_KEY])) {
            $_SESSION[self::SESSION_FLASH_KEY] = [];
        }
        $_SESSION[self::SESSION_FLASH_KEY][] = [
            'type'    => $type, // 'success', 'error', 'warning', 'info'
            'message' => $message,
        ];
    }

    /**
     * Get and clear all flash messages
     */
    public static function getFlashes(): array {
        $flashes = $_SESSION[self::SESSION_FLASH_KEY] ?? [];
        unset($_SESSION[self::SESSION_FLASH_KEY]);
        return $flashes;
    }

    /**
     * Render a view file with an optional layout
     */
    public static function render(string $viewPath, array $data = [], ?string $layout = 'main'): void {
        // Extract data into local variables
        extract($data);

        // Capture view content into buffer
        ob_start();
        $fullViewPath = VIEWS_PATH . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $viewPath) . '.php';
        if (file_exists($fullViewPath)) {
            require $fullViewPath;
        } else {
            echo "<div class='alert alert-error'>View [{$viewPath}] not found.</div>";
        }
        $content = ob_get_clean();

        // If no layout is specified, render content directly
        if ($layout === null) {
            echo $content;
            return;
        }

        // Render layout wrapping content
        $layoutPath = VIEWS_PATH . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.php';
        if (file_exists($layoutPath)) {
            require $layoutPath;
        } else {
            echo $content;
        }
    }
}
