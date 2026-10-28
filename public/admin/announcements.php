<?php
/**
 * Admin/lecturer: Announcements CRUD with pinning + email/WhatsApp sharing
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_announcement_manager();
$isAdmin = $user['role'] === 'admin';
$errors = [];
$groupUrl = whatsapp_group_url();

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $isPinned = isset($_POST['is_pinned']) ? 1 : 0;
        $sendEmail = isset($_POST['send_email']) ? true : false;

        if ($title === '' || mb_strlen($title) > 200) {
            $errors[] = t('announcement.title') . ': ' . t('common.required');
        }
        if ($body === '') {
            $errors[] = t('announcement.body') . ': ' . t('common.required');
        }

        // Lecturers may only modify their own announcements.
        $existing = null;
        if ($action === 'update') {
            $existing = db_select_one('SELECT * FROM announcements WHERE id = ?', [$id]);
            if (!$existing || (!$isAdmin && (int)$existing['created_by'] !== (int)$user['id'])) {
                http_response_code(403);
                exit(t('auth.forbidden'));
            }
        }

        if (!$errors) {
            if ($action === 'create') {
                $annId = db_insert('announcements', [
                    'title' => $title,
                    'body' => $body,
                    'is_pinned' => $isPinned,
                    'created_by' => $user['id'],
                    'created_at' => utc_now(),
                ]);

                if ($sendEmail) {
                    try {
                        notify_new_announcement(db_select_one('SELECT * FROM announcements WHERE id = ?', [$annId]));
                    } catch (Throwable $e) {
                        error_log('Announcement notify failed: ' . $e->getMessage());
                    }
                }
                redirect_with(
                    base_url('admin/announcements.php?created=' . $annId),
                    'success',
                    t('announcement.created')
                );
            } else {
                db_update('announcements', [
                    'title' => $title,
                    'body' => $body,
                    'is_pinned' => $isPinned,
                ], 'id = ?', [$id]);
                redirect_with(base_url('admin/announcements.php'), 'success', t('announcement.updated'));
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $announcement = db_select_one('SELECT * FROM announcements WHERE id = ?', [$id]);
        if (!$announcement || (!$isAdmin && (int)$announcement['created_by'] !== (int)$user['id'])) {
            http_response_code(403);
            exit(t('auth.forbidden'));
        }
        db_delete('announcements', 'id = ?', [$id]);
        redirect_with(base_url('admin/announcements.php'), 'success', t('announcement.deleted'));
    } elseif ($action === 'toggle_pin') {
        $id = (int)($_POST['id'] ?? 0);
        $a = db_select_one('SELECT * FROM announcements WHERE id = ?', [$id]);
        if ($a && ($isAdmin || (int)$a['created_by'] === (int)$user['id'])) {
            db_update('announcements', ['is_pinned' => $a['is_pinned'] ? 0 : 1], 'id = ?', [$id]);
            redirect_with(base_url('admin/announcements.php'), 'success', t('announcement.updated'));
        }
        http_response_code(403);
        exit(t('auth.forbidden'));
    }
}

$announcements = $isAdmin
    ? db_select(
        'SELECT a.*, u.name AS author_name
         FROM announcements a JOIN users u ON u.id = a.created_by
         ORDER BY a.is_pinned DESC, a.created_at DESC'
    )
    : db_select(
        'SELECT a.*, u.name AS author_name
         FROM announcements a JOIN users u ON u.id = a.created_by
         WHERE a.created_by = ?
         ORDER BY a.is_pinned DESC, a.created_at DESC',
        [$user['id']]
    );

$createdId = (int)($_GET['created'] ?? 0);

$pageTitle = t('nav.announcements') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('announcements'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e(t('nav.announcements')) ?></h1>
                <p class="text-muted mb-0 small"><?= e($isAdmin ? t('announcement.manage_all') : t('announcement.manage_own')) ?></p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAnnModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('announcement.create')) ?>
            </button>
        </div>

        <?php if (!$groupUrl): ?>
            <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2" role="status">
                <span><?= e(t('sharing.not_configured_short')) ?></span>
                <?php if ($isAdmin): ?>
                    <a href="<?= base_url('admin/sharing.php') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-whatsapp" aria-hidden="true"></i> <?= e(t('sharing.configure')) ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if ($announcements): ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($announcements as $a): ?>
                    <?php $shareMsg = generate_announcement_share_message($a); ?>
                    <article class="lc-card announcement-card<?= $a['is_pinned'] ? ' announcement-pinned' : '' ?>"
                             <?= $createdId === (int)$a['id'] ? 'style="border-color: var(--lc-success)"' : '' ?>>
                        <div class="lc-card-body">
                            <div class="d-flex flex-wrap align-items-start justify-content-between gap-2">
                                <div class="min-w-0">
                                    <h2 class="announcement-title lc-wrap">
                                        <?php if ($a['is_pinned']): ?>
                                            <i class="bi bi-pin-angle-fill text-warning" aria-label="<?= ea(t('announcement.pinned')) ?>"></i>
                                        <?php endif; ?>
                                        <?= e($a['title']) ?>
                                    </h2>
                                    <div class="item-meta mb-2">
                                        <?= e($a['author_name']) ?> · <?= e(format_relative_time(utc_datetime($a['created_at']))) ?>
                                    </div>
                                    <p class="announcement-body mb-0 lc-wrap"><?= e($a['body']) ?></p>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-sm btn-whatsapp"
                                        data-whatsapp="#annAdmin<?= (int)$a['id'] ?>"
                                        <?php if ($groupUrl): ?>data-whatsapp-group="<?= ea($groupUrl) ?>"<?php endif; ?>>
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i>
                                    <?= e($groupUrl ? t('share.group') : t('share.whatsapp')) ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost" data-copy="#annAdmin<?= (int)$a['id'] ?>">
                                    <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('share.copy')) ?>
                                </button>
                                <form method="post" action="<?= base_url('admin/announcements.php') ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_pin">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-ghost">
                                        <i class="bi bi-pin-angle" aria-hidden="true"></i>
                                        <?= e($a['is_pinned'] ? t('common.clear') : t('announcement.pinned')) ?>
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAnn<?= (int)$a['id'] ?>">
                                    <i class="bi bi-pencil" aria-hidden="true"></i> <?= e(t('common.edit')) ?>
                                </button>
                                <form method="post" action="<?= base_url('admin/announcements.php') ?>" class="d-inline"
                                      data-confirm="<?= ea(t('announcement.delete') . '؟') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger-soft">
                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                    </button>
                                </form>
                                <textarea id="annAdmin<?= (int)$a['id'] ?>" class="d-none" aria-hidden="true"><?= e($shareMsg) ?></textarea>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="lc-card"><?php lc_empty_state(t('announcement.no_announcements'), t('announcement.create'), 'bi-megaphone'); ?></div>
        <?php endif; ?>
    </div>
</div>

<!-- Create announcement -->
<div class="modal fade" id="createAnnModal" tabindex="-1" aria-labelledby="createAnnLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="post" action="<?= base_url('admin/announcements.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h2 class="modal-title h5" id="createAnnLabel"><?= e(t('announcement.create')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="annTitle" class="form-label"><?= e(t('announcement.title')) ?> <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="annTitle" name="title" maxlength="200" required>
                </div>
                <div class="mb-3">
                    <label for="annBody" class="form-label"><?= e(t('announcement.body')) ?> <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="annBody" name="body" rows="4" required></textarea>
                </div>
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" role="switch" id="annPinned" name="is_pinned">
                    <label class="form-check-label" for="annPinned"><?= e(t('announcement.pinned')) ?></label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="annEmail" name="send_email" checked>
                    <label class="form-check-label" for="annEmail"><?= e(t('user.email_notify')) ?></label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit announcements -->
<?php foreach ($announcements as $a): ?>
    <div class="modal fade" id="editAnn<?= (int)$a['id'] ?>" tabindex="-1" aria-labelledby="editAnnLabel<?= (int)$a['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content" method="post" action="<?= base_url('admin/announcements.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editAnnLabel<?= (int)$a['id'] ?>"><?= e(t('announcement.edit')) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label" for="editAnnTitle<?= (int)$a['id'] ?>"><?= e(t('announcement.title')) ?></label>
                        <input type="text" class="form-control" id="editAnnTitle<?= (int)$a['id'] ?>" name="title"
                               maxlength="200" value="<?= ea($a['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="editAnnBody<?= (int)$a['id'] ?>"><?= e(t('announcement.body')) ?></label>
                        <textarea class="form-control" id="editAnnBody<?= (int)$a['id'] ?>" name="body" rows="4" required><?= e($a['body']) ?></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="editAnnPinned<?= (int)$a['id'] ?>"
                               name="is_pinned" <?= $a['is_pinned'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="editAnnPinned<?= (int)$a['id'] ?>"><?= e(t('announcement.pinned')) ?></label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php
$extraJs = '<script src="' . e(asset_url('js/share.js')) . '"></script>';
require __DIR__ . '/../../src/layout/footer.php';
?>