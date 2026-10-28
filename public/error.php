<?php
/**
 * Friendly error pages: /error.php?code=403|404|500
 */
require_once __DIR__ . '/../src/bootstrap.php';

$code = (int)($_GET['code'] ?? 404);
if (!in_array($code, [403, 404, 500], true)) {
    $code = 404;
}
http_response_code($code);

$titles = [
    403 => t('errors.403.title'),
    404 => t('errors.404.title'),
    500 => t('errors.500.title'),
];
$messages = [
    403 => t('errors.403.message'),
    404 => t('errors.404.message'),
    500 => t('errors.500.message'),
];
$icons = [403 => 'bi-shield-lock', 404 => 'bi-compass', 500 => 'bi-cloud-slash'];

$pageTitle = $titles[$code] . ' — ' . t('app.name');
require __DIR__ . '/../src/layout/header.php';
?>

<div class="row justify-content-center py-4 py-md-5">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="lc-card text-center">
            <div class="lc-card-body py-5">
                <div class="mb-3" aria-hidden="true">
                    <i class="bi <?= e($icons[$code]) ?>" style="font-size:64px;color:var(--lc-primary)"></i>
                </div>
                <div class="display-4 fw-bold text-muted mb-1"><?= $code ?></div>
                <h1 class="h4 mb-2"><?= e($titles[$code]) ?></h1>
                <p class="text-muted mb-4"><?= e($messages[$code]) ?></p>
                <a href="<?= base_url('index.php') ?>" class="btn btn-primary">
                    <i class="bi bi-house-door" aria-hidden="true"></i> <?= e(t('common.back_home')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../src/layout/footer.php'; ?>