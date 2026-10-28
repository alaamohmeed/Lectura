<?php
/**
 * Theme persistence endpoint (AJAX, JSON)
 * POST /settheme.php  { "theme": "light|dark|auto" }
 */
require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

if (!csrf_validate($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'error' => 'Invalid CSRF token']));
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$theme = $input['theme'] ?? '';

if (!in_array($theme, ['light', 'dark', 'auto'], true)) {
    http_response_code(422);
    exit(json_encode(['success' => false, 'error' => 'Invalid theme']));
}

$user = auth_user();
if (!$user) {
    // For guests the theme is stored client-side only; accept silently
    exit(json_encode(['success' => true, 'guest' => true]));
}

db_update('users', ['theme' => $theme], 'id = ?', [$user['id']]);
exit(json_encode(['success' => true]));