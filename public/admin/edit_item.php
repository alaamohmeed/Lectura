<?php
/**
 * Edit an item: fields, add/remove files, delete item
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_uploader();
$itemId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);

$item = db_select_one(
    'SELECT i.*, c.name AS course_name, c.term_id
     FROM items i JOIN courses c ON c.id = i.course_id
     WHERE i.id = ?',
    [$itemId]
);

if (!$item) {
    http_response_code(404);
    $pageTitle = t('errors.404.title');
    require __DIR__ . '/../../src/layout/header.php';
    echo '<div class="lc-card my-4">';
    lc_empty_state(t('errors.404.title'), t('errors.404.message'), 'bi-file-earmark-x', base_url('index.php'), t('common.back_home'));
    echo '</div>';
    require __DIR__ . '/../../src/layout/footer.php';
    exit;
}

if (!can('upload', (int)$item['course_id'])) {
    http_response_code(403);
    exit(t('auth.forbidden'));
}

$errors = [];
$lcPageConfig = $GLOBALS['config']['upload'];

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? 'update';

    if ($action === 'delete') {
        try {
            upload_delete_item_files($itemId);
            db_delete('items', 'id = ?', [$itemId]);
            redirect_with(
                base_url('course.php?id=' . (int)$item['course_id']),
                'success',
                t('item.deleted')
            );
        } catch (Throwable $e) {
            error_log('Delete item failed: ' . $e->getMessage());
            $errors[] = t('common.error');
        }
    } elseif ($action === 'remove_file') {
        $fileId = (int)($_POST['file_id'] ?? 0);
        $file = db_select_one('SELECT * FROM files WHERE id = ? AND item_id = ?', [$fileId, $itemId]);
        if ($file) {
            upload_delete($fileId);
            redirect_with(base_url('admin/edit_item.php?id=' . $itemId), 'success', t('item.updated'));
        }
        $errors[] = t('common.error');
    } elseif ($action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type = $_POST['type'] ?? $item['type'];
        $dueInput = trim($_POST['due_at'] ?? '');
        $courseId = (int)($_POST['course_id'] ?? $item['course_id']);

        if ($title === '' || mb_strlen($title) > 200) {
            $errors[] = t('item.title') . ': ' . t('common.required');
        }
        if (!in_array($type, ['lecture', 'assignment', 'summary', 'exam', 'other'], true)) {
            $errors[] = t('upload.select_type');
        }
        if (!db_select_one('SELECT id FROM courses WHERE id = ?', [$courseId])) {
            $errors[] = t('upload.invalid_course');
        } elseif (!can('upload', $courseId)) {
            $errors[] = t('upload.not_allowed');
        }

        $dueAt = null;
        if ($type === 'assignment' && $dueInput !== '') {
            try {
                $dt = new DateTime($dueInput, new DateTimeZone($GLOBALS['config']['app']['timezone'] ?? 'UTC'));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $dueAt = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $errors[] = t('item.due_date');
            }
        }

        // Optional new files
        $validFiles = [];
        if (!empty($_FILES['files']['name'][0])) {
            $validation = upload_validate($_FILES['files']);
            $validFiles = $validation['valid'];
            foreach ($validation['errors'] as $err) $errors[] = $err;
        }

        if (!$errors) {
            db_update('items', [
                'course_id' => $courseId,
                'type' => $type,
                'title' => $title,
                'description' => $description !== '' ? $description : null,
                'due_at' => $dueAt,
                'updated_at' => utc_now(),
            ], 'id = ?', [$itemId]);

            if ($validFiles) {
                upload_store($validFiles, $itemId);
            }

            redirect_with(base_url('admin/edit_item.php?id=' . $itemId), 'success', t('item.updated'));
        }
    }
}

$files = db_select('SELECT * FROM files WHERE item_id = ? ORDER BY created_at', [$itemId]);
$courses = get_manageable_courses($user);

$dueValue = $item['due_at'] ? (new DateTime($item['due_at'], new DateTimeZone('UTC')))
    ->setTimezone(new DateTimeZone($GLOBALS['config']['app']['timezone'] ?? 'UTC'))
    ->format('Y-m-d\TH:i') : '';

$groupUrl = whatsapp_group_url();
$shareMsg = generate_item_share_message($item, ['name' => $item['course_name']]);

$pageTitle = t('item.edit') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('upload'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e(t('item.edit')) ?></h1>
                <p class="text-muted mb-0 lc-wrap"><?= e($item['course_name']) ?> · <?= e($item['title']) ?></p>
            </div>
            <a href="<?= ea(base_url('item.php?id=' . $itemId)) ?>" class="btn btn-ghost">
                <i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('item.preview')) ?>
            </a>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= ea(base_url('admin/edit_item.php?id=' . $itemId)) ?>" enctype="multipart/form-data" class="lc-card mb-4" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $itemId ?>">
            <input type="hidden" name="action" value="update">

            <div class="lc-card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label for="course_id" class="form-label"><?= e(t('upload.course')) ?></label>
                        <select class="form-select" id="course_id" name="course_id">
                            <?php foreach ($courses as $c): ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)$item['course_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                    <?= e($c['name']) ?><?= !empty($c['term_name']) ? ' — ' . e($c['term_name']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="type" class="form-label"><?= e(t('upload.type')) ?></label>
                        <select class="form-select" id="type" name="type">
                            <?php foreach (['lecture', 'assignment', 'summary', 'exam', 'other'] as $type): ?>
                                <option value="<?= e($type) ?>" <?= $item['type'] === $type ? 'selected' : '' ?>><?= e(t("item.type.{$type}")) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label for="title" class="form-label"><?= e(t('item.title')) ?> <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" maxlength="200"
                               value="<?= ea($item['title']) ?>" required>
                    </div>

                    <div class="col-12">
                        <label for="description" class="form-label"><?= e(t('item.description')) ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($item['description']) ?></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="due_at" class="form-label"><?= e(t('upload.due_date')) ?></label>
                        <input type="datetime-local" class="form-control" id="due_at" name="due_at" value="<?= ea($dueValue) ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h2 class="h6 mb-2"><?= e(t('item.files')) ?></h2>
                <?php if ($files): ?>
                    <?php foreach ($files as $f): ?>
                        <div class="file-row">
                            <i class="bi <?= e(file_icon($f['mime'])) ?> file-icon" aria-hidden="true"></i>
                            <div class="min-w-0">
                                <div class="fw-semibold lc-wrap"><?= e($f['original_name']) ?></div>
                                <div class="file-item-size"><?= e(format_file_size((int)$f['size'])) ?></div>
                            </div>
                            <div class="file-actions">
                                <a href="<?= ea(base_url('download.php?id=' . (int)$f['id'])) ?>" class="btn btn-sm btn-ghost" aria-label="<?= ea(t('item.download')) ?>">
                                    <i class="bi bi-download" aria-hidden="true"></i>
                                </a>
                                <button type="submit" form="removeFile<?= (int)$f['id'] ?>" class="btn btn-sm btn-danger-soft" aria-label="<?= ea(t('item.remove_file')) ?>">
                                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted small"><?= e(t('item.no_files')) ?></p>
                <?php endif; ?>

                <div class="mt-3">
                    <label for="new_files" class="form-label"><?= e(t('item.upload_files')) ?></label>
                    <input type="file" class="form-control" id="new_files" name="files[]" multiple
                           accept="<?= ea('.' . implode(',.', $lcPageConfig['allowed_extensions'])) ?>">
                    <div class="form-text"><?= e(t('upload.dropzone_hint', ['size' => format_file_size((int)$lcPageConfig['max_size']), 'types' => implode(', ', $lcPageConfig['allowed_extensions'])])) ?></div>
                </div>
            </div>

            <div class="lc-card-body border-top d-flex flex-wrap justify-content-between gap-2">
                <button type="submit" class="btn btn-danger-soft" form="deleteForm">
                    <i class="bi bi-trash" aria-hidden="true"></i> <?= e(t('item.delete')) ?>
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2" aria-hidden="true"></i>
                    <span class="btn-label"><?= e(t('common.save')) ?></span>
                </button>
            </div>
        </form>

        <!-- Share to group -->
        <div class="lc-card mb-4">
            <div class="lc-card-body">
                <h2 class="h6 mb-2">
                    <i class="bi bi-whatsapp text-success" aria-hidden="true"></i>
                    <?= e($groupUrl ? t('share.group') : t('share.whatsapp')) ?>
                </h2>
                <textarea id="editShareMessage" class="share-box w-100 mb-3" rows="5" readonly aria-label="<?= ea(t('share.copy')) ?>"><?= e($shareMsg) ?></textarea>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-whatsapp"
                            data-whatsapp="#editShareMessage"
                            <?php if ($groupUrl): ?>data-whatsapp-group="<?= ea($groupUrl) ?>"<?php endif; ?>>
                        <i class="bi bi-whatsapp" aria-hidden="true"></i>
                        <?= e($groupUrl ? t('share.group') : t('share.whatsapp')) ?>
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-copy="#editShareMessage">
                        <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('share.copy')) ?>
                    </button>
                </div>
                <?php if ($groupUrl): ?>
                    <p class="form-text mt-2 mb-0"><?= e(t('sharing.share_hint')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Delete form (separate to avoid nested forms) -->
        <form id="deleteForm" method="post" action="<?= ea(base_url('admin/edit_item.php?id=' . $itemId)) ?>"
              data-confirm="<?= ea(t('item.delete') . '؟') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= $itemId ?>">
            <input type="hidden" name="action" value="delete">
        </form>

        <!-- Per-file removal forms -->
        <?php foreach ($files as $f): ?>
            <form method="post" action="<?= ea(base_url('admin/edit_item.php?id=' . $itemId)) ?>"
                  id="removeFile<?= (int)$f['id'] ?>" class="d-inline"
                  data-confirm="<?= ea(t('item.remove_file') . '؟') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $itemId ?>">
                <input type="hidden" name="action" value="remove_file">
                <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
            </form>
        <?php endforeach; ?>
    </div>
</div>

<?php
$extraJs = '<script src="' . e(asset_url('js/share.js')) . '"></script>';
require __DIR__ . '/../../src/layout/footer.php';
?>