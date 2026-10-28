<?php
/**
 * Live content versioning.
 *
 * Every content mutation increments a single row in `content_version`.
 * Open pages poll this number and refresh their main content only when it
 * changes, so users see new items/announcements without reloading.
 */

if (!function_exists('live_version')) {
    function live_version(): int
    {
        try {
            $row = db_select_one('SELECT `version` FROM `content_version` WHERE `id` = 1');
            if ($row) {
                return max(1, (int)$row['version']);
            }

            $pdo = $GLOBALS['pdo'] ?? null;
            if ($pdo) {
                $pdo->exec('INSERT IGNORE INTO `content_version` (`id`, `version`) VALUES (1, 1)');
            }
            return 1;
        } catch (Throwable $e) {
            // Keep the app usable if the table has not been created yet.
            error_log('Live version read failed: ' . $e->getMessage());
            return 1;
        }
    }
}

if (!function_exists('live_bump')) {
    function live_bump(): void
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) {
            return;
        }

        try {
            $pdo->exec('INSERT IGNORE INTO `content_version` (`id`, `version`) VALUES (1, 1)');
            $pdo->exec('UPDATE `content_version` SET `version` = `version` + 1 WHERE `id` = 1');
        } catch (Throwable $e) {
            // A missing version table must never break a content write.
            error_log('Live version bump failed: ' . $e->getMessage());
        }
    }
}

if (!function_exists('live_bump_for_write')) {
    /**
     * Decide whether a DB write affects content visible on live pages.
     *
     * @param string $operation insert|update|delete
     * @param string $table
     * @param array  $data      written columns (for update/insert)
     */
    function live_bump_for_write(string $operation, string $table, array $data = []): void
    {
        static $contentTables = [
            'terms',
            'courses',
            'course_assistants',
            'course_lecturers',
            'items',
            'files',
            'announcements',
            'app_settings',
        ];

        if (in_array($table, $contentTables, true)) {
            live_bump();
            return;
        }

        // Names/roles/activity affect author labels and availability, but
        // theme, language, last_seen_at, and password changes do not.
        if ($table === 'users' && $operation !== 'insert') {
            if (array_intersect(array_keys($data), ['name', 'role', 'is_active'])) {
                live_bump();
            }
        }
    }
}
