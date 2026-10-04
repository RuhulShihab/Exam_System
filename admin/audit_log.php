<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$logs = $pdo->query('SELECT * FROM audit_log ORDER BY id DESC LIMIT 200')->fetchAll();
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('audit_log_title')) ?></h3>
    <p class="muted"><?= htmlspecialchars(t('audit_log_note')) ?></p>

    <?php if (!$logs): ?>
        <p class="muted"><?= htmlspecialchars(t('no_audit_logs')) ?></p>
    <?php else: ?>
        <table class="admin-table">
            <tr>
                <th><?= htmlspecialchars(t('audit_col_time')) ?></th>
                <th><?= htmlspecialchars(t('audit_col_actor')) ?></th>
                <th><?= htmlspecialchars(t('audit_col_action')) ?></th>
                <th><?= htmlspecialchars(t('audit_col_details')) ?></th>
            </tr>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td class="muted small"><?= htmlspecialchars(date('d M Y, H:i', (int)$l['created_at'])) ?></td>
                    <td><?= htmlspecialchars($l['actor_name'] ?: '-') ?> <span class="muted small">(<?= htmlspecialchars($l['actor_role'] ?: '-') ?>)</span></td>
                    <td><?= htmlspecialchars($l['action']) ?></td>
                    <td class="muted small"><?= htmlspecialchars($l['details'] ?: '') ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
