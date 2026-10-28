<?php
/**
 * Admin: Courses CRUD + assistant assignment
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();
$errors = [];

$terms = db_select('SELECT * FROM terms ORDER BY is_current DESC, created_at DESC');
$assistants = db_select('SELECT id, name, email FROM users WHERE role = "assistant" AND is_active = 1 ORDER BY name');
$lecturers = db_select('SELECT id, name, email FROM users WHERE role = "lecturer" AND is_active = 1 ORDER BY name');
$validAssistantIds = array_map('intval', array_column($assistants, 'id'));
$validLecturerIds = array_map('intval', array_column($lecturers, 'id'));

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $termId = (int)($_POST['term_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $instructor = trim($_POST['instructor'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $assistantIds = array_values(array_intersect(
            array_map('intval', (array)($_POST['assistants'] ?? [])),
            $validAssistantIds
        ));
        $lecturerIds = array_values(array_intersect(
            array_map('intval', (array)($_POST['lecturers'] ?? [])),
            $validLecturerIds
        ));

        if (!db_select_one('SELECT id FROM terms WHERE id = ?', [$termId])) {
            $errors[] = t('term.no_terms');
        }
        if ($name === '' || mb_strlen($name) > 150) {
            $errors[] = t('course.name') . ': ' . t('common.required');
        }
        if ($color !== '' && !preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            $color = '';
        }
        if (mb_strlen($code) > 30) $code = mb_substr($code, 0, 30);
        if (mb_strlen($instructor) > 100) $instructor = mb_substr($instructor, 0, 100);

        if (!$errors) {
            $data = [
                'term_id' => $termId,
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'instructor' => $instructor !== '' ? $instructor : null,
                'color' => $color !== '' ? $color : null,
            ];

            if ($action === 'create') {
                $data['created_at'] = utc_now();
                $courseId = db_insert('courses', $data);
                $msg = t('course.created');
            } else {
                db_update('courses', $data, 'id = ?', [$id]);
                $courseId = $id;
                $msg = t('course.updated');
            }

            // Sync lecturer assignments (these control upload scope).
            db_delete('course_lecturers', 'course_id = ?', [$courseId]);
            foreach ($lecturerIds as $lid) {
                if ($lid > 0) {
                    try {
                        db_insert('course_lecturers', ['user_id' => $lid, 'course_id' => $courseId]);
                    } catch (Throwable $e) {
                        // ignore duplicates
                    }
                }
            }

            // Sync assistant assignments (informational; assistants upload globally).
            db_delete('course_assistants', 'course_id = ?', [$courseId]);
            foreach ($assistantIds as $aid) {
                if ($aid > 0) {
                    try {
                        db_insert('course_assistants', ['user_id' => $aid, 'course_id' => $courseId]);
                    } catch (Throwable $e) {
                        // ignore duplicates
                    }
                }
            }

            redirect_with(base_url('admin/courses.php'), 'success', $msg);
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $course = db_select_one('SELECT * FROM courses WHERE id = ?', [$id]);
        if ($course) {
            // Remove files from disk for all items in the course
            $itemIds = db_select('SELECT id FROM items WHERE course_id = ?', [$id]);
            foreach ($itemIds as $it) {
                upload_delete_item_files((int)$it['id']);
            }
            db_delete('courses', 'id = ?', [$id]);
            redirect_with(base_url('admin/courses.php'), 'success', t('course.deleted'));
        }
        $errors[] = t('common.error');
    }
}

$courses = db_select(
    'SELECT c.*, t.name AS term_name, t.is_current,
            (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id) AS item_count,
            (SELECT GROUP_CONCAT(u.name SEPARATOR ", ") FROM course_lecturers cl JOIN users u ON u.id = cl.user_id WHERE cl.course_id = c.id) AS lecturer_names,
            (SELECT GROUP_CONCAT(u.name SEPARATOR ", ") FROM course_assistants ca JOIN users u ON u.id = ca.user_id WHERE ca.course_id = c.id) AS assistant_names
     FROM courses c JOIN terms t ON t.id = c.term_id
     ORDER BY t.is_current DESC, t.created_at DESC, c.name'
);

// Map assistants per course for edit modals
$assistantsByCourse = [];
foreach (db_select('SELECT * FROM course_assistants') as $ca) {
    $assistantsByCourse[(int)$ca['course_id']][] = (int)$ca['user_id'];
}

// Map lecturers per course for edit modals
$lecturersByCourse = [];
foreach (db_select('SELECT * FROM course_lecturers') as $cl) {
    $lecturersByCourse[(int)$cl['course_id']][] = (int)$cl['user_id'];
}

$pageTitle = t('nav.courses') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('courses'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0"><?= e(t('nav.courses')) ?></h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCourseModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('course.create')) ?>
            </button>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if (!$terms): ?>
            <div class="lc-card"><?php lc_empty_state(t('term.no_terms'), t('term.create'), 'bi-calendar-range', base_url('admin/terms.php'), t('term.create')); ?></div>
        <?php else: ?>
            <div class="lc-card">
                <div class="lc-card-body p-0 p-md-2">
                    <?php if ($courses): ?>
                        <table class="lc-table">
                            <thead>
                                <tr>
                                    <th><?= e(t('course.name')) ?></th>
                                    <th><?= e(t('nav.terms')) ?></th>
                                    <th><?= e(t('course.instructor')) ?></th>
                                    <th><?= e(t('admin.stats.items')) ?></th>
                                    <th><?= e(t('course.staff')) ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $c): ?>
                                    <tr>
                                        <td data-label="<?= ea(t('course.name')) ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="course-card-icon" style="width:32px;height:32px;font-size:16px;--course-color:<?= ea($c['color'] ?: '#12355B') ?>" aria-hidden="true">
                                                    <i class="bi bi-journal-bookmark-fill"></i>
                                                </span>
                                                <div class="min-w-0">
                                                    <strong class="lc-wrap d-block"><?= e($c['name']) ?></strong>
                                                    <?php if ($c['code']): ?><span class="course-card-code"><?= e($c['code']) ?></span><?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="<?= ea(t('nav.terms')) ?>">
                                            <span class="lc-wrap"><?= e($c['term_name']) ?></span>
                                            <?php if ($c['is_current']): ?>
                                                <span class="badge-soft-success badge-type"><?= e(t('term.current_badge')) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td data-label="<?= ea(t('course.instructor')) ?>"><?= e($c['instructor'] ?: '—') ?></td>
                                        <td data-label="<?= ea(t('admin.stats.items')) ?>"><?= (int)$c['item_count'] ?></td>
                                        <td data-label="<?= ea(t('course.staff')) ?>" class="small">
                                            <?php if ($c['lecturer_names']): ?>
                                                <div><span class="text-muted"><?= e(t('role.lecturer')) ?>:</span> <?= e($c['lecturer_names']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($c['assistant_names']): ?>
                                                <div><span class="text-muted"><?= e(t('role.assistant')) ?>:</span> <?= e($c['assistant_names']) ?></div>
                                            <?php endif; ?>
                                            <?php if (!$c['lecturer_names'] && !$c['assistant_names']): ?>—<?php endif; ?>
                                        </td>
                                        <td data-label="">
                                            <div class="d-flex flex-wrap justify-content-end gap-1">
                                                <a href="<?= ea(base_url('course.php?id=' . (int)$c['id'])) ?>" class="btn btn-sm btn-ghost" aria-label="<?= ea(t('item.preview')) ?>">
                                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-ghost" data-bs-toggle="modal"
                                                        data-bs-target="#editCourse<?= (int)$c['id'] ?>" aria-label="<?= ea(t('common.edit')) ?>">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                </button>
                                                <form method="post" action="<?= base_url('admin/courses.php') ?>" class="d-inline"
                                                      data-confirm="<?= ea(t('course.delete') . '؟') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger-soft" aria-label="<?= ea(t('common.delete')) ?>">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <?php lc_empty_state(t('course.no_courses'), t('course.create'), 'bi-journals'); ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Shared form fields for create/edit
$renderCourseFields = function (array $course = [], int $idSuffix = 0) use ($terms, $assistants, $assistantsByCourse, $lecturers, $lecturersByCourse) {
    $assigned = $idSuffix ? ($assistantsByCourse[$idSuffix] ?? []) : [];
    $assignedLecturers = $idSuffix ? ($lecturersByCourse[$idSuffix] ?? []) : [];
    ?>
    <div class="row g-3">
        <div class="col-12">
            <label class="form-label" for="term_id_<?= $idSuffix ?>"><?= e(t('nav.terms')) ?> <span class="text-danger">*</span></label>
            <select class="form-select" id="term_id_<?= $idSuffix ?>" name="term_id" required>
                <?php foreach ($terms as $term): ?>
                    <option value="<?= (int)$term['id'] ?>" <?= (int)($course['term_id'] ?? 0) === (int)$term['id'] ? 'selected' : '' ?>>
                        <?= e($term['name']) ?><?= $term['is_current'] ? ' — ' . e(t('term.current_badge')) : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" for="name_<?= $idSuffix ?>"><?= e(t('course.name')) ?> <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="name_<?= $idSuffix ?>" name="name" maxlength="150"
                   value="<?= ea($course['name'] ?? '') ?>" required>
        </div>
        <div class="col-6">
            <label class="form-label" for="code_<?= $idSuffix ?>"><?= e(t('course.code')) ?></label>
            <input type="text" class="form-control" id="code_<?= $idSuffix ?>" name="code" maxlength="30"
                   value="<?= ea($course['code'] ?? '') ?>">
        </div>
        <div class="col-6">
            <label class="form-label" for="color_<?= $idSuffix ?>"><?= e(t('course.color')) ?></label>
            <input type="color" class="form-control form-control-color w-100" id="color_<?= $idSuffix ?>" name="color"
                   value="<?= ea($course['color'] ?? '#12355B') ?>">
        </div>
        <div class="col-12">
            <label class="form-label" for="instructor_<?= $idSuffix ?>"><?= e(t('course.instructor')) ?></label>
            <input type="text" class="form-control" id="instructor_<?= $idSuffix ?>" name="instructor" maxlength="100"
                   value="<?= ea($course['instructor'] ?? '') ?>">
        </div>
        <div class="col-12">
            <label class="form-label d-block"><?= e(t('role.lecturer')) ?></label>
            <?php if ($lecturers): ?>
                <div class="d-flex flex-column gap-1">
                    <?php foreach ($lecturers as $l): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="lecturers[]"
                                   id="lecturer_<?= $idSuffix ?>_<?= (int)$l['id'] ?>" value="<?= (int)$l['id'] ?>"
                                   <?= in_array((int)$l['id'], $assignedLecturers, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="lecturer_<?= $idSuffix ?>_<?= (int)$l['id'] ?>">
                                <?= e($l['name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text"><?= e(t('course.lecturers_help')) ?></div>
            <?php else: ?>
                <div class="form-text">
                    <?= e(t('course.no_lecturers')) ?>
                    <a href="<?= base_url('admin/invites.php') ?>"><?= e(t('nav.invites')) ?></a>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($assistants): ?>
            <div class="col-12">
                <label class="form-label d-block"><?= e(t('role.assistant')) ?></label>
                <div class="d-flex flex-column gap-1">
                    <?php foreach ($assistants as $a): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="assistants[]"
                                   id="assistant_<?= $idSuffix ?>_<?= (int)$a['id'] ?>" value="<?= (int)$a['id'] ?>"
                                   <?= in_array((int)$a['id'], $assigned, true) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="assistant_<?= $idSuffix ?>_<?= (int)$a['id'] ?>">
                                <?= e($a['name']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-text"><?= e(t('course.assistants_help')) ?></div>
            </div>
        <?php endif; ?>
    </div>
    <?php
};
?>

<!-- Create course modal -->
<div class="modal fade" id="createCourseModal" tabindex="-1" aria-labelledby="createCourseLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="post" action="<?= base_url('admin/courses.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h2 class="modal-title h5" id="createCourseLabel"><?= e(t('course.create')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
            </div>
            <div class="modal-body"><?php $renderCourseFields(); ?></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit course modals -->
<?php foreach ($courses as $c): ?>
    <div class="modal fade" id="editCourse<?= (int)$c['id'] ?>" tabindex="-1" aria-labelledby="editCourseLabel<?= (int)$c['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <form class="modal-content" method="post" action="<?= base_url('admin/courses.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editCourseLabel<?= (int)$c['id'] ?>"><?= e(t('course.edit')) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
                </div>
                <div class="modal-body"><?php $renderCourseFields($c, (int)$c['id']); ?></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>
