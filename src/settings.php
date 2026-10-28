<?php
/**
 * Application settings stored in the database.
 * Used for integration values that admins should be able to change without code edits.
 */

if (!function_exists('setting_get')) {
    function setting_get(string $key, ?string $default = null): ?string
    {
        try {
            $row = db_select_one(
                'SELECT setting_value FROM app_settings WHERE setting_key = ?',
                [$key]
            );
        } catch (Throwable $e) {
            // Keep pages usable before the additive migration has run.
            error_log('Setting read failed for ' . $key . ': ' . $e->getMessage());
            return $default;
        }

        if (!$row || $row['setting_value'] === null || $row['setting_value'] === '') {
            return $default;
        }

        return (string)$row['setting_value'];
    }
}

if (!function_exists('setting_set')) {
    function setting_set(string $key, ?string $value, ?int $userId = null): void
    {
        $existing = db_select_one(
            'SELECT setting_key FROM app_settings WHERE setting_key = ?',
            [$key]
        );

        if ($existing) {
            db_update('app_settings', [
                'setting_value' => $value,
                'updated_by' => $userId,
            ], 'setting_key = ?', [$key]);
            return;
        }

        db_insert('app_settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'updated_by' => $userId,
        ]);
    }
}

if (!function_exists('normalize_whatsapp_group_url')) {
    function normalize_whatsapp_group_url(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $host = strtolower((string)($parts['host'] ?? ''));
        $path = trim((string)($parts['path'] ?? ''), '/');

        if ($scheme !== 'https' || $host !== 'chat.whatsapp.com' || $path === '') {
            return null;
        }

        // Store a stable, query-free group invite link.
        return 'https://chat.whatsapp.com/' . $path;
    }
}

if (!function_exists('whatsapp_group_url')) {
    function whatsapp_group_url(): ?string
    {
        static $loaded = false;
        static $url = null;

        if ($loaded) {
            return $url;
        }

        $loaded = true;
        $stored = setting_get('whatsapp_group_url');
        $url = $stored ? normalize_whatsapp_group_url($stored) : null;
        return $url;
    }
}
