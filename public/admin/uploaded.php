<?php
/**
 * Upload success screen with WhatsApp share
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_uploader();
$itemId = (int)($_GET['id'] ?? 0);

$item = db_select_one(
    'SELECT i.*, c.name AS course_name, c.code AS course_code, c.color AS course_color
     FROM items i JOIN courses c ON c.id = i.course_id
     WHERE i.id = ?',
    [$itemId]
);

if (!$item) {
    http_response_code(404);
    $pageTitle = t('errors.404.title');
    require __DIR__ . '/../../src/layout/header.php';
    echo '<div class="lc-card my-4">';
    lc_empty_state(t('errors.404.title'), t('errors.404.message'), 'bi-file-earmark-x', base_url('admin/index.php'), t('nav.dashboard'));
    echo '</div>';
    require __DIR__ . '/../../src/layout/footer.php';
    exit;
}

// Staff may view the success page for items they can manage.
if (!can('upload', (int)$item['course_id'])) {
    http_response_code(403);
    exit(t('auth.forbidden'));
}

$files = db_select('SELECT * FROM files WHERE item_id = ? ORDER BY created_at', [$itemId]);
$sent = (int)($_GET['sent'] ?? 0);
$failed = (int)($_GET['failed'] ?? 0);
$groupUrl = whatsapp_group_url();

$shareMsg = generate_item_share_message($item, ['name' => $item['course_name']]);
$itemUrl = base_url('item.php?id=' . (int)$item['id']);

$pageTitle = t('upload.success') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="lc-card text-center">
            <div class="lc-card-body py-4">
                <div class="success-check" aria-hidden="true"><i class="bi bi-check-lg"></i></div>
                <h1 class="h3 mb-1"><?= e(t('upload.success')) ?></h1>
                <p class="text-muted mb-0"><?= e($item['course_name']) ?></p>

                <div class="text-start mt-4 p-3 rounded-3" style="background: var(--lc-surface-2)">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge-type <?= e(item_type_badge($item['type'])) ?>">
                            <i class="bi <?= e(item_type_icon($item['type'])) ?>" aria-hidden="true"></i>
                            <?= e(item_type_label($item['type'])) ?>
                        </span>
                        <span class="badge-soft-muted badge-type"><?= count($files) ?> <?= e(t('item.files')) ?></span>
                    </div>
                    <h2 class="h5 lc-wrap mb-1"><?= e($item['title']) ?></h2>
                    <p class="item-meta mb-0">
                        <i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime(utc_datetime($item['created_at']), 'medium')) ?>
                        <?php if ($item['due_at']): ?>
                            · <i class="bi bi-hourglass-split" aria-hidden="true"></i> <?= e(format_date(utc_datetime($item['due_at']), 'medium')) ?>
                        <?php endif; ?>
                    </p>
                </div>

                <!-- Email status -->
                <div class="d-flex justify-content-center gap-3 mt-3 small">
                    <span class="badge-soft-success badge-type">
                        <i class="bi bi-envelope-check" aria-hidden="true"></i> <?= $sent ?>
                    </span>
                    <?php if ($failed > 0): ?>
                        <span class="badge-soft-danger badge-type">
                            <i class="bi bi-envelope-x" aria-hidden="true"></i> <?= $failed ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- WhatsApp share -->
            <div class="lc-card-body border-top text-start">
                <h2 class="h6 mb-2"><i class="bi bi-whatsapp text-success" aria-hidden="true"></i> <?= e(t('share.whatsapp')) ?></h2>
                <textarea id="shareMessage" class="share-box w-100 mb-3" rows="5" readonly aria-label="<?= ea(t('share.copy')) ?>"><?= e($shareMsg) ?></textarea>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-whatsapp flex-grow-1"
                            data-whatsapp="#shareMessage"
                            <?php if ($groupUrl): ?>data-whatsapp-group="<?= ea($groupUrl) ?>"<?php endif; ?>>
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        <?= e($groupUrl ? t('share.group') : t('share.whatsapp')) ?>
                    </button>
                    <button type="button" class="btn btn-outline-primary flex-grow-1" data-copy="#shareMessage">
                        <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('share.copy')) ?>
                    </button>
                </div>
                <?php if ($groupUrl): ?>
                    <p class="form-text mt-2 mb-0"><?= e(t('sharing.share_hint')) ?></p>
                <?php endif; ?>
            </div>

            <div class="lc-card-body border-top d-flex flex-column flex-sm-row gap-2">
                <a href="<?= ea($itemUrl) ?>" class="btn btn-outline-primary flex-grow-1">
                    <i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('share.view_item')) ?>
                </a>
                <a href="<?= ea(base_url('admin/upload.php?course_id=' . (int)$item['course_id'])) ?>" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('share.upload_another')) ?>
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$extraJs = '<script src="' . e(asset_url('js/share.js')) . '"></script>';
require __DIR__ . '/../../src/layout/footer.php';