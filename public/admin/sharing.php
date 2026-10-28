<?php
/**
 * Admin: WhatsApp group sharing configuration
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();
$errors = [];
$groupUrl = whatsapp_group_url();

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? 'save';

    if ($action === 'save') {
        $raw = trim($_POST['whatsapp_group_url'] ?? '');

        if ($raw === '') {
            setting_set('whatsapp_group_url', null, (int)$user['id']);
            redirect_with(base_url('admin/sharing.php'), 'success', t('sharing.saved'));
        }

        $normalized = normalize_whatsapp_group_url($raw);
        if ($normalized === null) {
            $errors[] = t('sharing.invalid');
        } else {
            setting_set('whatsapp_group_url', $normalized, (int)$user['id']);
            redirect_with(base_url('admin/sharing.php'), 'success', t('sharing.saved'));
        }
    }
}

$groupUrl = whatsapp_group_url();
$pageTitle = t('nav.sharing') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('sharing'); ?>

    <div>
        <div class="mb-4">
            <h1 class="h3 mb-1"><?= e(t('nav.sharing')) ?></h1>
            <p class="text-muted mb-0"><?= e(t('sharing.subtitle')) ?></p>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                <form method="post" action="<?= base_url('admin/sharing.php') ?>" class="lc-card">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="save">
                    <div class="lc-card-body">
                        <h2 class="h5 mb-3">
                            <i class="bi bi-whatsapp text-success" aria-hidden="true"></i>
                            <?= e(t('sharing.group_link')) ?>
                        </h2>

                        <div class="mb-2">
                            <label for="whatsapp_group_url" class="form-label"><?= e(t('sharing.group_link')) ?></label>
                            <input type="url" class="form-control" id="whatsapp_group_url" name="whatsapp_group_url"
                                   value="<?= ea($groupUrl ?? '') ?>" inputmode="url"
                                   placeholder="https://chat.whatsapp.com/..." autocomplete="off">
                            <div class="form-text"><?= e(t('sharing.group_link_help')) ?></div>
                        </div>

                        <?php if ($groupUrl): ?>
                            <div class="d-flex flex-wrap gap-2 align-items-center mt-3">
                                <span class="badge-soft-success badge-type">
                                    <i class="bi bi-check-circle" aria-hidden="true"></i> <?= e(t('sharing.configured')) ?>
                                </span>
                                <a href="<?= ea($groupUrl) ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
                                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> <?= e(t('sharing.open_group')) ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-3 mb-0" role="status"><?= e(t('sharing.not_configured')) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="lc-card-body border-top d-flex flex-wrap justify-content-between gap-2">
                        <span class="text-muted small align-self-center"><?= e(t('sharing.privacy')) ?></span>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2" aria-hidden="true"></i> <?= e(t('common.save')) ?>
                        </button>
                    </div>
                </form>
            </div>

            <div class="col-12 col-lg-4">
                <div class="lc-card">
                    <div class="lc-card-body">
                        <h2 class="h6 mb-3"><i class="bi bi-info-circle text-primary" aria-hidden="true"></i> <?= e(t('sharing.how_title')) ?></h2>
                        <p class="small text-muted mb-2"><?= e(t('sharing.how_copy')) ?></p>
                        <p class="small text-muted mb-2"><?= e(t('sharing.how_open')) ?></p>
                        <p class="small text-muted mb-0"><?= e(t('sharing.how_paste')) ?></p>
                        <hr>
                        <p class="small text-muted mb-0"><?= e(t('sharing.how_item_links')) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
