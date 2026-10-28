<?php
/**
 * Admin: Terms CRUD
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();
$errors = [];

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = t('term.name') . ': ' . t('common.required');
        } else {
            db_insert('terms', ['name' => $name, 'is_current' => 0, 'created_at' => utc_now()]);
            redirect_with(base_url('admin/terms.php'), 'success', t('term.created'));
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = t('term.name') . ': ' . t('common.required');
        } else {
            db_update('terms', ['name' => $name], 'id = ?', [$id]);
            redirect_with(base_url('admin/terms.php'), 'success', t('term.updated'));
        }
    } elseif ($action === 'set_current') {
        $id = (int)($_POST['id'] ?? 0);
        if (db_select_one('SELECT id FROM terms WHERE id = ?', [$id])) {
            db_transaction(function () use ($id) {
                $GLOBALS['pdo']->exec('UPDATE terms SET is_current = 0');
                db_update('terms', ['is_current' => 1], 'id = ?', [$id]);
            });
            redirect_with(base_url('admin/terms.php'), 'success', t('term.updated'));
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $term = db_select_one('SELECT * FROM terms WHERE id = ?', [$id]);
        if (!$term) {
            $errors[] = t('term.no_terms');
        } elseif ($term['is_current']) {
            $errors[] = t('term.cannot_delete_current');
        } else {
            $courseCount = (int)(db_select_one('SELECT COUNT(*) AS n FROM courses WHERE term_id = ?', [$id])['n'] ?? 0);
            if ($courseCount > 0) {
                $errors[] = t('term.has_courses');
            } else {
                db_delete('terms', 'id = ?', [$id]);
                redirect_with(base_url('admin/terms.php'), 'success', t('term.deleted'));
            }
        }
    }
}

$terms = db_select(
    'SELECT t.*,
            (SELECT COUNT(*) FROM courses c WHERE c.term_id = t.id) AS course_count,
            (SELECT COUNT(*) FROM items i JOIN courses c2 ON c2.id = i.course_id WHERE c2.term_id = t.id) AS item_count
     FROM terms t
     ORDER BY t.is_current DESC, t.created_at DESC'
);

$pageTitle = t('nav.terms') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('terms'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0"><?= e(t('nav.terms')) ?></h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTermModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i> <?= e(t('term.create')) ?>
            </button>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="lc-card">
            <div class="lc-card-body p-0 p-md-2">
                <?php if ($terms): ?>
                    <table class="lc-table">
                        <thead>
                            <tr>
                                <th><?= e(t('term.name')) ?></th>
                                <th><?= e(t('nav.courses')) ?></th>
                                <th><?= e(t('admin.stats.items')) ?></th>
                                <th><?= e(t('user.is_active')) ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terms as $term): ?>
                                <tr>
                                    <td data-label="<?= ea(t('term.name')) ?>">
                                        <strong class="lc-wrap"><?= e($term['name']) ?></strong>
                                    </td>
                                    <td data-label="<?= ea(t('nav.courses')) ?>"><?= (int)$term['course_count'] ?></td>
                                    <td data-label="<?= ea(t('admin.stats.items')) ?>"><?= (int)$term['item_count'] ?></td>
                                    <td data-label="<?= ea(t('user.is_active')) ?>">
                                        <?php if ($term['is_current']): ?>
                                            <span class="badge-soft-success badge-type"><i class="bi bi-check-circle" aria-hidden="true"></i> <?= e(t('term.current_badge')) ?></span>
                                        <?php else: ?>
                                            <span class="badge-soft-muted badge-type"><?= e(t('term.archived_badge')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="">
                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                            <?php if (!$term['is_current']): ?>
                                                <form method="post" action="<?= base_url('admin/terms.php') ?>" class="d-inline">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="set_current">
                                                    <input type="hidden" name="id" value="<?= (int)$term['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-primary"><?= e(t('term.set_current')) ?></button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-ghost" data-bs-toggle="modal"
                                                    data-bs-target="#editTerm<?= (int)$term['id'] ?>" aria-label="<?= ea(t('common.edit')) ?>">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </button>
                                            <?php if (!$term['is_current']): ?>
                                                <form method="post" action="<?= base_url('admin/terms.php') ?>" class="d-inline"
                                                      data-confirm="<?= ea(t('term.delete') . '؟') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$term['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger-soft" aria-label="<?= ea(t('common.delete')) ?>">
                                                        <i class="bi bi-trash" aria-hidden="true"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <?php lc_empty_state(t('term.no_terms'), t('term.create'), 'bi-calendar-range'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create modal -->
<div class="modal fade" id="createTermModal" tabindex="-1" aria-labelledby="createTermLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="post" action="<?= base_url('admin/terms.php') ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-header">
                <h2 class="modal-title h5" id="createTermLabel"><?= e(t('term.create')) ?></h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
            </div>
            <div class="modal-body">
                <label for="newTermName" class="form-label"><?= e(t('term.name')) ?></label>
                <input type="text" class="form-control" id="newTermName" name="name" maxlength="100" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit modals -->
<?php foreach ($terms as $term): ?>
    <div class="modal fade" id="editTerm<?= (int)$term['id'] ?>" tabindex="-1" aria-labelledby="editTermLabel<?= (int)$term['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form class="modal-content" method="post" action="<?= base_url('admin/terms.php') ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$term['id'] ?>">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editTermLabel<?= (int)$term['id'] ?>"><?= e(t('term.edit')) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
                </div>
                <div class="modal-body">
                    <label for="termName<?= (int)$term['id'] ?>" class="form-label"><?= e(t('term.name')) ?></label>
                    <input type="text" class="form-control" id="termName<?= (int)$term['id'] ?>" name="name"
                           maxlength="100" value="<?= ea($term['name']) ?>" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.cancel')) ?></button>
                    <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
                </div>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>