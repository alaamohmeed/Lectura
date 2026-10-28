<?php
/**
 * Reusable UI partials
 * Each function returns/echoes escaped, semantic HTML.
 */

if (!function_exists('lc_empty_state')) {
    function lc_empty_state(string $title, string $message = '', string $icon = 'bi-inbox', ?string $actionUrl = null, ?string $actionLabel = null): void
    {
        ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">
                <rect x="3" y="4" width="18" height="16" rx="3"/>
                <path d="M3 9h18" opacity=".5"/>
                <circle cx="7.5" cy="6.5" r=".6" fill="currentColor"/>
                <circle cx="10" cy="6.5" r=".6" fill="currentColor"/>
                <path d="M7 14h6M7 17h10" stroke-linecap="round"/>
            </svg>
            <h3><?= e($title) ?></h3>
            <?php if ($message !== ''): ?><p><?= e($message) ?></p><?php endif; ?>
            <?php if ($actionUrl && $actionLabel): ?>
                <a href="<?= ea($actionUrl) ?>" class="btn btn-primary"><?= e($actionLabel) ?></a>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('lc_course_card')) {
    /**
     * @param array $course     course row (id, name, code, instructor, color)
     * @param array $counts     ['lecture'=>n,'assignment'=>n,'summary'=>n,'exam'=>n,'other'=>n]
     * @param bool  $compact    compact style for archive
     */
    function lc_course_card(array $course, array $counts = [], bool $compact = false): void
    {
        $color = $course['color'] ?: '#12355B';
        $total = array_sum($counts);
        $url = base_url('course.php?id=' . (int)$course['id']);

        // Build counts line
        $parts = [];
        foreach (['lecture', 'assignment', 'summary', 'exam'] as $type) {
            $n = (int)($counts[$type] ?? 0);
            if ($n > 0) {
                $parts[] = $n . ' ' . t("item.filter.{$type}");
            }
        }
        $countsText = $parts ? implode(' · ', $parts) : t('common.empty_state');
        ?>
        <a href="<?= ea($url) ?>" class="lc-card lc-card-hover course-card text-decoration-none<?= $compact ? ' course-card-compact' : '' ?>"
           style="--course-color: <?= ea($color) ?>"
           aria-label="<?= ea($course['name']) ?>">
            <?php if (!$compact): ?><div class="course-card-accent"></div><?php endif; ?>
            <div class="lc-card-body d-flex flex-column gap-2 h-100">
                <div class="d-flex align-items-start gap-3">
                    <span class="course-card-icon" aria-hidden="true">
                        <i class="bi bi-journal-bookmark-fill"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <h3 class="course-card-title lc-wrap mb-1"><?= e($course['name']) ?></h3>
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <?php if (!empty($course['code'])): ?>
                                <span class="course-card-code"><?= e($course['code']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($course['instructor'])): ?>
                                <span class="course-card-meta"><?= e($course['instructor']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="bi bi-chevron-<?= is_rtl() ? 'left' : 'right' ?> text-muted" aria-hidden="true"></i>
                </div>
                <div class="course-card-counts mt-auto">
                    <?php if ($total > 0): ?>
                        <?= e($countsText) ?>
                    <?php else: ?>
                        <?= e(t('common.empty_state')) ?>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php
    }
}

if (!function_exists('lc_item_card')) {
    function lc_item_card(array $item, array $files = [], bool $showCourse = false): void
    {
        $type = $item['type'];
        $badge = item_type_badge($type);
        $icon = item_type_icon($type);
        $itemUrl = base_url('item.php?id=' . (int)$item['id']);
        $deadline = notify_get_deadline_status($item['due_at'] ?? null);
        ?>
        <article class="lc-card item-card" data-item-card
                 data-type="<?= ea($type) ?>" data-title="<?= ea($item['title']) ?>">
            <div class="lc-card-body">
                <div class="d-flex gap-3">
                    <span class="item-type-icon <?= e($badge) ?>" aria-hidden="true">
                        <i class="bi <?= e($icon) ?>"></i>
                    </span>
                    <div class="min-w-0 flex-grow-1">
                        <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                            <span class="badge-type <?= e($badge) ?>">
                                <i class="bi <?= e($icon) ?>" aria-hidden="true"></i>
                                <?= e(item_type_label($type)) ?>
                            </span>
                            <?php if ($showCourse): ?>
                                <span class="badge-soft-muted badge-type"><?= e($item['course_name'] ?? '') ?></span>
                            <?php endif; ?>
                            <?php if (!empty($item['is_new'])): ?>
                                <span class="badge-new"><?= e(t('nav.updates')) ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 class="item-title lc-wrap mb-1">
                            <a href="<?= ea($itemUrl) ?>"><?= e($item['title']) ?></a>
                        </h3>

                        <?php if (!empty($item['description'])): ?>
                            <p class="item-meta lc-clamp-2 mb-2"><?= e($item['description']) ?></p>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap align-items-center gap-2 item-meta">
                            <span><i class="bi bi-calendar3" aria-hidden="true"></i> <?= e(format_date(utc_datetime($item['created_at']), 'medium')) ?></span>
                            <?php if (!empty($item['uploader_name'])): ?>
                                <span aria-hidden="true">·</span>
                                <span><i class="bi bi-person" aria-hidden="true"></i> <?= e($item['uploader_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($files)): ?>
                                <span aria-hidden="true">·</span>
                                <span><i class="bi bi-paperclip" aria-hidden="true"></i> <?= count($files) ?></span>
                            <?php endif; ?>
                        </div>

                        <?php if ($item['due_at']): ?>
                            <div class="mt-2">
                                <span class="due-chip <?= e($deadline === 'overdue' ? 'overdue' : ($deadline === 'due_soon' ? 'due-soon' : '')) ?>">
                                    <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                                    <span data-countdown="<?= ea($item['due_at']) ?>"><?= e(notify_format_countdown($item['due_at'])) ?></span>
                                </span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <a href="<?= ea($itemUrl) ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye" aria-hidden="true"></i> <?= e(t('item.preview')) ?>
                            </a>
                            <?php if (!empty($files)): ?>
                                <a href="<?= ea(base_url('download.php?id=' . (int)$files[0]['id'])) ?>" class="btn btn-sm btn-ghost">
                                    <i class="bi bi-download" aria-hidden="true"></i> <?= e(t('item.download')) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </article>
        <?php
    }
}

if (!function_exists('lc_breadcrumb')) {
    /**
     * @param array<int,array{label:string,url?:string}> $crumbs
     */
    function lc_breadcrumb(array $crumbs): void
    {
        ?>
        <nav class="breadcrumb-lc" aria-label="Breadcrumb">
            <?php foreach ($crumbs as $i => $c): ?>
                <?php if ($i > 0): ?><span class="sep" aria-hidden="true">›</span><?php endif; ?>
                <?php if (!empty($c['url'])): ?>
                    <a href="<?= ea($c['url']) ?>"><?= e($c['label']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= e($c['label']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php
    }
}

if (!function_exists('lc_storage_meter')) {
    function lc_storage_meter(): void
    {
        $used = get_storage_usage();
        $total = (int)($GLOBALS['config']['storage']['quota'] ?? 100 * 1024 * 1024);
        $warningPercent = (int)($GLOBALS['config']['storage']['warning_percent'] ?? 80);
        if ($total <= 0) $total = 100 * 1024 * 1024;
        $percent = min(100, ($used / $total) * 100);
        $cls = $percent >= min(100, $warningPercent + 10) ? 'danger' : ($percent >= $warningPercent ? 'warning' : '');
        ?>
        <div class="storage-meter <?= e($cls) ?>">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small"><?= e(t('admin.stats.storage')) ?></span>
                <span class="storage-meter-label"><?= e(format_file_size($used)) ?> / <?= e(format_file_size($total)) ?></span>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="<?= (int)$percent ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= ea(t('admin.stats.storage')) ?>">
                <div class="progress-bar" style="width: <?= (int)$percent ?>%"></div>
            </div>
            <?php if ($percent >= $warningPercent): ?>
                <div class="alert alert-warning mt-2 mb-0 py-2 px-3 small">
                    <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                    <?= e(t('admin.stats.storage_warning')) ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

if (!function_exists('lc_pagination')) {
    function lc_pagination(int $current, int $total, string $baseUrl): void
    {
        if ($total <= 1) return;
        ?>
        <nav aria-label="pagination">
            <ul class="pagination justify-content-center mt-4">
                <?php for ($i = 1; $i <= $total; $i++): ?>
                    <li class="page-item<?= $i === $current ? ' active' : '' ?>">
                        <a class="page-link" href="<?= ea($baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $i) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php
    }
}

if (!function_exists('lc_admin_sidebar')) {
    function lc_admin_sidebar(string $active): void
    {
        $role = $GLOBALS['current_user']['role'] ?? '';
        $allLinks = [
            ['key' => 'dashboard', 'url' => 'admin/index.php', 'icon' => 'bi-speedometer2', 'label' => t('nav.dashboard')],
            ['key' => 'upload', 'url' => 'admin/upload.php', 'icon' => 'bi-cloud-arrow-up', 'label' => t('nav.upload')],
            ['key' => 'terms', 'url' => 'admin/terms.php', 'icon' => 'bi-calendar-range', 'label' => t('nav.terms')],
            ['key' => 'courses', 'url' => 'admin/courses.php', 'icon' => 'bi-journals', 'label' => t('nav.courses')],
            ['key' => 'users', 'url' => 'admin/users.php', 'icon' => 'bi-people', 'label' => t('nav.users')],
            ['key' => 'invites', 'url' => 'admin/invites.php', 'icon' => 'bi-envelope-plus', 'label' => t('nav.invites')],
            ['key' => 'announcements', 'url' => 'admin/announcements.php', 'icon' => 'bi-megaphone', 'label' => t('nav.announcements')],
            ['key' => 'sharing', 'url' => 'admin/sharing.php', 'icon' => 'bi-whatsapp', 'label' => t('nav.sharing')],
        ];

        $allowed = match ($role) {
            'admin' => array_column($allLinks, 'key'),
            'lecturer' => ['upload', 'announcements'],
            'assistant' => ['upload'],
            default => [],
        };

        $links = array_values(array_filter($allLinks, fn(array $link) => in_array($link['key'], $allowed, true)));
        if (!$links) return;
        ?>
        <aside class="admin-sidebar" aria-label="<?= ea(t('nav.admin')) ?>">
            <nav class="d-flex flex-row flex-lg-column flex-wrap flex-lg-nowrap gap-1">
                <?php foreach ($links as $l): ?>
                    <a href="<?= base_url($l['url']) ?>"
                       class="admin-sidebar-link flex-shrink-0<?= $active === $l['key'] ? ' active' : '' ?>"
                       <?= $active === $l['key'] ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= e($l['icon']) ?>" aria-hidden="true"></i>
                        <span><?= e($l['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php
    }
}
