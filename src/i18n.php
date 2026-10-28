<?php
/**
 * Internationalization (i18n)
 * Loads language files, handles language switching, and sets HTML direction
 */

// Initialize i18n system
if (!function_exists('i18n_init')) {
    function i18n_init(): void
    {
        $supported = ['ar', 'en'];
        $default = $GLOBALS['config']['app']['default_lang'] ?? 'ar';

        // Determine language: user preference > cookie > configured default
        $lang = $default;

        // 1. Logged-in user's preference
        if (!empty($GLOBALS['current_user']['lang']) && in_array($GLOBALS['current_user']['lang'], $supported, true)) {
            $lang = $GLOBALS['current_user']['lang'];
        }
        // 2. Cookie for logged-out users
        elseif (isset($_COOKIE['lectura_lang']) && in_array($_COOKIE['lectura_lang'], $supported, true)) {
            $lang = $_COOKIE['lectura_lang'];
        }
        // Otherwise use the configured default (Arabic by design)

        // Load translations
        $translations = [];
        foreach ($supported as $l) {
            $file = __DIR__ . "/../lang/{$l}.php";
            if (file_exists($file)) {
                $translations[$l] = require $file;
            } else {
                $translations[$l] = [];
            }
        }

        // Ensure default language has all keys (fallback)
        $allKeys = array_keys($translations[$default] ?? []);
        foreach ($supported as $l) {
            if ($l !== $default) {
                foreach ($allKeys as $key) {
                    if (!isset($translations[$l][$key])) {
                        $translations[$l][$key] = $translations[$default][$key];
                    }
                }
            }
        }

        $GLOBALS['i18n'] = [
            'current' => $lang,
            'supported' => $supported,
            'default' => $default,
            'translations' => $translations,
        ];

        // Set cookie for persistence (1 year)
        if (!headers_sent()) {
            setcookie('lectura_lang', $lang, [
                'expires' => time() + 3600 * 24 * 365,
                'path' => '/',
                'secure' => $GLOBALS['config']['session']['cookie_secure'] ?? true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
    }
}

// Switch language (called from language switcher endpoint)
if (!function_exists('i18n_switch')) {
    function i18n_switch(string $lang): bool
    {
        $supported = $GLOBALS['i18n']['supported'] ?? ['ar', 'en'];
        if (!in_array($lang, $supported, true)) {
            return false;
        }

        $GLOBALS['i18n']['current'] = $lang;

        // Update cookie
        if (!headers_sent()) {
            setcookie('lectura_lang', $lang, [
                'expires' => time() + 3600 * 24 * 365,
                'path' => '/',
                'secure' => $GLOBALS['config']['session']['cookie_secure'] ?? true,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        // Update user preference if logged in
        if (!empty($GLOBALS['current_user']['id'])) {
            db_update('users', ['lang' => $lang], 'id = ?', [$GLOBALS['current_user']['id']]);
            $GLOBALS['current_user']['lang'] = $lang;
        }

        return true;
    }
}

// Get current language code
if (!function_exists('i18n_current')) {
    function i18n_current(): string
    {
        return $GLOBALS['i18n']['current'] ?? 'ar';
    }
}

// Get current text direction
if (!function_exists('i18n_dir')) {
    function i18n_dir(): string
    {
        return i18n_current() === 'ar' ? 'rtl' : 'ltr';
    }
}

// Get Bootstrap CSS URL for current language (RTL build for Arabic)
if (!function_exists('bootstrap_css_url')) {
    function bootstrap_css_url(): string
    {
        $lang = i18n_current();
        $version = '5.3.3';
        if ($lang === 'ar') {
            return "https://cdn.jsdelivr.net/npm/bootstrap@{$version}/dist/css/bootstrap.rtl.min.css";
        }
        return "https://cdn.jsdelivr.net/npm/bootstrap@{$version}/dist/css/bootstrap.min.css";
    }
}

// Get available languages for switcher
if (!function_exists('i18n_languages')) {
    function i18n_languages(): array
    {
        return [
            'ar' => ['code' => 'ar', 'name' => 'العربية', 'native' => 'العربية', 'dir' => 'rtl', 'flag' => '🇸🇦'],
            'en' => ['code' => 'en', 'name' => 'English', 'native' => 'English', 'dir' => 'ltr', 'flag' => '🇺🇸'],
        ];
    }
}