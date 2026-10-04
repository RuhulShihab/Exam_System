<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'teacher', 'login.php');

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$code = strtoupper(trim($_GET['code'] ?? ''));
$stmt = $pdo->prepare('SELECT * FROM exams WHERE code = ? AND teacher_id = ?');
$stmt->execute([$code, current_user()['id']]);
$exam = $stmt->fetch();
?>
<p><a class="link-btn" href="dashboard.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('results_title')) ?> &mdash;
        <span class="code-tag small"><?= htmlspecialchars($code) ?></span>
    </h2>

    <?php if (!$exam): ?>
        <p class="muted">Not found.</p>
    <?php else:
        $subStmt = $pdo->prepare('SELECT * FROM submissions WHERE exam_id = ? ORDER BY id DESC');
        $subStmt->execute([$exam['id']]);
        $subs = $subStmt->fetchAll();
        ?>
        <?php if (!$subs): ?>
            <p class="muted"><?= htmlspecialchars(t('no_submissions_yet')) ?></p>
        <?php else: ?>
            <table class="sub-table">
                <tr>
                    <th><?= htmlspecialchars(t('table_student')) ?></th>
                    <th><?= htmlspecialchars(t('table_score')) ?></th>
                    <th><?= htmlspecialchars(t('table_status')) ?></th>
                </tr>
                <?php foreach ($subs as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['student_name']) ?></td>
                        <td><?= (int)$s['score'] ?> / <?= (int)$s['total'] ?></td>
                        <td>
                            <?php if ($s['auto_submitted']): ?>
                                <span class="pill pill-flag"><?= htmlspecialchars(t('status_auto')) ?></span>
                            <?php else: ?>
                                <span class="pill pill-ok"><?= htmlspecialchars(t('status_normal')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
