<?php
/**
 * Notifications
 * Handles WhatsApp sharing, email notifications, and in-site feed
 */

// Send notifications for new item
if (!function_exists('notify_new_item')) {
    function notify_new_item(array $item, array $course): void
    {
        // Email can take time for large cohorts; don't hit max_execution_time
        @set_time_limit(0);

        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;

        // Get all active students with email notifications enabled
        $stmt = $pdo->prepare('
            SELECT id, name, email, lang FROM users
            WHERE role = "student" AND is_active = 1 AND email_notify = 1
        ');
        $stmt->execute();
        $students = $stmt->fetchAll();

        if (empty($students)) return;

        $itemUrl = base_url("item.php?id={$item['id']}");

        // Send emails asynchronously (fire and forget with logging)
        foreach ($students as $student) {
            // Log notification attempt
            $logId = db_insert('notification_log', [
                'item_id' => $item['id'],
                'user_id' => $student['id'],
                'channel' => 'email',
                'status' => 'sent', // Will update to failed if error
                'created_at' => utc_now(),
            ]);

            try {
                $result = mailer_send_item_notification($student, $item, $course, $itemUrl);
                if (!$result['success']) {
                    db_update('notification_log', [
                        'status' => 'failed',
                        'error' => $result['error'] ?? 'Unknown error',
                    ], 'id = ?', [$logId]);
                    error_log("Email notification failed for user {$student['id']}: " . ($result['error'] ?? 'Unknown'));
                }
            } catch (Throwable $e) {
                db_update('notification_log', [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ], 'id = ?', [$logId]);
                error_log("Email notification exception for user {$student['id']}: " . $e->getMessage());
            }
        }
    }
}

// Send notifications for new announcement
if (!function_exists('notify_new_announcement')) {
    function notify_new_announcement(array $announcement): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return;

        $stmt = $pdo->prepare('
            SELECT id, name, email, lang FROM users
            WHERE role = "student" AND is_active = 1 AND email_notify = 1
        ');
        $stmt->execute();
        $students = $stmt->fetchAll();

        if (empty($students)) return;

        $siteUrl = base_url('index.php');

        foreach ($students as $student) {
            $logId = db_insert('notification_log', [
                'announcement_id' => $announcement['id'],
                'user_id' => $student['id'],
                'channel' => 'email',
                'status' => 'sent',
                'created_at' => utc_now(),
            ]);

            try {
                $result = mailer_send_announcement_notification($student, $announcement, $siteUrl);
                if (!$result['success']) {
                    db_update('notification_log', [
                        'status' => 'failed',
                        'error' => $result['error'] ?? 'Unknown error',
                    ], 'id = ?', [$logId]);
                }
            } catch (Throwable $e) {
                db_update('notification_log', [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ], 'id = ?', [$logId]);
            }
        }
    }
}

// Get latest updates for home page feed
if (!function_exists('notify_get_latest_updates')) {
    function notify_get_latest_updates(int $limit = 20): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];

        $user = $GLOBALS['current_user'] ?? null;
        $lastSeen = $user['last_seen_at'] ?? null;

        // Get latest items
        $stmt = $pdo->prepare('
            SELECT i.*, c.name as course_name, c.color as course_color, u.name as uploader_name,
                   "item" as source_type
            FROM items i
            JOIN courses c ON c.id = i.course_id
            JOIN users u ON u.id = i.uploaded_by
            WHERE c.term_id = (SELECT id FROM terms WHERE is_current = 1 LIMIT 1)
            ORDER BY i.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        $items = $stmt->fetchAll();

        // Get latest announcements
        $stmt = $pdo->prepare('
            SELECT a.*, u.name as author_name, "announcement" as source_type
            FROM announcements a
            JOIN users u ON u.id = a.created_by
            ORDER BY a.created_at DESC
            LIMIT ?
        ');
        $stmt->execute([$limit]);
        $announcements = $stmt->fetchAll();

        // Merge and sort by date
        $updates = array_merge($items, $announcements);
        usort($updates, fn($a, $b) => strtotime($b['created_at'] . ' UTC') <=> strtotime($a['created_at'] . ' UTC'));

        // Limit and mark "new" items
        $updates = array_slice($updates, 0, $limit);
        // If the user has never visited, treat the last 7 days as "new"
        $effectiveLastSeen = $lastSeen ?: gmdate('Y-m-d H:i:s', time() - 7 * 86400);
        foreach ($updates as &$update) {
            $update['is_new'] = strtotime($update['created_at'] . ' UTC') > strtotime($effectiveLastSeen . ' UTC');
        }

        return $updates;
    }
}

// Get upcoming deadlines (assignments due soon)
if (!function_exists('notify_get_upcoming_deadlines')) {
    function notify_get_upcoming_deadlines(int $limit = 3): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];

        $user = $GLOBALS['current_user'] ?? null;
        if (!$user) return [];

        // Admin sees every course; lecturers see only assigned courses.
        // Assistants are global uploaders, so they see current-term deadlines like students.
        if ($user['role'] === 'admin') {
            $courseCondition = '1=1';
            $params = [];
        } elseif ($user['role'] === 'lecturer') {
            $courseIds = array_column(get_manageable_courses($user), 'id');
            if (empty($courseIds)) return [];
            $placeholders = implode(',', array_fill(0, count($courseIds), '?'));
            $courseCondition = "i.course_id IN ($placeholders)";
            $params = $courseIds;
        } else {
            $courseCondition = 'c.term_id = (SELECT id FROM terms WHERE is_current = 1 LIMIT 1)';
            $params = [];
        }

        $sql = "
            SELECT i.*, c.name as course_name, c.color as course_color
            FROM items i
            JOIN courses c ON c.id = i.course_id
            WHERE i.type = 'assignment'
            AND i.due_at IS NOT NULL
            AND i.due_at > NOW()
            AND $courseCondition
            ORDER BY i.due_at ASC
            LIMIT ?
        ";

        $params[] = $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

// Get deadline status (normal, due_soon, overdue)
if (!function_exists('notify_get_deadline_status')) {
    function notify_get_deadline_status(?string $dueAt): string
    {
        if (!$dueAt) return 'normal';

        $due = new DateTime($dueAt, new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $diff = $now->diff($due);
        $totalHours = ($diff->days * 24) + $diff->h + ($diff->i / 60);

        if ($diff->invert || $totalHours <= 0) return 'overdue';
        if ($totalHours <= 24) return 'due_soon';
        return 'normal';
    }
}

// Format deadline countdown text
if (!function_exists('notify_format_countdown')) {
    function notify_format_countdown(?string $dueAt): string
    {
        if (!$dueAt) return '';

        $due = new DateTime($dueAt, new DateTimeZone('UTC'));
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $diff = $now->diff($due);

        $lang = current_lang();

        if ($diff->invert) {
            // Overdue
            $days = (int)$diff->days;
            if ($days > 0) {
                return $lang === 'ar'
                    ? "متأخر " . ar_counted_phrase($days, 'day') . ($diff->h ? " و " . ar_counted_phrase($diff->h, 'hour') : "")
                    : "Overdue by {$days} day" . ($days === 1 ? '' : 's') . ($diff->h ? " {$diff->h}h" : '');
            }
            if ($diff->h > 0) {
                return $lang === 'ar'
                    ? "متأخر " . ar_counted_phrase($diff->h, 'hour')
                    : "Overdue by {$diff->h} hour" . ($diff->h === 1 ? '' : 's');
            }
            return $lang === 'ar' ? 'متأخر' : 'Overdue';
        }

        // Upcoming
        $days = (int)$diff->days;
        if ($days > 0) {
            return $lang === 'ar'
                ? "باقي " . ar_counted_phrase($days, 'day') . ($diff->h ? " و " . ar_counted_phrase($diff->h, 'hour') : "")
                : "{$days} day" . ($days === 1 ? '' : 's') . ($diff->h ? " {$diff->h}h" : '') . " left";
        }
        if ($diff->h > 0) {
            return $lang === 'ar'
                ? "باقي " . ar_counted_phrase($diff->h, 'hour')
                : "{$diff->h} hour" . ($diff->h === 1 ? '' : 's') . " left";
        }
        if ($diff->i > 0) {
            return $lang === 'ar'
                ? "باقي " . ar_counted_phrase($diff->i, 'minute')
                : "{$diff->i} minute" . ($diff->i === 1 ? '' : 's') . " left";
        }

        return $lang === 'ar' ? 'الآن' : 'Due now';
    }
}

// Get notification stats for admin dashboard
if (!function_exists('notify_get_stats')) {
    function notify_get_stats(): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];

        $stmt = $pdo->query('
            SELECT
                COUNT(CASE WHEN status = "sent" THEN 1 END) as sent,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed
            FROM notification_log
            WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ');
        return $stmt->fetch() ?? ['sent' => 0, 'failed' => 0];
    }
}