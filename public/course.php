<?php
/**
 * Course page
 * Header, search, filter chips, item list
 */
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout/partials.php';

$user = auth_require();
$courseId = (int)($_GET['id'] ?? 0);

$course = db_select_one(
    'SELECT c.*, t.name AS term_name, t.is_current
     FROM courses c
     JOIN terms t ON t.id = c.term_id
     WHERE c.id = ?',
    [$courseId]
);

if (!$course) {
    http_response_code(404);
    require __DIR__ . '/../src/layout/header.php';
    echo '<div class="lc-card my-4">';
    lc_empty_state(t('errors.404.title'), t('errors.404.message'), 'bi-compass', base_url('index.php'), t('common.back_home'));
    echo '</div>';
    require __DIR__ . '/../src/layout/footer.php';
    exit;
}

$isUploader = $user && can('upload', $courseId);

$items = db_select(
    'SELECT i.*, u.name AS uploader_name
     FROM items i
     JOIN users u ON u.id = i.uploaded_by
     WHERE i.course_id = ?
     ORDER BY i.created_at DESC',
    [$courseId]
);

// Attach files
$filesByItem = [];
if ($items) {
    $ids = array_column($items, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $allFiles = db_select(
        "SELECT * FROM files WHERE item_id IN ($placeholders) ORDER BY created_at",
        $ids
    );
    foreach ($allFiles as $f) {
        $filesByItem[(int)$f['item_id']][] = $f;
    }
}

// Counts by type
$counts = ['lecture' => 0, 'assignment' => 0, 'summary' => 0, 'exam' => 0, 'other' => 0];
foreach ($items as $it) {
    $counts[$it['type']] = ($counts[$it['type']] ?? 0) + 1;
}
$totalItems = count($items);

$color = $course['color'] ?: '#12355B';
$pageTitle = $course['name'] . ' — ' . t('app.name');
$livePage = 'course';

require __DIR__ . '/../src/layout/header.php';
?>

<div style="--course-color: <?= ea($color) ?>">
    <?php lc_breadcrumb([
        ['label' => t('nav.home'), 'url' => base_url('index.php')],
        ['label' => $course['term_name'], 'url' => $course['is_current'] ? base_url('index.php') : base_url('archive.php')],
        ['label' => $course['name']],
    ]); ?>

    <!-- Course header -->
    <section class="lc-card mb-4" aria-labelledby="course-title">
        <div class="course-card-accent"></div>
        <div class="lc-card-body">
            <div class="d-flex flex-wrap align-items-start gap-3">
                <span class="course-card-icon" style="width:56px;height:56px;font-size:26px" aria-hidden="true">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </span>
                <div class="min-w-0 flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                        <h1 id="course-title" class="h3 mb-0 lc-wrap"><?= e($course['name']) ?></h1>
                        <?php if (!empty($course['code'])): ?>
                            <span class="course-card-code"><?= e($course['code']) ?></span>
                        <?php endif; ?>
                        <?php if ($course['is_current']): ?>
                            <span class="badge-soft-success badge-type"><?= e(t('term.current_badge')) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 course-card-meta">
                        <?php if (!empty($course['instructor'])): ?>
                            <span><i class="bi bi-person-badge" aria-hidden="true"></i> <?= e($course['instructor']) ?></span>
                        <?php endif; ?>
                        <span><i class="bi bi-collection" aria-hidden="true"></i> <?= (int)$totalItems ?> <?= e(t('admin.stats.items')) ?></span>
                    </div>
                </div>
                <?php if ($isUploader): ?>
                    <a href="<?= ea(base_url('admin/upload.php?course_id=' . (int)$course['id'])) ?>" class="btn btn-primary">
                        <i class="bi bi-upload" aria-hidden="true"></i> <?= e(t('nav.upload')) ?>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Type summary -->
            <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top course-card-counts">
                <?php foreach (['lecture', 'assignment', 'summary', 'exam', 'other'] as $type): ?>
                    <?php if ($counts[$type] > 0): ?>
                        <span class="d-inline-flex align-items-center gap-1">
                            <span class="badge-type <?= e(item_type_badge($type)) ?>">
                                <i class="bi <?= e(item_type_icon($type)) ?>" aria-hidden="true"></i>
                                <?= (int)$counts[$type] ?>
                            </span>
                            <?= e(t("item.filter.{$type}")) ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Search + filters -->
    <section aria-label="<?= ea(t('common.filter')) ?>">
        <div class="row g-2 align-items-center mb-3">
            <div class="col-12 col-md-6 col-lg-4">
                <label for="itemSearch" class="visually-hidden"><?= e(t('common.search')) ?></label>
                <div class="input-icon-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" class="form-control" id="itemSearch"
                           placeholder="<?= ea(t('item.search_placeholder')) ?>" autocomplete="off">
                </div>
            </div>
        </div>

        <div class="chip-row mb-3" role="group" aria-label="<?= ea(t('common.filter')) ?>">
            <button type="button" class="chip active" data-filter-type="all" aria-pressed="true">
                <i class="bi bi-grid" aria-hidden="true"></i> <?= e(t('item.filter_all')) ?>
                <span class="chip-count"><?= (int)$totalItems ?></span>
            </button>
            <?php foreach (['lecture', 'assignment', 'summary', 'exam', 'other'] as $type): ?>
                <button type="button" class="chip" data-filter-type="<?= e($type) ?>" aria-pressed="false">
                    <i class="bi <?= e(item_type_icon($type)) ?>" aria-hidden="true"></i> <?= e(t("item.filter.{$type}")) ?>
                    <span class="chip-count"><?= (int)$counts[$type] ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Items list -->
    <?php if ($items): ?>
        <div class="d-flex flex-column gap-3" id="itemsList">
            <?php foreach ($items as $item): ?>
                <?php lc_item_card($item, $filesByItem[(int)$item['id']] ?? []); ?>
            <?php endforeach; ?>
        </div>

        <div id="filterEmpty" class="lc-card d-none mt-3">
            <?php lc_empty_state(t('common.no_results'), t('item.search_placeholder'), 'bi-search'); ?>
        </div>
    <?php else: ?>
        <div class="lc-card">
            <?php lc_empty_state(
                t('common.empty_state'),
                $isUploader ? t('upload.title') : '',
                'bi-journal-x',
                $isUploader ? base_url('admin/upload.php?course_id=' . (int)$course['id']) : null,
                $isUploader ? t('nav.upload') : null
            ); ?>
        </div>
    <?php endif; ?>
</div>

<?php
$extraJs = '<script src="' . e(asset_url('js/countdown.js')) . '"></script>';
require __DIR__ . '/../src/layout/footer.php';
