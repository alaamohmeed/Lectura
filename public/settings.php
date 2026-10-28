<?php
/**
 * Student settings: profile, language, theme, notifications, password
 */
require_once __DIR__ . '/../src/bootstrap.php';

$user = auth_require();
$errors = [];

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? 'preferences';

    if ($action === 'preferences') {
        $name = trim($_POST['name'] ?? '');
        $lang = $_POST['lang'] ?? current_lang();
        $theme = $_POST['theme'] ?? 'light';
        $emailNotify = isset($_POST['email_notify']) ? 1 : 0;

        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = t('auth.name') . ': ' . t('common.required');
        }
        if (!in_array($lang, ['ar', 'en'], true)) $lang = 'ar';
        if (!in_array($theme, ['light', 'dark', 'auto'], true)) $theme = 'light';

        if (!$errors) {
            db_update('users', [
                'name' => $name,
                'lang' => $lang,
                'theme' => $theme,
                'email_notify' => $emailNotify,
            ], 'id = ?', [$user['id']]);

            // Keep session + i18n in sync
            $_SESSION['user_name'] = $name;
            if ($lang !== current_lang()) {
                i18n_switch($lang);
            }
            redirect_with(base_url('settings.php'), 'success', t('settings.saved'));
        }
    } elseif ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($new !== $confirm) {
            $errors[] = t('auth.confirm_password');
        } elseif (strlen($new) < 8) {
            $errors[] = t('user.weak') . ' — min 8';
        } elseif ($current === '') {
            $errors[] = t('auth.password') . ': ' . t('common.required');
        } else {
            $result = auth_change_password($user['id'], $current, $new);
            if ($result['success']) {
                redirect_with(base_url('settings.php'), 'success', t('auth.password_changed'));
            }
            $errors[] = $result['error'];
        }
    }
}

// Refresh user data
$user = auth_user();

$pageTitle = t('nav.settings') . ' — ' . t('app.name');
require __DIR__ . '/../src/layout/header.php';
?>

<div class="row g-4">
    <div class="col-12 col-lg-3">
        <div class="lc-card">
            <div class="lc-card-body text-center">
                <div class="avatar avatar-lg mx-auto mb-3"><?= e(mb_substr($user['name'], 0, 1)) ?></div>
                <h1 class="h5 mb-1 lc-wrap"><?= e($user['name']) ?></h1>
                <p class="item-meta mb-2 lc-wrap"><?= e($user['email']) ?></p>
                <span class="badge-type <?= e(role_badge_class($user['role'])) ?>">
                    <?= e(t("role.{$user['role']}")) ?>
                </span>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-9">
        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <!-- Profile / language / theme / notifications -->
        <form method="post" action="<?= base_url('settings.php') ?>" class="lc-card mb-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="preferences">
            <div class="lc-card-body">
                <h2 class="h5 mb-3"><i class="bi bi-person-gear text-primary" aria-hidden="true"></i> <?= e(t('settings.profile')) ?></h2>

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="name" class="form-label"><?= e(t('auth.name')) ?></label>
                        <input type="text" class="form-control" id="name" name="name" maxlength="100"
                               value="<?= ea($user['name']) ?>" required autocomplete="name">
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="email" class="form-label"><?= e(t('auth.email')) ?></label>
                        <input type="email" class="form-control" id="email" value="<?= ea($user['email']) ?>" disabled>
                        <div class="form-text"><?= e(t('auth.email')) ?> — <?= e(t('common.optional')) ?></div>
                    </div>
                </div>

                <hr class="my-4">

                <h2 class="h5 mb-3"><i class="bi bi-translate text-primary" aria-hidden="true"></i> <?= e(t('settings.language')) ?></h2>
                <div class="row g-3">
                    <?php foreach (i18n_languages() as $code => $l): ?>
                        <div class="col-6 col-md-4">
                            <label class="lc-card p-3 d-flex align-items-center gap-2 w-100" style="cursor:pointer">
                                <input class="form-check-input m-0" type="radio" name="lang" value="<?= e($code) ?>"
                                       <?= ($user['lang'] ?? current_lang()) === $code ? 'checked' : '' ?>>
                                <span><?= e($l['flag']) ?> <?= e($l['native']) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <h2 class="h5 mb-3"><i class="bi bi-palette text-primary" aria-hidden="true"></i> <?= e(t('settings.theme')) ?></h2>
                <div class="row g-3">
                    <?php
                    $themes = [
                        'light' => ['icon' => 'bi-sun', 'label' => t('settings.theme_light')],
                        'dark' => ['icon' => 'bi-moon-stars', 'label' => t('settings.theme_dark')],
                        'auto' => ['icon' => 'bi-circle-half', 'label' => t('settings.theme_auto')],
                    ];
                    foreach ($themes as $key => $th): ?>
                        <div class="col-12 col-md-4">
                            <label class="lc-card p-3 d-flex align-items-center gap-2 w-100" style="cursor:pointer">
                                <input class="form-check-input m-0" type="radio" name="theme" value="<?= e($key) ?>" data-theme-option
                                       <?= ($user['theme'] ?? 'light') === $key ? 'checked' : '' ?>>
                                <i class="bi <?= e($th['icon']) ?>" aria-hidden="true"></i>
                                <span><?= e($th['label']) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <hr class="my-4">

                <h2 class="h5 mb-3"><i class="bi bi-bell text-primary" aria-hidden="true"></i> <?= e(t('settings.notifications')) ?></h2>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="email_notify" name="email_notify"
                           <?= $user['email_notify'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="email_notify"><?= e(t('user.email_notify')) ?></label>
                </div>
            </div>
            <div class="lc-card-body border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span class="btn-label"><?= e(t('common.save')) ?></span>
                </button>
            </div>
        </form>

        <!-- Password -->
        <form method="post" action="<?= base_url('settings.php') ?>" class="lc-card">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="password">
            <div class="lc-card-body">
                <h2 class="h5 mb-3"><i class="bi bi-shield-lock text-primary" aria-hidden="true"></i> <?= e(t('settings.password')) ?></h2>
                <div class="row g-3">
                    <div class="col-12">
                        <label for="current_password" class="form-label"><?= e(t('auth.password')) ?></label>
                        <div class="input-icon-wrap has-end">
                            <i class="bi bi-lock" aria-hidden="true"></i>
                            <input type="password" class="form-control" id="current_password" name="current_password"
                                   required autocomplete="current-password">
                            <button type="button" class="btn-icon-end" data-toggle-password="#current_password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="new_password" class="form-label"><?= e(t('user.new_password')) ?></label>
                        <div class="input-icon-wrap has-end">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            <input type="password" class="form-control" id="new_password" name="new_password"
                                   required minlength="8" autocomplete="new-password" data-strength-input>
                            <button type="button" class="btn-icon-end" data-toggle-password="#new_password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="strength-meter" data-strength-meter data-score="0" aria-hidden="true">
                            <span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="confirm_password" class="form-label"><?= e(t('auth.confirm_password')) ?></label>
                        <div class="input-icon-wrap has-end">
                            <i class="bi bi-key" aria-hidden="true"></i>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                   required minlength="8" autocomplete="new-password">
                            <button type="button" class="btn-icon-end" data-toggle-password="#confirm_password" aria-label="<?= ea(t('auth.show_password')) ?>" aria-pressed="false">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="lc-card-body border-top d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <span class="btn-label"><?= e(t('common.save')) ?></span>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require __DIR__ . '/../src/layout/footer.php'; ?>
