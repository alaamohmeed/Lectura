<?php
/**
 * Login page
 */
require_once __DIR__ . '/../src/bootstrap.php';

// If already logged in, go home
if (auth_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$errors = [];
$oldEmail = '';

if (is_post()) {
    csrf_protect();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $oldEmail = $email;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = t('auth.email') . ': ' . t('common.required');
    }
    if ($password === '') {
        $errors[] = t('auth.password') . ': ' . t('common.required');
    }

    if (!$errors) {
        $result = auth_login($email, $password);
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? base_url('index.php');
            unset($_SESSION['redirect_after_login']);
            // Prevent open redirect
            $host = parse_url($redirect, PHP_URL_HOST);
            $currentHost = parse_url(base_url(), PHP_URL_HOST);
            if ($host !== null && $host !== $currentHost) {
                $redirect = base_url('index.php');
            }
            header('Location: ' . $redirect);
            exit;
        }
        $errors[] = $result['error'];
    }
}

$pageTitle = t('auth.login') . ' — ' . t('app.name');
$bodyClass = 'auth-page';
?>
<!DOCTYPE html>
<html lang="<?= e(current_lang()) ?>" dir="<?= e(current_dir()) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Theme initialization (runs before paint to avoid flash) -->
    <script>
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('theme'); } catch (e) {}
            var mode = stored || 'light';
            var effective = mode;
            if (mode !== 'light' && mode !== 'dark') {
                effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', effective);
            document.documentElement.setAttribute('data-lc-theme', mode);
        })();
    </script>
    <title><?= e($pageTitle) ?></title>
    <link href="<?= e(bootstrap_css_url()) ?>" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preload" href="<?= e(asset_url('fonts/ibm-plex-sans-arabic/arabic-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(asset_url('fonts/inter/latin-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link href="<?= e(asset_url('css/lectura.css')) ?>" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?= e(asset_url('img/favicon.svg')) ?>">
</head>
<body>
    <!-- Corner controls -->
    <div class="auth-corner">
        <div class="dropdown">
            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= ea(t('lang.switch')) ?>">
                <?php $langs = i18n_languages(); $cur = $langs[current_lang()] ?? $langs['ar']; ?>
                <i class="bi bi-globe2" aria-hidden="true"></i>
                <span><?= e($cur['native']) ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <?php foreach ($langs as $code => $l): ?>
                    <li><a class="dropdown-item<?= $code === current_lang() ? ' active' : '' ?>" href="<?= base_url('setlang.php') ?>?lang=<?= e($code) ?>&redirect=<?= eu(current_url()) ?>"><?= e($l['flag']) ?> <?= e($l['native']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" id="themeToggle" aria-label="<?= ea(t('theme.toggle')) ?>">
            <i class="bi bi-sun" data-theme="light"></i><i class="bi bi-moon-stars" data-theme="dark"></i><i class="bi bi-circle-half" data-theme="auto"></i>
        </button>
    </div>

    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-logo mb-2">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M4 12.5A2.5 2.5 0 0 1 6.5 10H20"/>
                    <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20"/>
                </svg>
                <span>Lectura</span>
            </div>
            <p class="auth-tagline mb-4"><?= e(t('app.tagline')) ?></p>

            <?php if ($errors): ?>
                <div class="alert alert-danger" role="alert" aria-live="assertive">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= base_url('login.php') ?>" novalidate>
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="email" class="form-label"><?= e(t('auth.email')) ?></label>
                    <div class="input-icon-wrap">
                        <i class="bi bi-envelope" aria-hidden="true"></i>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= ea($oldEmail) ?>" required autocomplete="email"
                               inputmode="email" placeholder="name@example.com">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label"><?= e(t('auth.password')) ?></label>
                    <div class="input-icon-wrap has-end">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                        <input type="password" class="form-control" id="password" name="password"
                               required autocomplete="current-password" placeholder="••••••••">
                        <button type="button" class="btn-icon-end" data-toggle-password="#password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span class="btn-label"><?= e(t('auth.login')) ?></span>
                </button>
            </form>

            <p class="auth-footer-note mb-0"><?= e(t('auth.need_access')) ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../src/layout/client-config.php'; ?>
    <script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
