<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$teachers = $pdo->query("SELECT * FROM users WHERE role = 'teacher' ORDER BY status = 'pending' DESC, id DESC")->fetchAll();

$flashPassword = $_SESSION['flash_new_password'] ?? null;
$flashFor = $_SESSION['flash_new_password_for'] ?? null;
unset($_SESSION['flash_new_password'], $_SESSION['flash_new_password_for']);

$statusBadge = [
    'pending' => ['status_pending', 'badge-pending'],
    'active'  => ['status_active', 'badge-active'],
    'blocked' => ['status_blocked', 'badge-blocked'],
];
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<?php if ($flashPassword): ?>
    <div class="notice-box">
        <?= htmlspecialchars(t('new_password_is')) ?> (<?= htmlspecialchars($flashFor) ?>):
        <span class="code-tag small"><?= htmlspecialchars($flashPassword) ?></span>
        <br><?= htmlspecialchars(t('new_password_note')) ?>
    </div>
<?php endif; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('manage_teachers')) ?></h3>
    <p><a class="btn-primary" href="create_teacher.php"><?= htmlspecialchars(t('add_teacher_btn')) ?></a></p>

    <?php if (!$teachers): ?>
        <p class="muted"><?= htmlspecialchars(t('no_teachers_yet')) ?></p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th><?= htmlspecialchars(t('table_name')) ?></th>
                <th><?= htmlspecialchars(t('table_email')) ?></th>
                <th><?= htmlspecialchars(t('table_status')) ?></th>
                <th><?= htmlspecialchars(t('table_actions')) ?></th>
            </tr>
            <?php foreach ($teachers as $tRow): $badge = $statusBadge[$tRow['status']] ?? ['status_pending', 'badge-pending']; ?>
                <tr>
                    <td><?= htmlspecialchars($tRow['name']) ?></td>
                    <td><?= htmlspecialchars($tRow['email']) ?></td>
                    <td><span class="badge <?= $badge[1] ?>"><?= htmlspecialchars(t($badge[0])) ?></span></td>
                    <td>
                        <div class="actions-cell">
                            <?php if ($tRow['status'] === 'pending'): ?>
                                <form method="post" action="teacher_action.php" onsubmit="return true;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="teacher_id" value="<?= (int)$tRow['id'] ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-ghost"><?= htmlspecialchars(t('action_approve')) ?></button>
                                </form>
                            <?php endif; ?>

                            <?php if ($tRow['status'] === 'blocked'): ?>
                                <form method="post" action="teacher_action.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="teacher_id" value="<?= (int)$tRow['id'] ?>">
                                    <input type="hidden" name="action" value="unblock">
                                    <button type="submit" class="btn-ghost"><?= htmlspecialchars(t('action_unblock')) ?></button>
                                </form>
                            <?php elseif ($tRow['status'] === 'active'): ?>
                                <form method="post" action="teacher_action.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="teacher_id" value="<?= (int)$tRow['id'] ?>">
                                    <input type="hidden" name="action" value="block">
                                    <button type="submit" class="btn-ghost"><?= htmlspecialchars(t('action_block')) ?></button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="teacher_action.php" onsubmit="return confirm('<?= htmlspecialchars(t('confirm_reset_password'), ENT_QUOTES) ?>');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="teacher_id" value="<?= (int)$tRow['id'] ?>">
                                <input type="hidden" name="action" value="reset_password">
                                <button type="submit" class="btn-ghost"><?= htmlspecialchars(t('action_reset_password')) ?></button>
                            </form>

                            <form method="post" action="teacher_action.php" onsubmit="return confirm('<?= htmlspecialchars(t('confirm_delete_teacher'), ENT_QUOTES) ?>');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="teacher_id" value="<?= (int)$tRow['id'] ?>">
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
