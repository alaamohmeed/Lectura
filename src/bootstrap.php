<?php
declare(strict_types=1);

/**
 * Lectura Bootstrap
 * Loads configuration, starts session, connects to DB, sets up i18n, and defines core helpers.
 */

$LC_IS_CLI = (php_sapi_name() === 'cli');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(__DIR__) . '/storage/logs/php_errors.log');

// Load configuration
$configPath = dirname(__DIR__) . '/config/config.php';
if (!file_exists($configPath)) {
    $examplePath = dirname(__DIR__) . '/config/config.example.php';
    if (file_exists($examplePath)) {
        http_response_code(500);
        exit('Configuration missing. Copy config/config.example.php to config/config.php and fill in your values.');
    }
    http_response_code(500);
    exit('Configuration file not found.');
}

$config = require $configPath;
$GLOBALS['config'] = $config;

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'UTC');

// Session configuration (skip for CLI)
if (!$LC_IS_CLI) {
    session_set_cookie_params([
        'lifetime' => $config['session']['lifetime'] ?? 2592000,
        'path' => '/',
        'domain' => '',
        'secure' => $config['session']['cookie_secure'] ?? true,
        'httponly' => $config['session']['cookie_httponly'] ?? true,
        'samesite' => $config['session']['cookie_samesite'] ?? 'Lax',
    ]);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Core files that do not need the DB at include time
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/live.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/i18n.php';

// Connect to database
try {
    $GLOBALS['pdo'] = db_connect($config['db']);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (!empty($config['app']['debug'])) {
        throw $e;
    }
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

// Auth (initializes $GLOBALS['current_user'] from the session)
require_once __DIR__ . '/auth.php';

// Remaining services
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/notify.php';

// Initialize i18n (now that the current user is known)
i18n_init();

// Security headers (set before any output)
if (!headers_sent() && !$LC_IS_CLI) {
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    $csp = "default-src 'self'; " .
           "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
           "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
           "font-src 'self' https://cdn.jsdelivr.net; " .
           "img-src 'self' data: https:; " .
           "connect-src 'self' https://wa.me; " .
           "frame-src https://wa.me;";
    header("Content-Security-Policy: {$csp}");
}

// Base URL helper
if (!function_exists('base_url')) {
    function base_url(string $path = ''): string
    {
        $base = rtrim($GLOBALS['config']['app']['base_url'] ?? '', '/');
        $path = ltrim($path, '/');
        return $path ? "{$base}/{$path}" : $base;
    }
}

// Asset URL helper
if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $url = base_url("assets/{$path}");
        // Cache-busting: assets are served with a long immutable cache lifetime.
        $file = __DIR__ . '/../public/assets/' . ltrim($path, '/');
        if (is_file($file)) {
            $url .= '?v=' . filemtime($file);
        }
        return $url;
    }
}

// Current URL helper
if (!function_exists('current_url')) {
    function current_url(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return "{$protocol}://{$host}{$uri}";
    }
}