<?php
/**
 * Registration via invite link
 * /register.php?token=...
 */
require_once __DIR__ . '/../src/bootstrap.php';

// If already logged in, go home
if (auth_user()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$errors = [];
$invite = null;

// Validate token before showing the form
if ($token !== '') {
    $invite = db_select_one(
        'SELECT * FROM invites WHERE token = ? AND used_by IS NULL AND expires_at > NOW()',
        [$token]
    );
}

$inviteRole = in_array(($invite['role'] ?? 'student'), ['student', 'lecturer'], true)
    ? $invite['role']
    : 'student';
$old = ['name' => '', 'email' => ''];

if (is_post()) {
    csrf_protect();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $old = ['name' => $name, 'email' => $email];

    if (!$invite) {
        $errors[] = t('invite.invalid_or_expired');
    }
    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = t('auth.name') . ': ' . t('common.required');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = t('auth.email') . ': ' . t('common.required');
    }
    if (strlen($password) < 8) {
        $errors[] = t('user.weak') . ' — min 8';
    }
    if ($password !== $confirm) {
        $errors[] = t('auth.confirm_password');
    }

    if (!$errors && $invite) {
        $result = auth_register($token, $name, $email, $password);
        if ($result['success']) {
            redirect_with(base_url('index.php'), 'success', t('auth.register') . ' ✓');
        }
        $errors[] = $result['error'];
    }
}

$pageTitle = t('invite.register_title') . ' — ' . t('app.name');
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

            <?php if (!$invite): ?>
                <div class="text-center py-3">
                    <i class="bi bi-shield-exclamation text-danger" style="font-size:48px" aria-hidden="true"></i>
                    <h1 class="h4 mt-3"><?= e(t('invite.invalid_or_expired')) ?></h1>
                    <p class="text-muted"><?= e(t('auth.need_access')) ?></p>
                    <a href="<?= base_url('login.php') ?>" class="btn btn-primary mt-2"><?= e(t('auth.login')) ?></a>
                </div>
            <?php else: ?>
                <h1 class="h4 text-center mb-1"><?= e(t('invite.register_title')) ?></h1>
                <p class="auth-tagline mb-2"><?= e(t('invite.register_desc')) ?></p>
                <p class="text-center mb-4">
                    <span class="badge-type <?= e(role_badge_class($inviteRole)) ?>">
                        <?= e(t('invite.role')) ?>: <?= e(t('role.' . $inviteRole)) ?>
                    </span>
                </p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger" role="alert" aria-live="assertive">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= base_url('register.php') ?>" novalidate>
                    <?= csrf_field() ?>
                    <input type="hidden" name="token" value="<?= ea($token) ?>">

                    <div class="mb-3">
                        <label for="name" class="form-label"><?= e(t('auth.name')) ?></label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="100"
                               value="<?= ea($old['name']) ?>" required autocomplete="name">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label"><?= e(t('auth.email')) ?></label>
                        <input type="email" class="form-control" id="email" name="email" maxlength="150"
                               value="<?= ea($old['email']) ?>" required autocomplete="email" inputmode="email">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><?= e(t('auth.password')) ?></label>
                        <div class="input-icon-wrap has-end">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input type="password" class="form-control" id="password" name="password"
                                   required minlength="8" autocomplete="new-password"
                                   data-strength-input>
                            <button type="button" class="btn-icon-end" data-toggle-password="#password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="strength-meter" data-strength-meter data-score="0" aria-hidden="true">
                            <span></span><span></span><span></span><span></span>
                        </div>
                        <div class="form-text" data-strength-label></div>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label"><?= e(t('auth.confirm_password')) ?></label>
                        <div class="input-icon-wrap has-end">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required minlength="8" autocomplete="new-password">
                            <button type="button" class="btn-icon-end" data-toggle-password="#confirm_password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg">
                        <i class="bi bi-person-plus"></i>
                        <span class="btn-label"><?= e(t('auth.register')) ?></span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php require __DIR__ . '/../src/layout/client-config.php'; ?>
    <script src="<?= e(asset_url('js/app.js')) ?>"></script>
</body>
</html>
