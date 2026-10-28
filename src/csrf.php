<?php
/**
 * CSRF Protection
 * Generates and validates CSRF tokens for form submissions
 */

// Generate and store CSRF token in session
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validate CSRF token
if (!function_exists('csrf_validate')) {
    function csrf_validate(?string $token): bool
    {
        if (!$token) {
            return false;
        }
        $stored = $_SESSION['csrf_token'] ?? '';
        if (!$stored) {
            return false;
        }
        // Use hash_equals for timing-safe comparison
        return hash_equals($stored, $token);
    }
}

// Regenerate CSRF token (e.g., after login)
if (!function_exists('csrf_regenerate')) {
    function csrf_regenerate(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }
}

// Middleware to check CSRF on POST requests
if (!function_exists('csrf_protect')) {
    function csrf_protect(): void
    {
        if (is_post()) {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validate($token)) {
                http_response_code(403);
                if (is_ajax()) {
                    header('Content-Type: application/json');
                    exit(json_encode(['error' => t('csrf.invalid'), 'code' => 'CSRF_INVALID']));
                }
                exit(t('csrf.invalid'));
            }
        }
    }
}