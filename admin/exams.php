<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$exams = $pdo->query(
    "SELECT e.*, u.name AS teacher_name, u.email AS teacher_email,
            (SELECT COUNT(*) FROM submissions s WHERE s.exam_id = e.id) AS sub_count
     FROM exams e
     LEFT JOIN users u ON u.id = e.teacher_id
     ORDER BY e.id DESC"
)->fetchAll();
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('manage_exams')) ?></h3>
    <p class="muted"><?= htmlspecialchars(t('admin_all_exams_note')) ?></p>

    <?php if (!$exams): ?>
        <p class="muted"><?= htmlspecialchars(t('no_exams_yet')) ?></p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th><?= htmlspecialchars(t('table_exam')) ?></th>
                <th><?= htmlspecialchars(t('table_teacher')) ?></th>
                <th><?= htmlspecialchars(t('exam_code_label')) ?></th>
                <th><?= htmlspecialchars(t('stat_submissions')) ?></th>
                <th><?= htmlspecialchars(t('table_actions')) ?></th>
            </tr>
            <?php foreach ($exams as $e): ?>
                <tr>
                    <td><?= htmlspecialchars($e['title']) ?></td>
                    <td><?= $e['teacher_name'] ? htmlspecialchars($e['teacher_name']) : '<span class="muted">' . htmlspecialchars(t('no_teacher_assigned')) . '</span>' ?></td>
                    <td><span class="code-tag small"><?= htmlspecialchars($e['code']) ?></span></td>
                    <td><?= (int)$e['sub_count'] ?></td>
                    <td>
                        <div class="actions-cell">
                            <a class="btn-ghost" href="submissions.php?exam_id=<?= (int)$e['id'] ?>"><?= htmlspecialchars(t('action_view_results')) ?></a>
                            <form method="post" action="exam_action.php" onsubmit="return confirm('<?= htmlspecialchars(t('confirm_delete_exam'), ENT_QUOTES) ?>');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="exam_id" value="<?= (int)$e['id'] ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-danger"><?= htmlspecialchars(t('action_delete')) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
