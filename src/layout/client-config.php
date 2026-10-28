<?php
/**
 * Client-side configuration shared by every page that loads app.js.
 * Keeps URLs and UI strings out of the JavaScript files.
 */
?>
    <script>
        window.LC_BASE = <?= json_encode(rtrim(base_url(), '/'), JSON_UNESCAPED_SLASHES) ?>;
        window.LC_I18N = <?= json_encode([
            'copied' => t('share.copied'),
            'groupCopied' => t('share.group_copied'),
            'copyFailed' => t('share.copy_failed'),
            'liveUpdated' => t('live.updated'),
            'themeAuto' => t('settings.theme_auto'),
            'themeDark' => t('settings.theme_dark'),
            'themeLight' => t('settings.theme_light'),
            'strength' => [t('user.weak'), t('user.weak'), t('user.fair'), t('user.good'), t('user.strong')],
            'removeFile' => t('upload.remove'),
            'badType' => t('upload.js_bad_type'),
            'tooLarge' => t('upload.js_too_large'),
            'noFiles' => t('upload.no_files'),
            'uploadError' => t('upload.error'),
            'showPassword' => t('auth.show_password'),
            'hidePassword' => t('auth.hide_password'),
            'time' => [
                'left' => t('time.left'),
                'overdue' => t('time.overdue'),
                'and' => t('time.and'),
                'now' => t('time.now'),
                'overdueOnly' => t('time.overdue_only'),
                'suffix' => t('time.suffix'),
                'omitOneTwo' => t('time.omit_one_two'),
                'days' => t('time.days'),
                'hours' => t('time.hours'),
                'minutes' => t('time.minutes'),
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    </script>
