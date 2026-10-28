<?php
/**
 * Home page
 * Greeting, announcements, deadlines, current-term courses, latest updates
 */
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout/partials.php';

$user = auth_require();
$isUploader = $user && is_staff_uploader($user);

// Current term
$term = db_select_one('SELECT * FROM terms WHERE is_current = 1 LIMIT 1');

// Announcements: pinned first, then newest
$announcements = db_select(
    'SELECT a.*, u.name AS author_name
     FROM announcements a
     JOIN users u ON u.id = a.created_by
     ORDER BY a.is_pinned DESC, a.created_at DESC
     LIMIT 6'
);

// Courses of the current term with item counts
$courses = [];
if ($term) {
    $courses = db_select(
        'SELECT c.*,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = "lecture") AS lecture_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = "assignment") AS assignment_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = "summary") AS summary_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = "exam") AS exam_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = "other") AS other_count
         FROM courses c
         WHERE c.term_id = ?
         ORDER BY c.name',
        [$term['id']]
    );
}

// Upcoming deadlines
$deadlines = notify_get_upcoming_deadlines(3);

// Latest updates feed
$updates = notify_get_latest_updates(20);

$pageTitle = t('nav.home') . ' — ' . t('app.name');
$livePage = 'home';

// ---- Render ----
require __DIR__ . '/../src/layout/header.php';
?>

<div class="d-flex flex-column gap-4">

    <!-- 1. Greeting -->
    <section class="hero-greeting d-flex flex-wrap align-items-end justify-content-between gap-2" aria-labelledby="greeting">
        <div>
            <h1 id="greeting"><?= e(t('home.greeting', ['name' => $user['name']])) ?></h1>
            <p>
                <?php if ($term): ?>
                    <i class="bi bi-calendar-range" aria-hidden="true"></i> <?= e($term['name']) ?>
                <?php else: ?>
                    <?= e(t('term.no_terms')) ?>
                <?php endif; ?>
            </p>
        </div>
    </section>

    <!-- 2. Announcements -->
    <?php if ($announcements): ?>
        <section aria-labelledby="announcements-heading">
            <div class="section-heading">
                <h2 id="announcements-heading" class="h5">
                    <i class="bi bi-megaphone text-warning" aria-hidden="true"></i>
                    <?= e(t('nav.announcements')) ?>
                </h2>
            </div>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($announcements as $a): ?>
                    <?php
                    $shareMsg = $isUploader ? generate_announcement_share_message($a) : '';
                    ?>
                    <article class="lc-card announcement-card<?= $a['is_pinned'] ? ' announcement-pinned' : '' ?>">
                        <div class="lc-card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="announcement-title lc-wrap">
                                        <?php if ($a['is_pinned']): ?>
                                            <i class="bi bi-pin-angle-fill text-warning" aria-label="<?= ea(t('announcement.pinned')) ?>"></i>
                                        <?php endif; ?>
                                        <?= e($a['title']) ?>
                                    </h3>
                                    <div class="item-meta mb-2">
                                        <i class="bi bi-person" aria-hidden="true"></i> <?= e($a['author_name']) ?>
                                        · <?= e(format_relative_time(utc_datetime($a['created_at']))) ?>
                                    </div>
                                    <p class="announcement-body mb-0 lc-wrap"><?= e($a['body']) ?></p>
                                </div>
                                <button type="button" class="btn-close flex-shrink-0" data-dismiss-announcement="<?= (int)$a['id'] ?>" aria-label="<?= ea(t('announcement.dismiss')) ?>"></button>
                            </div>
                            <?php if ($isUploader): ?>
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <button type="button" class="btn btn-sm btn-whatsapp" data-whatsapp="#ann-share-<?= (int)$a['id'] ?>">
                                    <i class="bi bi-whatsapp" aria-hidden="true"></i> <?= e(t('share.whatsapp')) ?>
                                </button>
                                <button type="button" class="btn btn-sm btn-ghost" data-copy="#ann-share-<?= (int)$a['id'] ?>">
                                    <i class="bi bi-clipboard" aria-hidden="true"></i> <?= e(t('share.copy')) ?>
                                </button>
                                <textarea id="ann-share-<?= (int)$a['id'] ?>" class="d-none" aria-hidden="true"><?= e($shareMsg) ?></textarea>
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- 3. Upcoming deadlines -->
    <?php if ($deadlines): ?>
        <section aria-labelledby="deadlines-heading">
            <div class="section-heading">
                <h2 id="deadlines-heading" class="h5">
                    <i class="bi bi-hourglass-split text-danger" aria-hidden="true"></i>
                    <?= e(t('home.deadlines')) ?>
                </h2>
            </div>
            <div class="deadline-strip">
                <?php foreach ($deadlines as $d): ?>
                    <?php
                    $status = notify_get_deadline_status($d['due_at']);
                    $chipClass = $status === 'overdue' ? 'overdue' : ($status === 'due_soon' ? 'due-soon' : '');
                    ?>
                    <a href="<?= ea(base_url('item.php?id=' . (int)$d['id'])) ?>" class="deadline-card text-decoration-none">
                        <span class="badge-type badge-assignment align-self-start">
                            <i class="bi bi-journal-text" aria-hidden="true"></i> <?= e(t('item.type.assignment')) ?>
                        </span>
                        <div class="fw-semibold lc-wrap" style="color: var(--lc-text)"><?= e($d['title']) ?></div>
                        <div class="item-meta"><?= e($d['course_name']) ?></div>
                        <span class="due-chip <?= e($chipClass) ?> mt-auto">
                            <i class="bi bi-clock" aria-hidden="true"></i>
                            <span data-countdown="<?= ea($d['due_at']) ?>"><?= e(notify_format_countdown($d['due_at'])) ?></span>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- 4. Courses grid -->
    <section aria-labelledby="courses-heading">
        <div class="section-heading">
            <h2 id="courses-heading" class="h5">
                <i class="bi bi-journals text-primary" aria-hidden="true"></i>
                <?= e(t('nav.courses')) ?>
            </h2>
            <a href="<?= base_url('archive.php') ?>" class="btn btn-sm btn-ghost">
                <?= e(t('nav.archive')) ?> <i class="bi bi-chevron-<?= is_rtl() ? 'left' : 'right' ?>" aria-hidden="true"></i>
            </a>
        </div>

        <?php if ($courses): ?>
            <div class="row g-3">
                <?php foreach ($courses as $c): ?>
                    <div class="col-12 col-sm-6 col-lg-4">
                        <?php lc_course_card($c, [
                            'lecture' => $c['lecture_count'],
                            'assignment' => $c['assignment_count'],
                            'summary' => $c['summary_count'],
                            'exam' => $c['exam_count'],
                            'other' => $c['other_count'],
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="lc-card">
                <?php lc_empty_state(
                    t('course.no_courses'),
                    $isUploader ? t('upload.title') : '',
                    'bi-journals',
                    $isUploader ? base_url('admin/upload.php') : null,
                    $isUploader ? t('nav.upload') : null
                ); ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- 5. Latest updates -->
    <section id="updates" aria-labelledby="updates-heading">
        <div class="section-heading">
            <h2 id="updates-heading" class="h5">
                <i class="bi bi-bell text-primary" aria-hidden="true"></i>
                <?= e(t('home.latest_updates')) ?>
            </h2>
        </div>

        <?php if ($updates): ?>
            <div class="lc-card">
                <div class="lc-card-body py-2">
                    <?php foreach ($updates as $u): ?>
                        <?php if ($u['source_type'] === 'item'): ?>
                            <?php
                            $type = $u['type'];
                            $icon = item_type_icon($type);
                            $badge = item_type_badge($type);
                            ?>
                            <div class="feed-item">
                                <span class="feed-icon <?= e($badge) ?>" aria-hidden="true"><i class="bi <?= e($icon) ?>"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <a class="feed-title lc-wrap" href="<?= ea(base_url('item.php?id=' . (int)$u['id'])) ?>"><?= e($u['title']) ?></a>
                                        <?php if ($u['is_new']): ?><span class="badge-new"><?= e(t('common.new')) ?></span><?php endif; ?>
                                    </div>
                                    <div class="feed-meta">
                                        <?= e(item_type_label($type)) ?> · <?= e($u['course_name']) ?>
                                        · <?= e(format_relative_time(utc_datetime($u['created_at']))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="feed-item">
                                <span class="feed-icon badge-assignment" aria-hidden="true"><i class="bi bi-megaphone"></i></span>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="feed-title lc-wrap"><?= e($u['title']) ?></span>
                                        <?php if ($u['is_new']): ?><span class="badge-new"><?= e(t('common.new')) ?></span><?php endif; ?>
                                    </div>
                                    <div class="feed-meta">
                                        <?= e(t('nav.announcements')) ?> · <?= e(format_relative_time(utc_datetime($u['created_at']))) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="lc-card"><?php lc_empty_state(t('common.empty_state'), '', 'bi-bell'); ?></div>
        <?php endif; ?>
    </section>
</div>

<?php
// Update last_seen_at AFTER rendering so badges reflect this visit
db_update('users', ['last_seen_at' => utc_now()], 'id = ?', [$user['id']]);
$_SESSION['user_last_seen'] = utc_now();

$extraJs = '<script src="' . e(asset_url('js/countdown.js')) . '"></script>'
         . '<script src="' . e(asset_url('js/share.js')) . '"></script>';
require __DIR__ . '/../src/layout/footer.php';