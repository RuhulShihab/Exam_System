<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$examId = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;

if ($examId > 0) {
    $stmt = $pdo->prepare(
        "SELECT s.*, e.title AS exam_title, e.code AS exam_code
         FROM submissions s
         JOIN exams e ON e.id = s.exam_id
         WHERE s.exam_id = ?
         ORDER BY s.id DESC"
    );
    $stmt->execute([$examId]);
} else {
    $stmt = $pdo->query(
        "SELECT s.*, e.title AS exam_title, e.code AS exam_code
         FROM submissions s
         JOIN exams e ON e.id = s.exam_id
         ORDER BY s.id DESC"
    );
}
$subs = $stmt->fetchAll();
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('manage_submissions')) ?></h3>
    <p class="muted"><?= htmlspecialchars(t('admin_all_subs_note')) ?></p>

    <?php if (!$subs): ?>
        <p class="muted"><?= htmlspecialchars(t('no_submissions_yet')) ?></p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th><?= htmlspecialchars(t('table_student')) ?></th>
                <th><?= htmlspecialchars(t('table_exam')) ?></th>
                <th><?= htmlspecialchars(t('table_score')) ?></th>
                <th><?= htmlspecialchars(t('table_status')) ?></th>
                <th><?= htmlspecialchars(t('table_actions')) ?></th>
            </tr>
            <?php foreach ($subs as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['student_name']) ?></td>
                    <td><?= htmlspecialchars($s['exam_title']) ?> <span class="code-tag small"><?= htmlspecialchars($s['exam_code']) ?></span></td>
                    <td><?= (int)$s['score'] ?> / <?= (int)$s['total'] ?></td>
                    <td>
                        <?php if ($s['auto_submitted']): ?>
                            <span class="pill pill-flag"><?= htmlspecialchars(t('status_auto')) ?></span>
                        <?php else: ?>
                            <span class="pill pill-ok"><?= htmlspecialchars(t('status_normal')) ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="post" action="submission_action.php" onsubmit="return confirm('<?= htmlspecialchars(t('confirm_delete_sub'), ENT_QUOTES) ?>');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="submission_id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="exam_id" value="<?= $examId ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="btn-danger"><?= htmlspecialchars(t('action_delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
