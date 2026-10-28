<?php
/**
 * Lightweight live-update version endpoint.
 *
 * Authenticated pages poll this every few seconds. The response is tiny; the
 * page itself is fetched only after the content version changes.
 */
require_once __DIR__ . '/../../src/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Robots-Tag: noindex');

$user = auth_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthenticated']);
    exit;
}

$since = isset($_GET['since']) ? max(0, (int)$_GET['since']) : 0;
$version = live_version();

echo json_encode([
    'ok' => true,
    'version' => $version,
    'changed' => $version !== $since,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
