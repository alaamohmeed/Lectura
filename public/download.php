<?php
/**
 * Protected file download and preview
 * GET /download.php?id=<file_id>[&preview=1]
 *
 * Verifies: logged-in + active user. Files live outside the web root.
 */
require_once __DIR__ . '/../src/bootstrap.php';

$user = auth_user();
if (!$user) {
    http_response_code(403);
    // For a direct file request, send a friendly minimal page
    exit('403 — ' . t('auth.forbidden'));
}

if (!$user['is_active']) {
    http_response_code(403);
    exit('403 — ' . t('auth.forbidden'));
}

$fileId = (int)($_GET['id'] ?? 0);
$preview = !empty($_GET['preview']);

if ($fileId <= 0) {
    http_response_code(400);
    exit('Bad request');
}

$file = db_select_one('SELECT * FROM files WHERE id = ?', [$fileId]);
if (!$file) {
    http_response_code(404);
    exit('File not found');
}

$uploadPath = $GLOBALS['config']['upload']['path'] ?? dirname(__DIR__) . '/storage/uploads';
$filePath = $uploadPath . '/' . basename($file['stored_name']);

if (!is_file($filePath)) {
    http_response_code(404);
    error_log("Missing upload file on disk: {$file['stored_name']}");
    exit('File not found');
}

$allowedPreviewMimes = ['application/pdf', 'image/jpeg', 'image/png'];
$serveInline = $preview && in_array($file['mime'], $allowedPreviewMimes, true);

// Rate limiting: track downloads per session per minute (simple)
$now = time();
$_SESSION['download_times'] = array_filter(
    $_SESSION['download_times'] ?? [],
    fn($t) => $t > $now - 60
);
if (count($_SESSION['download_times']) > 60) {
    http_response_code(429);
    exit('Too many requests');
}
$_SESSION['download_times'][] = $now;

// Build safe filename for headers
$original = $file['original_name'];
$asciiFallback = preg_replace('/[^\x20-\x7E]/', '_', $original);
$asciiFallback = str_replace(['"', '\\'], '_', $asciiFallback);
if ($asciiFallback === '' || $asciiFallback === null) {
    $asciiFallback = 'file';
}
$encodedName = rawurlencode($original);

$disposition = $serveInline ? 'inline' : 'attachment';

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . ($serveInline ? $file['mime'] : 'application/octet-stream'));
header('Content-Length: ' . filesize($filePath));
header("Content-Disposition: {$disposition}; filename=\"{$asciiFallback}\"; filename*=UTF-8''{$encodedName}");
header('Cache-Control: private, max-age=1800');
header('Accept-Ranges: bytes');

// Simple range support (helps PDF viewers)
$size = filesize($filePath);
$start = 0;
$end = $size - 1;

if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    if ($m[1] !== '') $start = (int)$m[1];
    if ($m[2] !== '') $end = (int)$m[2];
    if ($start > $end || $start >= $size) {
        header('Content-Range: bytes */' . $size);
        http_response_code(416);
        exit;
    }
    $end = min($end, $size - 1);
    http_response_code(206);
    header("Content-Range: bytes {$start}-{$end}/{$size}");
    header('Content-Length: ' . ($end - $start + 1));
}

$fp = fopen($filePath, 'rb');
if (!$fp) {
    http_response_code(500);
    exit('Unable to read file');
}

fseek($fp, $start);
$remaining = $end - $start + 1;
while ($remaining > 0 && !feof($fp)) {
    $chunk = fread($fp, min(8192, $remaining));
    if ($chunk === false) break;
    echo $chunk;
    $remaining -= strlen($chunk);
    if (function_exists('fastcgi_finish_request')) {
        // Let output flush naturally; not calling finish here
    }
    flush();
}
fclose($fp);
exit;