<?php
/**
 * Language switcher endpoint
 * GET /setlang.php?lang=ar|en&redirect=<url>
 */
require_once __DIR__ . '/../src/bootstrap.php';

$lang = $_GET['lang'] ?? '';
$redirect = $_GET['redirect'] ?? '';

if (!in_array($lang, ['ar', 'en'], true)) {
    http_response_code(400);
    exit('Invalid language');
}

i18n_switch($lang);

// Validate redirect: only allow same-origin URLs/paths to prevent open redirects
$target = base_url('index.php');
if ($redirect !== '') {
    $decoded = rawurldecode($redirect);
    $host = parse_url($decoded, PHP_URL_HOST);
    $currentHost = parse_url(base_url(), PHP_URL_HOST);
    if ($host === null) {
        // Relative path - only allow starting with /
        if (str_starts_with($decoded, '/') && !str_starts_with($decoded, '//')) {
            $target = $decoded;
        }
    } elseif ($host === $currentHost) {
        $target = $decoded;
    }
}

header('Location: ' . $target);
exit;