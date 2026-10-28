<?php
/**
 * Archive page — previous terms with their courses
 */
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/layout/partials.php';

$user = auth_require();

$terms = db_select(
    'SELECT t.*,
            (SELECT COUNT(*) FROM courses c WHERE c.term_id = t.id) AS course_count
     FROM terms t
     WHERE t.is_current = 0
     ORDER BY t.created_at DESC'
);

$coursesByTerm = [];
if ($terms) {
    $termIds = array_column($terms, 'id');
    $placeholders = implode(',', array_fill(0, count($termIds), '?'));
    $courses = db_select(
        "SELECT c.*,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = 'lecture') AS lecture_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = 'assignment') AS assignment_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = 'summary') AS summary_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = 'exam') AS exam_count,
                (SELECT COUNT(*) FROM items i WHERE i.course_id = c.id AND i.type = 'other') AS other_count
         FROM courses c
         WHERE c.term_id IN ($placeholders)
         ORDER BY c.name",
        $termIds
    );
    foreach ($courses as $c) {
        $coursesByTerm[(int)$c['term_id']][] = $c;
    }
}

$currentTerm = db_select_one('SELECT * FROM terms WHERE is_current = 1 LIMIT 1');

$pageTitle = t('nav.archive') . ' — ' . t('app.name');
$livePage = 'archive';
require __DIR__ . '/../src/layout/header.php';
?>

<div class="mb-4">
    <h1 class="mb-1"><i class="bi bi-archive text-primary" aria-hidden="true"></i> <?= e(t('nav.archive')) ?></h1>
    <?php if ($currentTerm): ?>
        <p class="text-muted mb-0">
            <?= e(t('nav.courses')) ?> · <?= e($currentTerm['name']) ?> —
            <a href="<?= base_url('index.php') ?>"><?= e(t('term.current_badge')) ?></a>
        </p>
    <?php endif; ?>
</div>

<?php if ($terms): ?>
    <div class="accordion accordion-lc" id="archiveAccordion">
        <?php foreach ($terms as $i => $term): ?>
            <div class="accordion-item">
                <h2 class="accordion-header" id="heading-<?= (int)$term['id'] ?>">
                    <button class="accordion-button<?= $i === 0 ? '' : ' collapsed' ?>" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapse-<?= (int)$term['id'] ?>"
                            aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>" aria-controls="collapse-<?= (int)$term['id'] ?>">
                        <span class="d-flex align-items-center gap-2 flex-wrap">
                            <i class="bi bi-calendar-range" aria-hidden="true"></i>
                            <?= e($term['name']) ?>
                            <span class="badge-soft-muted badge-type"><?= (int)$term['course_count'] ?> <?= e(t('nav.courses')) ?></span>
                        </span>
                    </button>
                </h2>
                <div id="collapse-<?= (int)$term['id'] ?>" class="accordion-collapse collapse<?= $i === 0 ? ' show' : '' ?>"
                     aria-labelledby="heading-<?= (int)$term['id'] ?>" data-bs-parent="#archiveAccordion">
                    <div class="accordion-body">
                        <?php $termCourses = $coursesByTerm[(int)$term['id']] ?? []; ?>
                        <?php if ($termCourses): ?>
                            <div class="row g-3">
                                <?php foreach ($termCourses as $c): ?>
                                    <div class="col-12 col-sm-6 col-lg-4">
                                        <?php lc_course_card($c, [
                                            'lecture' => $c['lecture_count'],
                                            'assignment' => $c['assignment_count'],
                                            'summary' => $c['summary_count'],
                                            'exam' => $c['exam_count'],
                                            'other' => $c['other_count'],
                                        ], true); ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0"><?= e(t('course.no_courses')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="lc-card">
        <?php lc_empty_state(t('term.no_terms'), '', 'bi-archive'); ?>
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../src/layout/footer.php'; ?>