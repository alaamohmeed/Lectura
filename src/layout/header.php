<?php
/**
 * Layout Header
 * Includes DOCTYPE, head, opening body, top bar, and navigation
 */

$pageTitle = $pageTitle ?? t('app.name');
$pageDescription = $pageDescription ?? '';
$extraCss = $extraCss ?? '';
$extraJs = $extraJs ?? '';
$bodyClass = $bodyClass ?? '';
$livePage = $livePage ?? '';

$lang = current_lang();
$dir = current_dir();
$bootstrapCss = bootstrap_css_url();
$favicon = asset_url('img/favicon.svg');

$user = $GLOBALS['current_user'] ?? null;
$isAdmin = $user && $user['role'] === 'admin';
$isUploader = $user && is_staff_uploader($user);
$isAnnouncementManager = $user && is_announcement_manager($user);

// Determine current page for active nav
$currentPage = basename($_SERVER['SCRIPT_NAME'] ?? '', '.php');
$adminPage = isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], '/admin/') ? basename($_SERVER['SCRIPT_NAME'], '.php') : '';
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>" dir="<?= e($dir) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= ea($pageDescription) ?>">
    <meta name="theme-color" content="#12355B" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#071827" media="(prefers-color-scheme: dark)">
    <meta name="csrf-token" content="<?= ea(csrf_token()) ?>">
    <meta name="lc-auth" content="<?= $user ? '1' : '0' ?>">
    <?php if ($livePage !== ''): ?>
        <meta name="lc-live-version" content="<?= (int)live_version() ?>">
        <meta name="lc-live-page" content="<?= ea($livePage) ?>">
    <?php endif; ?>

    <title><?= e($pageTitle) ?></title>

    <!-- Bootstrap CSS (RTL for Arabic) -->
    <link href="<?= e($bootstrapCss) ?>" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Self-hosted fonts -->
    <link rel="preload" href="<?= e(asset_url('fonts/ibm-plex-sans-arabic/arabic-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= e(asset_url('fonts/inter/latin-400.woff2')) ?>" as="font" type="font/woff2" crossorigin>

    <!-- Lectura Design System -->
    <link href="<?= e(asset_url('css/lectura.css')) ?>" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= e($favicon) ?>">
    <link rel="apple-touch-icon" href="<?= e($favicon) ?>">

    <!-- Theme initialization (runs before paint to avoid flash) -->
    <script>
        (function () {
            var dbTheme = <?= json_encode($user['theme'] ?? 'light') ?>;
            var stored = null;
            try { stored = localStorage.getItem('theme'); } catch (e) {}
            var mode = stored || dbTheme || 'light';
            var effective = mode;
            if (mode !== 'light' && mode !== 'dark') {
                effective = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-bs-theme', effective);
            document.documentElement.setAttribute('data-lc-theme', mode);
        })();
    </script>

    <?= $extraCss ?>
</head>
<body class="<?= e($bodyClass) ?>">
    <!-- Top Bar -->
    <header class="topbar" role="banner">
        <div class="container-fluid px-3 px-md-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <!-- Logo -->
                <a href="<?= base_url('index.php') ?>" class="topbar-brand d-flex align-items-center gap-2 text-decoration-none" aria-label="<?= ea(t('app.name')) ?>">
                    <svg class="topbar-logo-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor"/>
                        <path d="M6.5 17H20" stroke="currentColor"/>
                        <path d="M4 12.5A2.5 2.5 0 0 1 6.5 10H20" stroke="currentColor"/>
                        <path d="M6.5 10H20" stroke="currentColor"/>
                        <path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20" stroke="currentColor"/>
                        <path d="M6.5 3H20" stroke="currentColor"/>
                    </svg>
                    <span class="topbar-logo-text fw-bold fs-5">Lectura</span>
                </a>

                <!-- Desktop Navigation -->
                <nav class="topbar-nav d-none d-md-flex align-items-center gap-1" role="navigation" aria-label="<?= ea(t('nav.main')) ?>">
                    <?php if ($user): ?>
                        <a href="<?= base_url('index.php') ?>" class="topbar-nav-link<?= $currentPage === 'index' ? ' active' : '' ?>">
                            <i class="bi bi-house-door"></i> <span><?= e(t('nav.home')) ?></span>
                        </a>
                        <a href="<?= base_url('archive.php') ?>" class="topbar-nav-link<?= $currentPage === 'archive' ? ' active' : '' ?>">
                            <i class="bi bi-archive"></i> <span><?= e(t('nav.archive')) ?></span>
                        </a>
                        <a href="<?= base_url('index.php#updates') ?>" class="topbar-nav-link">
                            <i class="bi bi-bell"></i> <span><?= e(t('nav.updates')) ?></span>
                        </a>

                        <?php if ($isUploader): ?>
                            <a href="<?= base_url('admin/upload.php') ?>" class="topbar-nav-link btn btn-sm btn-primary ms-1">
                                <i class="bi bi-upload"></i> <span><?= e(t('nav.upload')) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($isAnnouncementManager && !$isAdmin): ?>
                            <a href="<?= base_url('admin/announcements.php') ?>" class="topbar-nav-link<?= $adminPage === 'announcements' ? ' active' : '' ?>">
                                <i class="bi bi-megaphone"></i> <span><?= e(t('nav.announcements')) ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($isAdmin): ?>
                            <a href="<?= base_url('admin/index.php') ?>" class="topbar-nav-link<?= $adminPage === 'index' ? ' active' : '' ?>">
                                <i class="bi bi-speedometer2"></i> <span><?= e(t('nav.admin')) ?></span>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </nav>

                <!-- Right side actions -->
                <div class="topbar-actions d-flex align-items-center gap-2">
                    <!-- Language Switcher -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= ea(t('lang.switch')) ?>">
                            <?php $langs = i18n_languages(); $current = $langs[$lang] ?? $langs['ar']; ?>
                            <i class="bi bi-globe2" aria-hidden="true"></i>
                            <span class="d-none d-sm-inline"><?= e($current['native']) ?></span>
                            <span class="d-sm-none"><?= e(strtoupper($lang)) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($langs as $langCode => $langData): ?>
                                <li>
                                    <a class="dropdown-item<?= $langCode === $lang ? ' active' : '' ?>" href="<?= base_url('setlang.php') ?>?lang=<?= e($langCode) ?>&redirect=<?= eu(current_url()) ?>">
                                        <span class="me-2"><?= e($langData['flag']) ?></span> <?= e($langData['native']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Theme Toggle -->
                    <?php if ($user): ?>
                        <button class="btn btn-sm btn-outline-secondary" type="button" id="themeToggle" aria-label="<?= ea(t('theme.toggle')) ?>" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= ea(t('theme.toggle')) ?>">
                            <i class="bi bi-sun" data-theme="light"></i>
                            <i class="bi bi-moon-stars" data-theme="dark"></i>
                            <i class="bi bi-circle-half" data-theme="auto"></i>
                        </button>
                    <?php endif; ?>

                    <!-- User Menu / Login -->
                    <?php if ($user): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= ea($user['name']) ?>">
                                <div class="avatar avatar-sm">
                                    <span><?= e(mb_substr($user['name'], 0, 1)) ?></span>
                                </div>
                                <span class="d-none d-sm-inline"><?= e($user['name']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header"><?= e($user['name']) ?></h6></li>
                                <li><small class="dropdown-item-text text-muted"><?= e($user['email']) ?></small></li>
                                <li><span class="dropdown-item-text profile-role-badge"><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'lecturer' ? 'success' : ($user['role'] === 'assistant' ? 'warning' : 'primary')) ?>"><?= e(t("role.{$user['role']}")) ?></span></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($isAnnouncementManager): ?>
                                    <li><a class="dropdown-item" href="<?= base_url('admin/announcements.php') ?>"><i class="bi bi-megaphone me-2"></i><?= e(t('nav.announcements')) ?></a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= base_url('settings.php') ?>"><i class="bi bi-gear me-2"></i><?= e(t('nav.settings')) ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><form action="<?= base_url('logout.php') ?>" method="post"><button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i><?= e(t('auth.logout')) ?></button><?= csrf_field() ?></form></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= base_url('login.php') ?>" class="btn btn-sm btn-primary"><?= e(t('auth.login')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Bottom Navigation -->
    <?php if ($user): ?>
        <nav class="bottombar d-md-none" role="navigation" aria-label="<?= ea(t('nav.main')) ?>">
            <a href="<?= base_url('index.php') ?>" class="bottombar-item<?= $currentPage === 'index' ? ' active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span><?= e(t('nav.home')) ?></span>
            </a>
            <a href="<?= base_url('archive.php') ?>" class="bottombar-item<?= $currentPage === 'archive' ? ' active' : '' ?>">
                <i class="bi bi-archive"></i>
                <span><?= e(t('nav.archive')) ?></span>
            </a>
            <a href="<?= base_url('index.php#updates') ?>" class="bottombar-item">
                <i class="bi bi-bell"></i>
                <span><?= e(t('nav.updates')) ?></span>
            </a>
            <a href="<?= base_url('settings.php') ?>" class="bottombar-item<?= $currentPage === 'settings' ? ' active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span><?= e(t('nav.settings')) ?></span>
            </a>
            <?php if ($isAdmin): ?>
                <a href="<?= base_url('admin/index.php') ?>" class="bottombar-item bottombar-item-primary">
                    <i class="bi bi-speedometer2"></i>
                    <span><?= e(t('nav.admin')) ?></span>
                </a>
            <?php elseif ($isUploader): ?>
                <a href="<?= base_url('admin/upload.php') ?>" class="bottombar-item bottombar-item-primary">
                    <i class="bi bi-upload"></i>
                    <span><?= e(t('nav.upload')) ?></span>
                </a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="main-content" role="main">
        <div class="container-fluid px-3 px-md-4">
            <!-- Flash Messages -->
            <?php $flash = get_flash(); if ($flash): ?>
                <div class="alert alert-<?= e($flash['type'] === 'error' ? 'danger' : $flash['type']) ?> alert-dismissible fade show animate__animated animate__fadeInDown" role="alert" aria-live="polite">
                    <?= e($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="<?= ea(t('common.dismiss')) ?>"></button>
                </div>
            <?php endif; ?>
