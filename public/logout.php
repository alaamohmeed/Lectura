<?php
/**
 * Logout endpoint (POST only, CSRF protected)
 */
require_once __DIR__ . '/../src/bootstrap.php';

if (is_post()) {
    csrf_protect();
    auth_logout();
}

header('Location: ' . base_url('login.php'));
exit;