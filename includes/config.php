<?php
// includes/config.php

// Base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', realpath(__DIR__ . '/../') . '/');
}

// Simple .env loader
$envFile = BASE_PATH . '.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}

// Fallback to defaults if not defined in .env
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_PORT')) define('DB_PORT', '5432');
if (!defined('DB_USER')) define('DB_USER', 'postgres');
if (!defined('DB_PASS')) define('DB_PASS', 'postgres123');
if (!defined('DB_NAME')) define('DB_NAME', 'assetmanagement');
if (!defined('DEV_MODE')) define('DEV_MODE', true);

define('UPLOAD_DIR', BASE_PATH . 'public/uploads/');

if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0777, true);
}
?>