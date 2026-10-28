<?php
/**
 * Admin/assistant upload form
 * Supports normal form POST and AJAX (XHR) with JSON response.
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_uploader();
$isAdmin = $user['role'] === 'admin';

// Admins and assistants may upload anywhere; lecturers only to assigned courses.
$courses = get_manageable_courses($user);

$preselectedCourse = (int)($_GET['course_id'] ?? 0);

$errors = [];
$old = [
    'course_id' => $preselectedCourse,
    'type' => 'lecture',
    'title' => '',
    'description' => '',
    'due_at' => '',
];

// PHP discards POST data when the whole request exceeds post_max_size,
// which would otherwise surface as a confusing CSRF/session error.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)
    && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    $msg = t('upload.error.post_max', ['max' => ini_get('post_max_size')]);
    if (is_ajax()) {
        http_response_code(413);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['success' => false, 'error' => $msg]));
    }
    $errors[] = $msg;
} elseif (is_post()) {
    csrf_protect();

    $old['course_id'] = (int)($_POST['course_id'] ?? 0);
    $old['type'] = $_POST['type'] ?? 'lecture';
    $old['title'] = trim($_POST['title'] ?? '');
    $old['description'] = trim($_POST['description'] ?? '');
    $old['due_at'] = trim($_POST['due_at'] ?? '');

    $allowedTypes = ['lecture', 'assignment', 'summary', 'exam', 'other'];

    // Validate course + permission
    $course = db_select_one('SELECT * FROM courses WHERE id = ?', [$old['course_id']]);
    if (!$course) {
        $errors[] = t('upload.invalid_course');
    } elseif (!can('upload', (int)$old['course_id'])) {
        $errors[] = t('upload.not_allowed');
    }

    if (!in_array($old['type'], $allowedTypes, true)) {
        $errors[] = t('upload.select_type');
    }
    if ($old['title'] === '' || mb_strlen($old['title']) > 200) {
        $errors[] = t('item.title') . ': ' . t('common.required');
    }

    $dueAt = null;
    if ($old['type'] === 'assignment' && $old['due_at'] !== '') {
        try {
            $dt = new DateTime($old['due_at'], new DateTimeZone($GLOBALS['config']['app']['timezone'] ?? 'UTC'));
            $dt->setTimezone(new DateTimeZone('UTC'));
            $dueAt = $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $errors[] = t('item.due_date');
        }
    }

    // Files
    $validFiles = [];
    $filesSubmitted = false;
    if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
        foreach ($_FILES['files']['name'] as $submittedName) {
            if ($submittedName !== '' && $submittedName !== null) {
                $filesSubmitted = true;
                break;
            }
        }
    }

    if ($filesSubmitted) {
        $validation = upload_validate($_FILES['files']);
        $validFiles = $validation['valid'];
        foreach ($validation['errors'] as $err) {
            $errors[] = $err;
        }
    } elseif ($old['type'] !== 'assignment') {
        $errors[] = t('upload.no_files');
    }

    if (!$errors) {
        try {
            $stored = null;
            $itemId = db_transaction(function () use ($old, $user, $dueAt, $validFiles, &$stored) {
                $itemId = db_insert('items', [
                    'course_id' => $old['course_id'],
                    'type' => $old['type'],
                    'title' => $old['title'],
                    'description' => $old['description'] !== '' ? $old['description'] : null,
                    'due_at' => $dueAt,
                    'uploaded_by' => $user['id'],
                    'created_at' => utc_now(),
                ]);

                $stored = upload_store($validFiles, $itemId);
                if (count($stored) === 0 && $old['type'] !== 'assignment') {
                    throw new RuntimeException('No files could be stored');
                }
                return $itemId;
            });

            // Email notifications (never break the upload)
            $sent = 0;
            $failed = 0;
            try {
                notify_new_item(
                    db_select_one('SELECT * FROM items WHERE id = ?', [$itemId]),
                    $course
                );
                $statRows = db_select(
                    'SELECT status, COUNT(*) AS n FROM notification_log WHERE item_id = ? GROUP BY status',
                    [$itemId]
                );
                foreach ($statRows as $row) {
                    if ($row['status'] === 'sent') $sent = (int)$row['n'];
                    if ($row['status'] === 'failed') $failed = (int)$row['n'];
                }
            } catch (Throwable $e) {
                error_log('Notification error after upload: ' . $e->getMessage());
            }

            $redirect = base_url('admin/uploaded.php?id=' . $itemId . '&sent=' . $sent . '&failed=' . $failed);

            if (is_ajax()) {
                header('Content-Type: application/json; charset=utf-8');
                exit(json_encode(['success' => true, 'redirect' => $redirect, 'id' => $itemId]));
            }
            header('Location: ' . $redirect);
            exit;
        } catch (Throwable $e) {
            error_log('Upload failed: ' . $e->getMessage());
            $errors[] = t('upload.error');
        }
    }

    // AJAX errors
    if (is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        exit(json_encode(['success' => false, 'error' => implode(' ', $errors)]));
    }
}

$lcPageConfig = $GLOBALS['config']['upload'];
$pageTitle = t('upload.title') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('upload'); ?>

    <div>
        <h1 class="h3 mb-1"><?= e(t('upload.title')) ?></h1>
        <p class="text-muted mb-4"><?= e(t('upload.dropzone_hint', ['size' => format_file_size((int)$lcPageConfig['max_size']), 'types' => implode(', ', $lcPageConfig['allowed_extensions'])])) ?></p>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (!$courses): ?>
            <div class="lc-card">
                <?php if ($user['role'] === 'lecturer'): ?>
                    <?php lc_empty_state(t('upload.no_assigned_courses'), t('nav.upload'), 'bi-journals'); ?>
                <?php else: ?>
                    <?php lc_empty_state(t('course.no_courses'), t('admin.stats.courses'), 'bi-journals',
                        $isAdmin ? base_url('admin/courses.php') : null,
                        $isAdmin ? t('course.create') : null); ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <form id="uploadForm" method="post" action="<?= base_url('admin/upload.php') ?>" enctype="multipart/form-data"
                  data-max-size="<?= (int)$lcPageConfig['max_size'] ?>"
                  data-allowed-ext="<?= ea(implode(',', $lcPageConfig['allowed_extensions'])) ?>"
                  class="lc-card" data-no-loading novalidate>
                <?= csrf_field() ?>
                <div class="lc-card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="course" class="form-label"><?= e(t('upload.course')) ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="course" name="course_id" required>
                                <option value=""><?= e(t('upload.select_course')) ?></option>
                                <?php foreach ($courses as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= (int)$old['course_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['name']) ?><?= !empty($c['term_name']) ? ' — ' . e($c['term_name']) : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="itemType" class="form-label"><?= e(t('upload.type')) ?> <span class="text-danger">*</span></label>
                            <select class="form-select" id="itemType" name="type" required>
                                <?php foreach (['lecture', 'assignment', 'summary', 'exam', 'other'] as $type): ?>
                                    <option value="<?= e($type) ?>" <?= $old['type'] === $type ? 'selected' : '' ?>>
                                        <?= e(t("item.type.{$type}")) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="title" class="form-label"><?= e(t('item.title')) ?> <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" maxlength="200"
                                   value="<?= ea($old['title']) ?>" required placeholder="<?= ea(t('item.title')) ?>">
                        </div>

                        <div class="col-12">
                            <label for="description" class="form-label"><?= e(t('item.description')) ?> <span class="text-muted">(<?= e(t('common.optional')) ?>)</span></label>
                            <textarea class="form-control" id="description" name="description" rows="3"
                                      placeholder="<?= ea(t('item.description')) ?>"><?= e($old['description']) ?></textarea>
                        </div>

                        <div class="col-12 col-md-6 d-none" id="dueDateWrap">
                            <label for="dueDate" class="form-label"><?= e(t('upload.due_date')) ?></label>
                            <input type="datetime-local" class="form-control" id="dueDate" name="due_at"
                                   value="<?= ea($old['due_at']) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label"><?= e(t('item.files')) ?> <span class="text-danger" id="fileRequiredMark">*</span></label>
                            <div class="dropzone" id="dropzone" role="button" tabindex="0"
                                 aria-label="<?= ea(t('upload.dropzone_text')) ?>">
                                <i class="bi bi-cloud-arrow-up dropzone-icon" aria-hidden="true"></i>
                                <div class="dropzone-text"><?= e(t('upload.dropzone_text')) ?></div>
                                <div class="dropzone-hint">
                                    <?= e(t('upload.dropzone_hint', ['size' => format_file_size((int)$lcPageConfig['max_size']), 'types' => implode(', ', $lcPageConfig['allowed_extensions'])])) ?>
                                </div>
                                <input type="file" id="fileInput" name="files[]" class="visually-hidden"
                                       multiple accept="<?= ea('.' . implode(',.', $lcPageConfig['allowed_extensions'])) ?>">
                            </div>
                            <div class="file-list" id="fileList" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="upload-progress" id="uploadProgress">
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span><?= e(t('upload.progress')) ?></span>
                            <span id="uploadProgressText">0%</span>
                        </div>
                        <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div class="progress-bar" id="uploadProgressBar" style="width:0%"></div>
                        </div>
                    </div>
                </div>

                <div class="lc-card-body border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-lg" id="uploadSubmit">
                        <i class="bi bi-cloud-arrow-up" aria-hidden="true"></i>
                        <span class="btn-label"><?= e(t('nav.upload')) ?></span>
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php
$extraJs = '<script src="' . e(asset_url('js/upload.js')) . '"></script>';
require __DIR__ . '/../../src/layout/footer.php';