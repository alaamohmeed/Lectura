<?php
/**
 * Authentication & Authorization
 * Handles login, logout, session management, rate limiting, and role checks
 */

// Get current user from session
if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        // Return cached user if available
        if (!empty($GLOBALS['current_user']) && $GLOBALS['current_user']['id'] === $_SESSION['user_id']) {
            return $GLOBALS['current_user'];
        }

        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return null;

        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND is_active = 1');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user) {
            $GLOBALS['current_user'] = $user;
            return $user;
        }

        // User not found or inactive - destroy session
        auth_logout();
        return null;
    }
}

// Require authentication (redirect to login if not authenticated)
if (!function_exists('auth_require')) {
    function auth_require(?string $redirect = null): array
    {
        $user = auth_user();
        if (!$user) {
            $target = $redirect ?? current_url();
            $_SESSION['redirect_after_login'] = $target;
            header('Location: ' . base_url('login.php'));
            exit;
        }
        return $user;
    }
}

// Require specific role
if (!function_exists('auth_require_role')) {
    function auth_require_role(string|array $roles, ?string $redirect = null): array
    {
        $user = auth_require($redirect);
        $roles = (array)$roles;
        if (!in_array($user['role'], $roles, true)) {
            http_response_code(403);
            if (is_ajax()) {
                header('Content-Type: application/json');
                exit(json_encode(['error' => t('auth.forbidden'), 'code' => 'FORBIDDEN']));
            }
            // Show friendly 403 page
            $pageTitle = t('errors.403.title');
            require_once __DIR__ . '/layout/header.php';
            echo '<main class="container py-5"><div class="text-center">';
            echo '<h1 class="display-1 text-danger">403</h1>';
            echo '<h2 class="mb-3">' . e(t('errors.403.title')) . '</h2>';
            echo '<p class="text-muted mb-4">' . e(t('errors.403.message')) . '</p>';
            echo '<a href="' . base_url('index.php') . '" class="btn btn-primary">' . e(t('common.back_home')) . '</a>';
            echo '</div></main>';
            require_once __DIR__ . '/layout/footer.php';
            exit;
        }
        return $user;
    }
}

// Require admin role
if (!function_exists('auth_require_admin')) {
    function auth_require_admin(?string $redirect = null): array
    {
        return auth_require_role('admin', $redirect);
    }
}

// Require admin, lecturer, or assistant role
if (!function_exists('auth_require_uploader')) {
    function auth_require_uploader(?string $redirect = null): array
    {
        return auth_require_role(['admin', 'lecturer', 'assistant'], $redirect);
    }
}

// Require admin or lecturer role
if (!function_exists('auth_require_announcement_manager')) {
    function auth_require_announcement_manager(?string $redirect = null): array
    {
        return auth_require_role(['admin', 'lecturer'], $redirect);
    }
}

// Login user
if (!function_exists('auth_login')) {
    function auth_login(string $email, string $password, bool $remember = false): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            throw new RuntimeException('Database not initialized');
        }

        $ip = get_client_ip();
        $config = $GLOBALS['config']['auth'] ?? ['max_failed_attempts' => 5, 'lockout_minutes' => 10];

        // Check rate limiting
        $lockoutUntil = null;
        $stmt = $pdo->prepare('
            SELECT COUNT(*) as attempts, MAX(created_at) as last_attempt
            FROM login_attempts
            WHERE email = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ');
        $stmt->execute([$email, $ip, $config['lockout_minutes']]);
        $attemptData = $stmt->fetch();

        if ($attemptData && $attemptData['attempts'] >= $config['max_failed_attempts']) {
            $lastAttempt = new DateTime($attemptData['last_attempt'], new DateTimeZone('UTC'));
            $lockoutUntil = clone $lastAttempt;
            $lockoutUntil->modify("+{$config['lockout_minutes']} minutes");
            $now = new DateTime('now', new DateTimeZone('UTC'));
            if ($now < $lockoutUntil) {
                $minutes = max(1, (int)ceil(($lockoutUntil->getTimestamp() - $now->getTimestamp()) / 60));
                return [
                    'success' => false,
                    'error' => t('auth.locked', ['minutes' => $minutes]),
                    'code' => 'RATE_LIMITED',
                ];
            }
        }

        // Find user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Record failed attempt (but don't reveal user doesn't exist)
            log_login_attempt($email, $ip, false);
            return [
                'success' => false,
                'error' => t('auth.invalid_credentials'),
                'code' => 'INVALID_CREDENTIALS',
            ];
        }

        if (!$user['is_active']) {
            return [
                'success' => false,
                'error' => t('auth.account_disabled'),
                'code' => 'ACCOUNT_DISABLED',
            ];
        }

        if (!password_verify($password, $user['password_hash'])) {
            log_login_attempt($email, $ip, false);
            return [
                'success' => false,
                'error' => t('auth.invalid_credentials'),
                'code' => 'INVALID_CREDENTIALS',
            ];
        }

        // Success - clear failed attempts
        clear_login_attempts($email, $ip);

        // Regenerate session ID
        session_regenerate_id(true);

        // Store user in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];

        $GLOBALS['current_user'] = $user;

        return [
            'success' => true,
            'user' => $user,
        ];
    }
}

// Logout user
if (!function_exists('auth_logout')) {
    function auth_logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
        $GLOBALS['current_user'] = null;
    }
}

// Log a login attempt
if (!function_exists('log_login_attempt')) {
    function log_login_attempt(string $email, string $ip, bool $success): void
    {
        if ($success) return; // Only log failures

        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;

        $stmt = $pdo->prepare('INSERT INTO login_attempts (email, ip, created_at) VALUES (?, ?, NOW())');
        $stmt->execute([$email, $ip]);
    }
}

// Clear login attempts for email+ip
if (!function_exists('clear_login_attempts')) {
    function clear_login_attempts(string $email, string $ip): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;

        $stmt = $pdo->prepare('DELETE FROM login_attempts WHERE email = ? AND ip = ?');
        $stmt->execute([$email, $ip]);
    }
}

// Register new user via invite token
if (!function_exists('auth_register')) {
    function auth_register(string $token, string $name, string $email, string $password): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            throw new RuntimeException('Database not initialized');
        }

        // Serialize invite use to guarantee single-use
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                SELECT * FROM invites
                WHERE token = ? AND used_by IS NULL AND expires_at > NOW()
                FOR UPDATE
            ');
            $stmt->execute([$token]);
            $invite = $stmt->fetch();

            if (!$invite) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => t('invite.invalid_or_expired'),
                    'code' => 'INVALID_INVITE',
                ];
            }

            // Check if email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                return [
                    'success' => false,
                    'error' => t('auth.email_exists'),
                    'code' => 'EMAIL_EXISTS',
                ];
            }

            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $inviteRole = in_array(($invite['role'] ?? 'student'), ['student', 'lecturer'], true)
                ? $invite['role']
                : 'student';

            // Create user
            $userId = db_insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => $hash,
                'role' => $inviteRole,
                'lang' => 'ar',
                'theme' => 'light',
                'is_active' => 1,
                'email_notify' => 1,
                'created_at' => utc_now(),
            ]);

            // Mark invite as used
            db_update('invites', [
                'used_by' => $userId,
                'used_at' => utc_now(),
            ], 'id = ?', [$invite['id']]);

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        // Log in the new user
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $inviteRole;
        $_SESSION['user_name'] = $name;

        $user = [
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => $inviteRole,
            'lang' => 'ar',
            'theme' => 'light',
            'is_active' => 1,
            'email_notify' => 1,
        ];
        $GLOBALS['current_user'] = $user;

        return [
            'success' => true,
            'user' => $user,
        ];
    }
}

// Change password
if (!function_exists('auth_change_password')) {
    function auth_change_password(int $userId, string $currentPassword, string $newPassword): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            throw new RuntimeException('Database not initialized');
        }

        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            return [
                'success' => false,
                'error' => t('auth.current_password_incorrect'),
                'code' => 'INVALID_CURRENT_PASSWORD',
            ];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        db_update('users', ['password_hash' => $hash], 'id = ?', [$userId]);

        return ['success' => true];
    }
}

// Admin: reset user password
if (!function_exists('auth_admin_reset_password')) {
    function auth_admin_reset_password(int $adminId, int $targetUserId, string $newPassword): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            throw new RuntimeException('Database not initialized');
        }

        // Verify admin
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        if (!$admin || $admin['role'] !== 'admin') {
            return ['success' => false, 'error' => t('auth.forbidden'), 'code' => 'FORBIDDEN'];
        }

        // Verify target user exists
        $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$targetUserId]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => t('auth.user_not_found'), 'code' => 'USER_NOT_FOUND'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        db_update('users', ['password_hash' => $hash], 'id = ?', [$targetUserId]);

        return ['success' => true];
    }
}

// Admin: toggle user active status
if (!function_exists('auth_admin_toggle_active')) {
    function auth_admin_toggle_active(int $adminId, int $targetUserId): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            throw new RuntimeException('Database not initialized');
        }

        // Verify admin
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        if (!$admin || $admin['role'] !== 'admin') {
            return ['success' => false, 'error' => t('auth.forbidden'), 'code' => 'FORBIDDEN'];
        }

        // Prevent self-deactivation
        if ($adminId === $targetUserId) {
            return ['success' => false, 'error' => t('auth.cannot_deactivate_self'), 'code' => 'SELF_DEACTIVATE'];
        }

        $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = ?');
        $stmt->execute([$targetUserId]);
        $user = $stmt->fetch();
        if (!$user) {
            return ['success' => false, 'error' => t('auth.user_not_found'), 'code' => 'USER_NOT_FOUND'];
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        db_update('users', ['is_active' => $newStatus], 'id = ?', [$targetUserId]);

        return ['success' => true, 'is_active' => (bool)$newStatus];
    }
}

// Update user preferences
if (!function_exists('auth_update_preferences')) {
    function auth_update_preferences(int $userId, array $prefs): array
    {
        $allowed = ['lang', 'theme', 'email_notify'];
        $data = array_intersect_key($prefs, array_flip($allowed));

        if (empty($data)) {
            return ['success' => false, 'error' => t('common.no_changes')];
        }

        db_update('users', $data, 'id = ?', [$userId]);

        // Update current user cache
        if (!empty($GLOBALS['current_user']) && $GLOBALS['current_user']['id'] === $userId) {
            $GLOBALS['current_user'] = array_merge($GLOBALS['current_user'], $data);
        }

        return ['success' => true];
    }
}

// Initialize current_user global on bootstrap
$GLOBALS['current_user'] = auth_user();
