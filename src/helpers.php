<?php
/**
 * Core Helpers
 * Common utility functions used throughout the application
 */

// Output escaping for HTML context
if (!function_exists('e')) {
    function e(mixed $value, int $flags = ENT_QUOTES | ENT_SUBSTITUTE, string $encoding = 'UTF-8'): string
    {
        if (is_null($value)) {
            return '';
        }
        return htmlspecialchars((string)$value, $flags, $encoding);
    }
}

// Escape for HTML attribute context (double quotes)
if (!function_exists('ea')) {
    function ea(mixed $value): string
    {
        return e($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// Escape for URL context
if (!function_exists('eu')) {
    function eu(mixed $value): string
    {
        return rawurlencode((string)$value);
    }
}

// Escape for JavaScript context (inside single quotes)
if (!function_exists('ej')) {
    function ej(mixed $value): string
    {
        $json = json_encode((string)$value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE);
        // Remove surrounding quotes from json_encode
        return substr($json, 1, -1);
    }
}

// Current UTC timestamp for database writes.
// All DATETIME columns store UTC; the app timezone is used for display only.
if (!function_exists('utc_now')) {
    function utc_now(): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}

// Parse a database datetime string as UTC.
if (!function_exists('utc_datetime')) {
    function utc_datetime(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (Exception $e) {
            return null;
        }
    }
}

// Get translation string
if (!function_exists('t')) {
    function t(string $key, array $params = []): string
    {
        $lang = $GLOBALS['i18n']['current'] ?? 'ar';
        $translations = $GLOBALS['i18n']['translations'][$lang] ?? [];
        $text = $translations[$key] ?? $key;

        // Simple parameter replacement: {param} -> value
        foreach ($params as $param => $value) {
            $text = str_replace('{' . $param . '}', e($value), $text);
        }
        return $text;
    }
}

// Get translation for HTML attribute
if (!function_exists('ta')) {
    function ta(string $key, array $params = []): string
    {
        return ea(t($key, $params));
    }
}

// Get translation for a specific language (used by emails)
if (!function_exists('t_lang')) {
    function t_lang(string $key, array $params = [], ?string $lang = null): string
    {
        if ($lang === null || $lang === ($GLOBALS['i18n']['current'] ?? 'ar')) {
            return t($key, $params);
        }
        $translations = $GLOBALS['i18n']['translations'][$lang] ?? [];
        $text = $translations[$key] ?? $key;
        foreach ($params as $param => $value) {
            $text = str_replace('{' . $param . '}', e($value), $text);
        }
        return $text;
    }
}

// Get current language
if (!function_exists('current_lang')) {
    function current_lang(): string
    {
        return $GLOBALS['i18n']['current'] ?? 'ar';
    }
}

// Get current direction
if (!function_exists('current_dir')) {
    function current_dir(): string
    {
        return current_lang() === 'ar' ? 'rtl' : 'ltr';
    }
}

// Check if RTL
if (!function_exists('is_rtl')) {
    function is_rtl(): bool
    {
        return current_dir() === 'rtl';
    }
}

// Format date for current locale
if (!function_exists('format_date')) {
    function format_date(?DateTimeInterface $date, string $format = 'medium', ?string $lang = null): string
    {
        if (!$date) {
            return '';
        }

        $lang = $lang ?? current_lang();
        $locale = $lang === 'ar' ? 'ar_SA' : 'en_US';
        $timezone = new DateTimeZone($GLOBALS['config']['app']['timezone'] ?? 'UTC');
        $date = DateTimeImmutable::createFromInterface($date)->setTimezone($timezone);

        // Use IntlDateFormatter for proper localization when available
        if (class_exists('IntlDateFormatter')) {
            $fmt = match ($format) {
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
                default => IntlDateFormatter::MEDIUM,
            };

            $formatter = new IntlDateFormatter(
                $locale,
                $fmt,
                IntlDateFormatter::NONE,
                $timezone->getName(),
                IntlDateFormatter::GREGORIAN
            );

            $result = $formatter->format($date);
            if ($result !== false) {
                return $result;
            }
        }

        // Localized fallback (no ext-intl)
        $months = ['january', 'february', 'march', 'april', 'may', 'june',
                   'july', 'august', 'september', 'october', 'november', 'december'];
        $monthName = t_lang('date.' . $months[(int)$date->format('n') - 1], [], $lang);

        if ($lang === 'ar') {
            return $date->format('j') . ' ' . $monthName . ' ' . $date->format('Y');
        }
        if ($format === 'short') {
            return $date->format('M j, Y');
        }
        return $date->format('M j, Y');
    }
}

// Format datetime for current locale
if (!function_exists('format_datetime')) {
    function format_datetime(?DateTimeInterface $date, string $format = 'medium', ?string $lang = null): string
    {
        if (!$date) {
            return '';
        }

        $lang = $lang ?? current_lang();
        $locale = $lang === 'ar' ? 'ar_SA' : 'en_US';
        $timezone = new DateTimeZone($GLOBALS['config']['app']['timezone'] ?? 'UTC');
        $date = DateTimeImmutable::createFromInterface($date)->setTimezone($timezone);

        if (class_exists('IntlDateFormatter')) {
            $fmt = match ($format) {
                'short' => IntlDateFormatter::SHORT,
                'medium' => IntlDateFormatter::MEDIUM,
                'long' => IntlDateFormatter::LONG,
                'full' => IntlDateFormatter::FULL,
                default => IntlDateFormatter::MEDIUM,
            };

            $formatter = new IntlDateFormatter(
                $locale,
                $fmt,
                $fmt,
                $timezone->getName(),
                IntlDateFormatter::GREGORIAN
            );

            $result = $formatter->format($date);
            if ($result !== false) {
                return $result;
            }
        }

        return format_date($date, $format, $lang) . ' ' . $date->format('H:i');
    }
}

// Arabic counted-noun form (1 / 2 / 3–10 / 11+)
if (!function_exists('ar_counted_unit')) {
    function ar_counted_unit(int $n, string $unit): string
    {
        $forms = [
            'year' => ['سنة', 'سنتين', 'سنوات', 'سنة'],
            'month' => ['شهر', 'شهرين', 'أشهر', 'شهرًا'],
            'day' => ['يوم', 'يومين', 'أيام', 'يومًا'],
            'hour' => ['ساعة', 'ساعتين', 'ساعات', 'ساعة'],
            'minute' => ['دقيقة', 'دقيقتين', 'دقائق', 'دقيقة'],
        ];
        $f = $forms[$unit] ?? [$unit, $unit, $unit, $unit];
        if ($n === 1) return $f[0];
        if ($n === 2) return $f[1];
        if ($n >= 3 && $n <= 10) return $f[2];
        return $f[3];
    }
}

// Arabic counted-noun phrase (omits the numeral for 1 and 2)
if (!function_exists('ar_counted_phrase')) {
    function ar_counted_phrase(int $n, string $unit): string
    {
        $word = ar_counted_unit($n, $unit);
        return ($n === 1 || $n === 2) ? $word : "{$n} {$word}";
    }
}

// Format relative time (e.g., "2 hours ago")
if (!function_exists('format_relative_time')) {
    function format_relative_time(DateTimeInterface $date): string
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $diff = $now->diff($date);

        $lang = current_lang();

        if ($diff->y > 0) {
            return $lang === 'ar'
                ? "منذ " . ar_counted_phrase($diff->y, 'year')
                : "{$diff->y} year" . ($diff->y === 1 ? '' : 's') . " ago";
        }
        if ($diff->m > 0) {
            return $lang === 'ar'
                ? "منذ " . ar_counted_phrase($diff->m, 'month')
                : "{$diff->m} month" . ($diff->m === 1 ? '' : 's') . " ago";
        }
        if ($diff->d > 0) {
            return $lang === 'ar'
                ? "منذ " . ar_counted_phrase($diff->d, 'day')
                : "{$diff->d} day" . ($diff->d === 1 ? '' : 's') . " ago";
        }
        if ($diff->h > 0) {
            return $lang === 'ar'
                ? "منذ " . ar_counted_phrase($diff->h, 'hour')
                : "{$diff->h} hour" . ($diff->h === 1 ? '' : 's') . " ago";
        }
        if ($diff->i > 0) {
            return $lang === 'ar'
                ? "منذ " . ar_counted_phrase($diff->i, 'minute')
                : "{$diff->i} minute" . ($diff->i === 1 ? '' : 's') . " ago";
        }

        return $lang === 'ar' ? 'الآن' : 'just now';
    }
}

// Format file size
if (!function_exists('format_file_size')) {
    function format_file_size(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, $i > 0 ? 1 : 0) . ' ' . $units[$i];
    }
}

// Get file icon class based on MIME type
if (!function_exists('file_icon')) {
    function file_icon(string $mime): string
    {
        return match (true) {
            str_starts_with($mime, 'application/pdf') => 'bi-file-earmark-pdf-fill text-danger',
            str_starts_with($mime, 'application/msword'),
            str_starts_with($mime, 'application/vnd.openxmlformats-officedocument.wordprocessingml') => 'bi-file-earmark-word-fill text-primary',
            str_starts_with($mime, 'application/vnd.ms-powerpoint'),
            str_starts_with($mime, 'application/vnd.openxmlformats-officedocument.presentationml') => 'bi-file-earmark-ppt-fill text-warning',
            str_starts_with($mime, 'application/vnd.ms-excel'),
            str_starts_with($mime, 'application/vnd.openxmlformats-officedocument.spreadsheetml') => 'bi-file-earmark-excel-fill text-success',
            str_starts_with($mime, 'image/') => 'bi-file-earmark-image-fill text-info',
            str_starts_with($mime, 'application/zip'),
            str_starts_with($mime, 'application/x-zip') => 'bi-file-earmark-zip-fill text-secondary',
            default => 'bi-file-earmark-fill text-muted',
        };
    }
}

// Get item type badge class
if (!function_exists('item_type_badge')) {
    function item_type_badge(string $type): string
    {
        return match ($type) {
            'lecture' => 'badge-lecture',
            'assignment' => 'badge-assignment',
            'summary' => 'badge-summary',
            'exam' => 'badge-exam',
            'other' => 'badge-other',
            default => 'badge-other',
        };
    }
}

// Get item type label
if (!function_exists('item_type_label')) {
    function item_type_label(string $type, ?string $lang = null): string
    {
        return t_lang("item.type.{$type}", [], $lang);
    }
}

// Get item type icon
if (!function_exists('item_type_icon')) {
    function item_type_icon(string $type): string
    {
        return match ($type) {
            'lecture' => 'bi-book',
            'assignment' => 'bi-journal-text',
            'summary' => 'bi-file-text',
            'exam' => 'bi-file-earmark-check',
            'other' => 'bi-file-earmark',
            default => 'bi-file-earmark',
        };
    }
}

// Generate a secure random token
if (!function_exists('generate_token')) {
    function generate_token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }
}

// Check if request is POST
if (!function_exists('is_post')) {
    function is_post(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}

// Check if request is AJAX
if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// Get client IP address
if (!function_exists('get_client_ip')) {
    function get_client_ip(): string
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                return trim($ips[0]);
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}

// Redirect with flash message
if (!function_exists('redirect_with')) {
    function redirect_with(string $url, string $type, string $message): never
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        header("Location: {$url}");
        exit;
    }
}

// Get and clear flash message
if (!function_exists('get_flash')) {
    function get_flash(): ?array
    {
        if (!empty($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}

// Check if a user is a staff uploader (admin, lecturer, or assistant)
if (!function_exists('is_staff_uploader')) {
    function is_staff_uploader(array $user): bool
    {
        return in_array($user['role'] ?? '', ['admin', 'lecturer', 'assistant'], true);
    }
}

// Check if a user can manage announcements
if (!function_exists('is_announcement_manager')) {
    function is_announcement_manager(array $user): bool
    {
        return in_array($user['role'] ?? '', ['admin', 'lecturer'], true);
    }
}

// Map a role to its display badge
if (!function_exists('role_badge_class')) {
    function role_badge_class(string $role): string
    {
        return match ($role) {
            'admin' => 'badge-exam',
            'lecturer' => 'badge-summary',
            'assistant' => 'badge-assignment',
            default => 'badge-lecture',
        };
    }
}

// Check if user has permission
if (!function_exists('can')) {
    function can(string $permission, ?int $courseId = null): bool
    {
        $user = $GLOBALS['current_user'] ?? null;
        if (!$user || !$user['is_active']) {
            return false;
        }

        return match ($permission) {
            'admin.access' => $user['role'] === 'admin',
            'upload' => is_staff_uploader($user) &&
                        ($user['role'] !== 'lecturer' || $courseId === null ||
                         is_lecturer_for_course((int)$user['id'], $courseId)),
            'manage_terms' => $user['role'] === 'admin',
            'manage_courses' => $user['role'] === 'admin',
            'manage_users' => $user['role'] === 'admin',
            'manage_invites' => $user['role'] === 'admin',
            'manage_announcements' => is_announcement_manager($user),
            'manage_all_announcements' => $user['role'] === 'admin',
            'view_admin_dashboard' => $user['role'] === 'admin',
            default => false,
        };
    }
}

// Check if a lecturer is assigned to a course
if (!function_exists('is_lecturer_for_course')) {
    function is_lecturer_for_course(int $userId, int $courseId): bool
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return false;

        $stmt = $pdo->prepare('SELECT 1 FROM course_lecturers WHERE user_id = ? AND course_id = ?');
        $stmt->execute([$userId, $courseId]);
        return (bool)$stmt->fetchColumn();
    }
}

// Check if user is assistant for a course (informational assignment; assistants are global uploaders)
if (!function_exists('is_assistant_for_course')) {
    function is_assistant_for_course(int $userId, int $courseId): bool
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return false;

        $stmt = $pdo->prepare('SELECT 1 FROM course_assistants WHERE user_id = ? AND course_id = ?');
        $stmt->execute([$userId, $courseId]);
        return (bool)$stmt->fetchColumn();
    }
}

// Get courses a staff user may manage (lecturers are course-scoped; assistants are global)
if (!function_exists('get_manageable_courses')) {
    function get_manageable_courses(array $user): array
    {
        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo || !is_staff_uploader($user)) return [];

        if (($user['role'] ?? '') === 'lecturer') {
            $stmt = $pdo->prepare('
                SELECT c.*, t.name AS term_name, t.is_current
                FROM courses c
                JOIN terms t ON t.id = c.term_id
                JOIN course_lecturers cl ON cl.course_id = c.id
                WHERE cl.user_id = ?
                ORDER BY t.is_current DESC, t.created_at DESC, c.name
            ');
            $stmt->execute([(int)$user['id']]);
            return $stmt->fetchAll();
        }

        return db_select('
            SELECT c.*, t.name AS term_name, t.is_current
            FROM courses c
            JOIN terms t ON t.id = c.term_id
            ORDER BY t.is_current DESC, t.created_at DESC, c.name
        ');
    }
}

// Get current user's courses (for compatibility)
if (!function_exists('get_user_courses')) {
    function get_user_courses(int $userId): array
    {
        $user = $GLOBALS['current_user'] ?? null;
        if ($user && (int)$user['id'] === $userId) {
            return get_manageable_courses($user);
        }

        $pdo = $GLOBALS['pdo'] ?? null;
        if (!$pdo) return [];

        $stmt = $pdo->prepare('
            SELECT c.* FROM courses c
            JOIN course_lecturers cl ON cl.course_id = c.id
            WHERE cl.user_id = ?
            ORDER BY c.term_id DESC, c.name
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}

// Build WhatsApp share URL
if (!function_exists('build_whatsapp_url')) {
    function build_whatsapp_url(string $message): string
    {
        return 'https://wa.me/?text=' . rawurlencode($message);
    }
}

// Generate WhatsApp share message for an item (no links)
if (!function_exists('generate_item_share_message')) {
    function generate_item_share_message(array $item, array $course): string
    {
        $lang = current_lang();
        $typeLabel = item_type_label($item['type']);
        $dueText = format_date(utc_datetime($item['due_at'] ?? null));

        if ($lang === 'ar') {
            $emoji = match ($item['type']) {
                'lecture' => '📚',
                'assignment' => '📝',
                'summary' => '📋',
                'exam' => '📝',
                default => '📄',
            };
            // Arabic adjective follows the noun's gender
            $noun = match ($item['type']) {
                'lecture' => 'محاضرة',
                'assignment' => 'واجب',
                'summary' => 'ملخص',
                'exam' => 'امتحان',
                default => 'مادة',
            };
            $adjective = $item['type'] === 'lecture' ? 'جديدة' : 'جديد';
            $message = "{$emoji} تم رفع {$noun} {$adjective} على Lectura\n";
            $message .= "المادة: {$course['name']}\n";
            $message .= "العنوان: {$item['title']}\n";
            if ($dueText) {
                $message .= "موعد التسليم: {$dueText}\n";
            }
        } else {
            $emoji = match ($item['type']) {
                'lecture' => '📚',
                'assignment' => '📝',
                'summary' => '📋',
                'exam' => '📝',
                default => '📄',
            };
            $message = "{$emoji} New {$typeLabel} uploaded on Lectura\n";
            $message .= "Course: {$course['name']}\n";
            $message .= "Title: {$item['title']}\n";
            if ($dueText) {
                $message .= "Due: {$dueText}\n";
            }
        }

        return rtrim($message);
    }
}

// Generate WhatsApp share message for an announcement
if (!function_exists('generate_announcement_share_message')) {
    function generate_announcement_share_message(array $announcement, ?string $baseUrl = null): string
    {
        $baseUrl = $baseUrl ?? base_url();
        $url = "{$baseUrl}/index.php";

        $lang = current_lang();

        if ($lang === 'ar') {
            $message = "📢 إعلان جديد على Lectura\n";
            $message .= "العنوان: {$announcement['title']}\n";
            $message .= "{$announcement['body']}\n";
            $message .= "🔗 {$url}";
        } else {
            $message = "📢 New announcement on Lectura\n";
            $message .= "Title: {$announcement['title']}\n";
            $message .= "{$announcement['body']}\n";
            $message .= "🔗 {$url}";
        }

        return $message;
    }
}

// Validate CSRF token
if (!function_exists('validate_csrf')) {
    function validate_csrf(string $token): bool
    {
        return csrf_validate($token);
    }
}

// Get CSRF token field HTML
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . ea(csrf_token()) . '">';
    }
}

// Check if string starts with another string (PHP 8+ has str_starts_with)
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

// Check if string ends with another string (PHP 8+ has str_ends_with)
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        $len = strlen($needle);
        return $len === 0 || substr($haystack, -$len) === $needle;
    }
}

// Get storage usage in bytes
if (!function_exists('get_storage_usage')) {
    function get_storage_usage(): int
    {
        $path = $GLOBALS['config']['upload']['path'] ?? __DIR__ . '/../storage/uploads';
        if (!is_dir($path)) return 0;

        $size = 0;
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}

// Get storage usage percentage
if (!function_exists('get_storage_usage_percent')) {
    function get_storage_usage_percent(): float
    {
        $used = get_storage_usage();
        // Estimate total from config; real quota would need hosting quota tools
        $total = (int)($GLOBALS['config']['storage']['quota'] ?? 100 * 1024 * 1024);
        if ($total <= 0) return 0;
        return min(100, ($used / $total) * 100);
    }
}

// Check if storage is near limit
if (!function_exists('is_storage_warning')) {
    function is_storage_warning(): bool
    {
        $threshold = $GLOBALS['config']['storage']['warning_percent'] ?? 80;
        return get_storage_usage_percent() >= $threshold;
    }
}

// Flush the response to the client and keep running in the background
if (!function_exists('lc_finish_request')) {
    function lc_finish_request(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }
        // Best-effort for mod_php / other SAPIs
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        @flush();
        if (function_exists('session_write_close')) {
            @session_write_close();
        }
        @ignore_user_abort(true);
    }
}