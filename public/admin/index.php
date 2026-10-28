<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();

$studentCount = (int)(db_select_one('SELECT COUNT(*) AS n FROM users WHERE role = "student" AND is_active = 1')['n'] ?? 0);
$courseCount = (int)(db_select_one('SELECT COUNT(*) AS n FROM courses')['n'] ?? 0);
$itemCount = (int)(db_select_one('SELECT COUNT(*) AS n FROM items')['n'] ?? 0);
$fileCount = (int)(db_select_one('SELECT COUNT(*) AS n FROM files')['n'] ?? 0);

$latest = db_select(
    'SELECT i.*, c.name AS course_name, u.name AS uploader_name,
            (SELECT COUNT(*) FROM files f WHERE f.item_id = i.id) AS file_count
     FROM items i
     JOIN courses c ON c.id = i.course_id
     JOIN users u ON u.id = i.uploaded_by
     ORDER BY i.created_at DESC
     LIMIT 8'
);

$notifyStats = notify_get_stats();
$currentTerm = db_select_one('SELECT * FROM terms WHERE is_current = 1 LIMIT 1');

$pageTitle = t('nav.dashboard') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('dashboard'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-end justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e(t('nav.dashboard')) ?></h1>
                <p class="text-muted mb-0">
                    <?php if ($currentTerm): ?>
                        <?= e($currentTerm['name']) ?>
                    <?php else: ?>
                        <?= e(t('term.no_terms')) ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?= base_url('admin/upload.php') ?>" class="btn btn-primary">
                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> <?= e(t('nav.upload')) ?>
            </a>
        </div>

        <!-- Counters -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon" aria-hidden="true"><i class="bi bi-people"></i></span>
                    <div>
                        <div class="stat-value"><?= $studentCount ?></div>
                        <div class="stat-label"><?= e(t('admin.stats.students')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon" style="background: var(--lc-teal-soft); color: var(--lc-teal)" aria-hidden="true"><i class="bi bi-journals"></i></span>
                    <div>
                        <div class="stat-value"><?= $courseCount ?></div>
                        <div class="stat-label"><?= e(t('admin.stats.courses')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon" style="background: var(--lc-accent-soft); color: var(--lc-warning)" aria-hidden="true"><i class="bi bi-collection"></i></span>
                    <div>
                        <div class="stat-value"><?= $itemCount ?></div>
                        <div class="stat-label"><?= e(t('admin.stats.items')) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-card">
                    <span class="stat-icon" style="background: var(--lc-rose-soft); color: var(--lc-rose)" aria-hidden="true"><i class="bi bi-paperclip"></i></span>
                    <div>
                        <div class="stat-value"><?= $fileCount ?></div>
                        <div class="stat-label"><?= e(t('item.files')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-7">
                <div class="lc-card h-100">
                    <div class="lc-card-body">
                        <div class="section-heading">
                            <h2 class="h5 mb-0"><i class="bi bi-clock-history text-primary" aria-hidden="true"></i> <?= e(t('admin.latest_uploads')) ?></h2>
                            <a href="<?= base_url('admin/upload.php') ?>" class="btn btn-sm btn-ghost">
                                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('nav.upload')) ?>
                            </a>
                        </div>

                        <?php if ($latest): ?>
                            <?php foreach ($latest as $it): ?>
                                <div class="feed-item">
                                    <span class="feed-icon <?= e(item_type_badge($it['type'])) ?>" aria-hidden="true">
                                        <i class="bi <?= e(item_type_icon($it['type'])) ?>"></i>
                                    </span>
                                    <div class="min-w-0 flex-grow-1">
                                        <a class="feed-title lc-wrap" href="<?= ea(base_url('item.php?id=' . (int)$it['id'])) ?>"><?= e($it['title']) ?></a>
                                        <div class="feed-meta">
                                            <?= e($it['course_name']) ?> · <?= (int)$it['file_count'] ?> <?= e(t('item.files')) ?>
                                            · <?= e(format_relative_time(utc_datetime($it['created_at']))) ?>
                                        </div>
                                    </div>
                                    <a href="<?= ea(base_url('admin/edit_item.php?id=' . (int)$it['id'])) ?>" class="btn btn-sm btn-ghost" aria-label="<?= ea(t('item.edit')) ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted mb-0"><?= e(t('admin.no_uploads')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <!-- Storage -->
                <div class="lc-card mb-3">
                    <div class="lc-card-body">
                        <?php lc_storage_meter(); ?>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="bi bi-info-circle" aria-hidden="true"></i>
                            <?= e(t('admin.stats.storage_hint')) ?>
                        </p>
                    </div>
                </div>

                <!-- Quick actions -->
                <div class="lc-card mb-3">
                    <div class="lc-card-body">
                        <h2 class="h6 mb-3"><?= e(t('admin.quick_actions')) ?></h2>
                        <div class="d-grid gap-2">
                            <a href="<?= base_url('admin/upload.php') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i> <?= e(t('nav.upload')) ?>
                            </a>
                            <a href="<?= base_url('admin/announcements.php') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-megaphone" aria-hidden="true"></i> <?= e(t('announcement.create')) ?>
                            </a>
                            <a href="<?= base_url('admin/invites.php') ?>" class="btn btn-outline-primary">
                                <i class="bi bi-envelope-plus" aria-hidden="true"></i> <?= e(t('invite.create')) ?>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Email stats -->
                <div class="lc-card">
                    <div class="lc-card-body">
                        <h2 class="h6 mb-2"><i class="bi bi-envelope-check" aria-hidden="true"></i> <?= e(t('settings.notifications')) ?></h2>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted"><?= e(t('common.success')) ?></span>
                            <span class="badge-soft-success badge-type"><?= (int)($notifyStats['sent'] ?? 0) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small mt-1">
                            <span class="text-muted"><?= e(t('common.error')) ?></span>
                            <span class="badge-soft-danger badge-type"><?= (int)($notifyStats['failed'] ?? 0) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>