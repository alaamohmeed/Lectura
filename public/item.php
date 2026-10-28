<?php
/**
 * Item detail page (share-link target)
 */
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout/partials.php';

$user = auth_require();
$itemId = (int)($_GET['id'] ?? 0);

$item = db_select_one(
    'SELECT i.*, c.name AS course_name, c.code AS course_code, c.instructor, c.color AS course_color,
            c.term_id, t.name AS term_name, t.is_current,
            u.name AS uploader_name
     FROM items i
     JOIN courses c ON c.id = i.course_id
     JOIN terms t ON t.id = c.term_id
     JOIN users u ON u.id = i.uploaded_by
     WHERE i.id = ?',
    [$itemId]
);

if (!$item) {
    http_response_code(404);
    require __DIR__ . '/../src/layout/header.php';
    echo '<div class="lc-card my-4">';
    lc_empty_state(t('errors.404.title'), t('errors.404.message'), 'bi-file-earmark-x', base_url('index.php'), t('common.back_home'));
    echo '</div>';
    require __DIR__ . '/../src/layout/footer.php';
    exit;
}

$files = db_select('SELECT * FROM files WHERE item_id = ? ORDER BY created_at', [$itemId]);
$isUploader = $user && can('upload', (int)$item['course_id']);
$canShare = $isUploader;

$type = $item['type'];
$badge = item_type_badge($type);
$icon = item_type_icon($type);
$deadline = notify_get_deadline_status($item['due_at']);
$shareMsg = $canShare ? generate_item_share_message($item, ['name' => $item['course_name']]) : '';

$pageTitle = $item['title'] . ' — ' . t('app.name');
$livePage = 'item';

require __DIR__ . '/../src/layout/header.php';
?>

<div style="--course-color: <?= ea($item['course_color'] ?: '#12355B') ?>">
    <?php lc_breadcrumb([
        ['label' => t('nav.home'), 'url' => base_url('index.php')],
        ['label' => $item['course_name'], 'url' => base_url('course.php?id=' . (int)$item['course_id'])],
        ['label' => $item['title']],
    ]); ?>

    <div class="row g-4">
        <!-- Main item -->
        <div class="col-12 col-lg-8">
            <article class="lc-card">
                <div class="lc-card-body">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge-type <?= e($badge) ?>">
                            <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
                            <?= e(item_type_label($type)) ?>
                        </span>
                        <?php if ($item['is_current']): ?>
                            <span class="badge-soft-muted badge-type"><?= e($item['term_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <h1 class="h2 lc-wrap mb-2"><?= e($item['title']) ?></h1>

                    <div class="d-flex flex-wrap align-items-center gap-3 item-meta mb-3">
                        <span><i class="bi bi-journal-bookmark" aria-hidden="true"></i> <?= e($item['course_name']) ?></span>
                        <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($item['uploader_name']) ?></span>
                        <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_datetime(utc_datetime($item['created_at']), 'medium')) ?></span>
                    </div>

                    <?php if ($item['due_at']): ?>
                        <div class="mb-3">
                            <span class="due-chip <?= e($deadline === 'overdue' ? 'overdue' : ($deadline === 'due_soon' ? 'due-soon' : '')) ?>">
                                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                <strong><?= e(t('item.due_date')) ?>:</strong>
                                <span data-countdown="<?= ea($item['due_at']) ?>"><?= e(notify_format_countdown($item['due_at'])) ?></span>
                                <span class="visually-hidden"><?= e(format_datetime(utc_datetime($item['due_at']), 'medium')) ?></span>
                            </span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['description'])): ?>
                        <div class="lc-wrap mb-2" style="white-space: pre-wrap; line-height: 1.75"><?= e($item['description']) ?></div>
                    <?php endif; ?>
                </div>

                <!-- Files -->
                <div class="lc-card-body border-top">
                    <h2 class="h6 mb-3"><i class="bi bi-paperclip" aria-hidden="true"></i> <?= e(t('item.files')) ?> (<?= count($files) ?>)</h2>

                    <?php if ($files): ?>
                        <?php foreach ($files as $f): ?>
                            <?php
                            $isPreviewable = in_array($f['mime'], ['application/pdf', 'image/jpeg', 'image/png'], true);
                            ?>
                            <div class="file-row">
                                <i class="bi <?= e(file_icon($f['mime'])) ?> file-icon" aria-hidden="true"></i>
                                <div class="min-w-0">
                                    <div class="fw-semibold lc-wrap"><?= e($f['original_name']) ?></div>
                                    <div class="file-item-size"><?= e(format_file_size((int)$f['size'])) ?> · <?= e($f['mime']) ?></div>
                                </div>
                                <div class="file-actions">
                                    <?php if ($isPreviewable): ?>
                                        <a href="<?= ea(base_url('download.php?id=' . (int)$f['id'] . '&preview=1')) ?>"
                                           target="_blank" rel="noopener" class="btn btn-sm btn-ghost">
                                            <i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('item.preview')) ?>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= ea(base_url('download.php?id=' . (int)$f['id'])) ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-download" aria-hidden="true"></i> <?= e(t('item.download')) ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0"><?= e(t('item.no_files')) ?></p>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Share (staff who can manage this course only) -->
            <?php if ($canShare): ?>
            <div class="lc-card mt-3">
                <div class="lc-card-body">
                    <h2 class="h6 mb-3"><i class="bi bi-share" aria-hidden="true"></i> <?= e(t('share.whatsapp')) ?></h2>
                    <textarea id="shareMessage" class="share-box w-100 mb-3" rows="5" readonly aria-label="<?= ea(t('share.copy')) ?>"><?= e($shareMsg) ?></textarea>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-whatsapp" data-whatsapp="#shareMessage">
                            <i class="bi bi-whatsapp" aria-hidden="true"></i> <?= e(t('share.whatsapp')) ?>
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-copy="#shareMessage">
                            <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('share.copy')) ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside class="col-12 col-lg-4">
            <div class="lc-card" style="position: sticky; top: calc(var(--lc-topbar-h) + 16px)">
                <div class="lc-card-body">
                    <h2 class="h6 text-muted mb-3"><?= e(t('nav.courses')) ?></h2>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <span class="course-card-icon" aria-hidden="true"><i class="bi bi-journal-bookmark-fill"></i></span>
                        <div class="min-w-0">
                            <a href="<?= ea(base_url('course.php?id=' . (int)$item['course_id'])) ?>" class="fw-bold lc-wrap d-block" style="color: var(--lc-text)">
                                <?= e($item['course_name']) ?>
                            </a>
                            <?php if (!empty($item['course_code'])): ?>
                                <span class="course-card-code"><?= e($item['course_code']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($item['instructor'])): ?>
                        <p class="item-meta mb-1"><i class="bi bi-person-badge" aria-hidden="true"></i> <?= e($item['instructor']) ?></p>
                    <?php endif; ?>
                    <p class="item-meta mb-0"><i class="bi bi-calendar-range" aria-hidden="true"></i> <?= e($item['term_name']) ?></p>

                    <?php if ($isUploader): ?>
                        <hr>
                        <div class="d-flex flex-column gap-2">
                            <a href="<?= ea(base_url('admin/edit_item.php?id=' . (int)$item['id'])) ?>" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-pencil" aria-hidden="true"></i> <?= e(t('item.edit')) ?>
                            </a>
                            <a href="<?= ea(base_url('admin/upload.php?course_id=' . (int)$item['course_id'])) ?>" class="btn btn-ghost btn-sm">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('item.create')) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php
$extraJs = '<script src="' . e(asset_url('js/countdown.js')) . '"></script>'
         . '<script src="' . e(asset_url('js/share.js')) . '"></script>';
require __DIR__ . '/../src/layout/footer.php';
