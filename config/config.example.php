<?php
/**
 * Lectura Configuration Template
 * Copy this file to config.php and fill in your values.
 * Never commit config.php to version control.
 */

return [
    // Base URL of the application (no trailing slash)
    'app' => [
        'base_url' => 'https://yourname.alwaysdata.net',
        'name' => 'Lectura',
        'default_lang' => 'ar',
        // Used for date display and due-date input; database storage is UTC.
        'timezone' => 'UTC',
        'debug' => false, // Set to true only for local development
        'setup_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_STRING',
    ],

    // Database configuration (alwaysdata uses a dedicated MySQL host, not localhost)
    // All timestamps are stored in UTC; no database timezone setting is required.
    'db' => [
        'host' => 'mysql-yourname.alwaysdata.net',
        'name' => 'yourname_lectura',
        'user' => 'yourname_lectura',
        'pass' => 'your_database_password',
        'charset' => 'utf8mb4',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ],
    ],

    // SMTP configuration for PHPMailer (email notifications)
    // Set 'use_smtp' to true after filling in real credentials.
    // When false (or Composer/PHPMailer is unavailable), PHP mail() is used.
    'mail' => [
        'use_smtp' => false,
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'noreply@example.com',
        'password' => 'your_smtp_password',
        'encryption' => 'tls', // 'tls', 'ssl', or ''
        'from_email' => 'noreply@example.com',
        'from_name' => 'Lectura',
    ],

    // File upload configuration
    'upload' => [
        'path' => __DIR__ . '/../storage/uploads', // Outside public web root
        'max_size' => 10 * 1024 * 1024, // 10 MB in bytes
        'allowed_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'image/jpeg',
            'image/png',
            'application/zip',
        ],
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip'],
    ],

    // Invite token configuration
    'invite' => [
        'token_length' => 32, // bytes, results in 64 hex chars
        'expiry_days' => 7,
    ],

    // Login rate limiting
    'auth' => [
        'max_failed_attempts' => 5,
        'lockout_minutes' => 10,
    ],

    // Storage warning threshold (percentage)
    'storage' => [
        'quota' => 100 * 1024 * 1024, // dashboard meter estimate, not a host-enforced quota
        'warning_percent' => 80,
    ],

    // Session configuration
    'session' => [
        'lifetime' => 3600 * 24 * 30, // 30 days
        'cookie_secure' => true, // Set to false for local HTTP development
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
];