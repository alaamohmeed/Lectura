<?php
/**
 * Admin: Users management (activate/deactivate, reset password, role)
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();
$errors = [];

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? '';
    $targetId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'toggle_active') {
        $result = auth_admin_toggle_active((int)$user['id'], $targetId);
        if ($result['success']) {
            redirect_with(base_url('admin/users.php'), 'success', $result['is_active'] ? t('user.activate') : t('user.deactivate'));
        }
        $errors[] = $result['error'];
    } elseif ($action === 'reset_password') {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (strlen($new) < 8) {
            $errors[] = t('user.weak') . ' — min 8';
        } elseif ($new !== $confirm) {
            $errors[] = t('auth.confirm_password');
        } else {
            $result = auth_admin_reset_password((int)$user['id'], $targetId, $new);
            if ($result['success']) {
                redirect_with(base_url('admin/users.php'), 'success', t('auth.password_reset'));
            }
            $errors[] = $result['error'];
        }
    } elseif ($action === 'update_role') {
        $role = $_POST['role'] ?? 'student';
        if (!in_array($role, ['admin', 'lecturer', 'assistant', 'student'], true)) {
            $errors[] = t('common.error');
        } elseif ($targetId === (int)$user['id'] && $role !== 'admin') {
            $errors[] = t('auth.cannot_deactivate_self');
        } else {
            db_update('users', ['role' => $role], 'id = ?', [$targetId]);
            // Remove assignments that no longer apply to the new role.
            if ($role !== 'assistant') {
                db_delete('course_assistants', 'user_id = ?', [$targetId]);
            }
            if ($role !== 'lecturer') {
                db_delete('course_lecturers', 'user_id = ?', [$targetId]);
            }
            redirect_with(base_url('admin/users.php'), 'success', t('common.success'));
        }
    }
}

$users = db_select(
    'SELECT u.*,
            (SELECT COUNT(*) FROM items i WHERE i.uploaded_by = u.id) AS upload_count
     FROM users u
     ORDER BY FIELD(u.role, "admin", "lecturer", "assistant", "student"), u.created_at DESC'
);

$pageTitle = t('nav.users') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('users'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <h1 class="h3 mb-0"><?= e(t('nav.users')) ?></h1>
            <a href="<?= base_url('admin/invites.php') ?>" class="btn btn-primary">
                <i class="bi bi-envelope-plus" aria-hidden="true"></i> <?= e(t('invite.create')) ?>
            </a>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <div class="lc-card">
            <div class="lc-card-body p-0 p-md-2">
                <table class="lc-table">
                    <thead>
                        <tr>
                            <th><?= e(t('user.name')) ?></th>
                            <th><?= e(t('user.role')) ?></th>
                            <th><?= e(t('user.lang')) ?></th>
                            <th><?= e(t('user.is_active')) ?></th>
                            <th><?= e(t('admin.stats.items')) ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td data-label="<?= ea(t('user.name')) ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-sm" aria-hidden="true"><?= e(mb_substr($u['name'], 0, 1)) ?></span>
                                        <div class="min-w-0">
                                            <strong class="lc-wrap d-block"><?= e($u['name']) ?></strong>
                                            <span class="item-meta lc-wrap"><?= e($u['email']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="<?= ea(t('user.role')) ?>">
                                    <span class="badge-type <?= e(role_badge_class($u['role'])) ?>">
                                        <?= e(t("role.{$u['role']}")) ?>
                                    </span>
                                </td>
                                <td data-label="<?= ea(t('user.lang')) ?>"><?= e(strtoupper($u['lang'])) ?></td>
                                <td data-label="<?= ea(t('user.is_active')) ?>">
                                    <?php if ($u['is_active']): ?>
                                        <span class="badge-soft-success badge-type"><?= e(t('user.active')) ?></span>
                                    <?php else: ?>
                                        <span class="badge-soft-danger badge-type"><?= e(t('user.inactive')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="<?= ea(t('admin.stats.items')) ?>"><?= (int)$u['upload_count'] ?></td>
                                <td data-label="">
                                    <div class="d-flex flex-wrap justify-content-end gap-1">
                                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                                            <button type="button" class="btn btn-sm btn-ghost" data-bs-toggle="modal"
                                                    data-bs-target="#editUser<?= (int)$u['id'] ?>" title="<?= ea(t('user.role')) ?>">
                                                <i class="bi bi-person-gear" aria-hidden="true"></i>
                                            </button>
                                            <form method="post" action="<?= base_url('admin/users.php') ?>" class="d-inline"
                                                  data-confirm="<?= ea(($u['is_active'] ? t('user.deactivate') : t('user.activate')) . '؟') ?>">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-danger-soft' : 'btn-outline-primary' ?>">
                                                    <?= e($u['is_active'] ? t('user.deactivate') : t('user.activate')) ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- User modals -->
<?php foreach ($users as $u): ?>
    <div class="modal fade" id="editUser<?= (int)$u['id'] ?>" tabindex="-1" aria-labelledby="editUserLabel<?= (int)$u['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="editUserLabel<?= (int)$u['id'] ?>"><?= e(t('user.name')) ?>: <?= e($u['name']) ?></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= ea(t('common.close')) ?>"></button>
                </div>
                <div class="modal-body">
                    <form method="post" action="<?= base_url('admin/users.php') ?>" class="mb-4">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update_role">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <label class="form-label" for="role<?= (int)$u['id'] ?>"><?= e(t('user.role')) ?></label>
                        <div class="d-flex gap-2">
                            <select class="form-select" id="role<?= (int)$u['id'] ?>" name="role">
                                <?php foreach (['student', 'assistant', 'lecturer', 'admin'] as $r): ?>
                                    <option value="<?= e($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e(t("role.{$r}")) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary"><?= e(t('common.save')) ?></button>
                        </div>
                    </form>

                    <hr>

                    <form method="post" action="<?= base_url('admin/users.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                        <h3 class="h6 mb-2"><?= e(t('user.reset_password')) ?></h3>
                        <div class="mb-2">
                            <label class="form-label" for="newpw<?= (int)$u['id'] ?>"><?= e(t('user.new_password')) ?></label>
                            <input type="password" class="form-control" id="newpw<?= (int)$u['id'] ?>" name="new_password"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="confirmpw<?= (int)$u['id'] ?>"><?= e(t('auth.confirm_password')) ?></label>
                            <input type="password" class="form-control" id="confirmpw<?= (int)$u['id'] ?>" name="confirm_password"
                                   minlength="8" required autocomplete="new-password">
                        </div>
                        <button type="submit" class="btn btn-danger-soft"><?= e(t('user.reset_password')) ?></button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal"><?= e(t('common.close')) ?></button>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>