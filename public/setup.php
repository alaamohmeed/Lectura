<?php
/**
 * One-time setup script — creates the first admin account.
 * Protected by config['app']['setup_secret'].
 *
 * After completing setup, DELETE THIS FILE or set setup_secret to null.
 */
require_once __DIR__ . '/../src/bootstrap.php';

$adminExists = (bool)db_select_one('SELECT id FROM users WHERE role = "admin" LIMIT 1');
$setupSecret = $GLOBALS['config']['app']['setup_secret'] ?? null;

// Disabled after first admin exists or if secret not configured
if ($adminExists || !$setupSecret) {
    http_response_code(404);
    exit('Setup is disabled.');
}

$errors = [];
$done = false;

if (is_post()) {
    csrf_protect();
    $secret = $_POST['secret'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!hash_equals($setupSecret, $secret)) {
        $errors[] = 'Invalid setup secret.';
    }
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Name is required.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (db_select_one('SELECT id FROM users WHERE email = ?', [$email])) {
        $errors[] = 'This email is already registered.';
    }

    if (!$errors) {
        db_insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'admin',
            'lang' => 'ar',
            'theme' => 'light',
            'is_active' => 1,
            'email_notify' => 1,
            'created_at' => utc_now(),
        ]);
        $done = true;
    }
}

$pageTitle = 'Setup — Lectura';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link href="<?= e(bootstrap_css_url()) ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preload" href="<?= e(asset_url('fonts/ibm-plex-sans-arabic/arabic-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= e(asset_url('css/lectura.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="auth-wrap">
    <div class="auth-card">
        <div class="auth-logo mb-3">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M4 12.5A2.5 2.5 0 0 1 6.5 10H20"/>
                <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20"/>
            </svg>
            <span>Lectura</span>
        </div>

        <?php if ($done): ?>
            <div class="text-center">
                <div class="success-check mx-auto" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
                <h1 class="h4">Setup complete</h1>
                <p class="text-muted">The first admin account has been created.</p>
                <div class="alert alert-warning text-start">
                    <strong>Important:</strong> delete <code>public/setup.php</code> now.
                </div>
                <a href="<?= base_url('login.php') ?>" class="btn btn-primary w-100">Go to login</a>
            </div>
        <?php else: ?>
            <h1 class="h4 text-center mb-1">First-time setup</h1>
            <p class="text-muted text-center mb-4">Create the admin account</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('setup.php') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="secret">Setup secret (from config/config.php)</label>
                    <input type="password" class="form-control" id="secret" name="secret" required autocomplete="off">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="name">Admin name</label>
                    <input type="text" class="form-control" id="name" name="name" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password (min 8)</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8" required autocomplete="new-password">
                </div>
                <div class="mb-4">
                    <label class="form-label" for="confirm_password">Confirm password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">Create admin</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
