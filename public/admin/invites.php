<?php
/**
 * Admin: Invites — create one-time invite links and copy them
 */
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../../src/layout/partials.php';

$user = auth_require_admin();
$errors = [];
$newInviteUrl = null;
$newInviteRole = null;

$lcPageConfig = $GLOBALS['config']['invite'] ?? ['token_length' => 32, 'expiry_days' => 7];

if (is_post()) {
    csrf_protect();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $role = in_array(($_POST['role'] ?? 'student'), ['student', 'lecturer'], true)
            ? $_POST['role']
            : 'student';
        $token = bin2hex(random_bytes((int)$lcPageConfig['token_length']));
        $expires = (new DateTime('now', new DateTimeZone('UTC')))
            ->modify('+' . (int)$lcPageConfig['expiry_days'] . ' days')
            ->format('Y-m-d H:i:s');

        db_insert('invites', [
            'token' => $token,
            'created_by' => $user['id'],
            'role' => $role,
            'expires_at' => $expires,
            'created_at' => utc_now(),
        ]);

        $newInviteUrl = base_url('register.php?token=' . $token);
        $newInviteRole = $role;
        $_SESSION['last_invite_url'] = $newInviteUrl;
        // Stay on page to show + copy the link
    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $invite = db_select_one('SELECT * FROM invites WHERE id = ?', [$id]);
        if ($invite && !$invite['used_by']) {
            db_delete('invites', 'id = ?', [$id]);
            redirect_with(base_url('admin/invites.php'), 'success', t('common.success'));
        }
        $errors[] = t('common.error');
    }
}

$invites = db_select(
    'SELECT i.*, c.name AS creator_name, u.name AS used_by_name
     FROM invites i
     JOIN users c ON c.id = i.created_by
     LEFT JOIN users u ON u.id = i.used_by
     ORDER BY i.created_at DESC
     LIMIT 100'
);

$pageTitle = t('nav.invites') . ' — ' . t('app.name');
require __DIR__ . '/../../src/layout/header.php';
?>

<div class="admin-layout">
    <?php lc_admin_sidebar('invites'); ?>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
            <div>
                <h1 class="h3 mb-1"><?= e(t('nav.invites')) ?></h1>
                <p class="text-muted mb-0 small"><?= e(t('auth.invite_only')) ?> · <?= e(t('invite.expiry_days', ['days' => (int)$lcPageConfig['expiry_days']])) ?></p>
            </div>
            <form method="post" action="<?= base_url('admin/invites.php') ?>" class="d-flex flex-wrap align-items-center gap-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">
                <label for="inviteRole" class="visually-hidden"><?= e(t('invite.role')) ?></label>
                <select class="form-select form-select-sm" id="inviteRole" name="role" style="width:auto;min-width:150px">
                    <option value="student"><?= e(t('role.student')) ?></option>
                    <option value="lecturer"><?= e(t('role.lecturer')) ?></option>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-envelope-plus" aria-hidden="true"></i> <?= e(t('invite.create')) ?>
                </button>
            </form>
        </div>

        <?php if ($errors): ?>
            <div class="alert alert-danger" role="alert" aria-live="assertive">
                <ul class="mb-0 ps-3"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <?php if ($newInviteUrl || !empty($_SESSION['last_invite_url'])): ?>
            <?php $shownUrl = $newInviteUrl ?: $_SESSION['last_invite_url']; unset($_SESSION['last_invite_url']); ?>
            <div class="lc-card mb-4" style="border-color: var(--lc-success)">
                <div class="lc-card-body">
                    <h2 class="h6 mb-2"><i class="bi bi-check-circle text-success" aria-hidden="true"></i> <?= e(t('invite.title')) ?></h2>
                    <div class="input-group">
                        <input type="text" class="form-control" id="newInviteUrl" value="<?= ea($shownUrl) ?>" readonly aria-label="<?= ea(t('invite.token')) ?>">
                        <button type="button" class="btn btn-primary" data-copy="#newInviteUrl">
                            <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('invite.copy_link')) ?>
                        </button>
                    </div>
                    <?php if ($newInviteRole): ?>
                        <p class="form-text mb-0 mt-2"><?= e(t('invite.role')) ?>: <?= e(t('role.' . $newInviteRole)) ?></p>
                    <?php endif; ?>
                    <p class="form-text mb-0 mt-2"><?= e(t('invite.expires')) ?>: <?= e(t('invite.expiry_days', ['days' => (int)$lcPageConfig['expiry_days']])) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="lc-card">
            <div class="lc-card-body p-0 p-md-2">
                <?php if ($invites): ?>
                    <table class="lc-table">
                        <thead>
                            <tr>
                                <th><?= e(t('invite.token')) ?></th>
                                <th><?= e(t('invite.role')) ?></th>
                                <th><?= e(t('invite.expires')) ?></th>
                                <th><?= e(t('user.is_active')) ?></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invites as $inv): ?>
                                <?php
                                $url = base_url('register.php?token=' . $inv['token']);
                                $expired = !$inv['used_by'] && strtotime($inv['expires_at'] . ' UTC') < time();
                                ?>
                                <tr>
                                    <td data-label="<?= ea(t('invite.token')) ?>">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <code class="lc-wrap" style="font-size:12px"><?= e(substr($inv['token'], 0, 16)) ?>…</code>
                                            <?php if (!$inv['used_by'] && !$expired): ?>
                                                <button type="button" class="btn btn-sm btn-ghost" data-copy="<?= ea($url) ?>" aria-label="<?= ea(t('invite.copy_link')) ?>">
                                                    <i class="bi bi-clipboard" aria-hidden="true"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td data-label="<?= ea(t('invite.role')) ?>">
                                        <span class="badge-type <?= e(role_badge_class($inv['role'] ?? 'student')) ?>">
                                            <?= e(t('role.' . ($inv['role'] ?? 'student'))) ?>
                                        </span>
                                    </td>
                                    <td data-label="<?= ea(t('invite.expires')) ?>"><?= e(format_datetime(utc_datetime($inv['expires_at']), 'short')) ?></td>
                                    <td data-label="<?= ea(t('user.is_active')) ?>">
                                        <?php if ($inv['used_by']): ?>
                                            <span class="badge-soft-muted badge-type"><?= e(t('invite.used')) ?><?= $inv['used_by_name'] ? ': ' . e($inv['used_by_name']) : '' ?></span>
                                        <?php elseif ($expired): ?>
                                            <span class="badge-soft-danger badge-type"><?= e(t('invite.expired')) ?></span>
                                        <?php else: ?>
                                            <span class="badge-soft-success badge-type"><?= e(t('invite.unused')) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="">
                                        <div class="d-flex justify-content-end">
                                            <?php if (!$inv['used_by']): ?>
                                                <form method="post" action="<?= base_url('admin/invites.php') ?>" data-confirm="<?= ea(t('common.delete') . '؟') ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= (int)$inv['id'] ?>">
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
                    <?php lc_empty_state(t('invite.title'), t('invite.create'), 'bi-envelope-plus'); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../src/layout/footer.php'; ?>