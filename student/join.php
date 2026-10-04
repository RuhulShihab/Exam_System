<?php
require_once __DIR__ . '/../includes/auth.php';
$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
$error = $_GET['error'] ?? '';
?>
<p><a class="link-btn" href="../index.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('join_exam_title')) ?></h2>
    <form method="post" action="start.php">
        <?= csrf_field() ?>
        <label><?= htmlspecialchars(t('your_name_label')) ?></label>
        <input type="text" name="student_name" placeholder="<?= htmlspecialchars(t('your_name_placeholder')) ?>" required>
        <label><?= htmlspecialchars(t('exam_code_label')) ?></label>
        <input type="text" name="code" style="text-transform:uppercase;" placeholder="A1B2C3" required>
        <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('start_exam_btn')) ?></button>
    </form>
    <?php if (!empty($_SESSION['student_name'])): ?><p><a class="btn-ghost full" href="dashboard.php">📊 Open My Dashboard</a></p><?php endif; ?>
    <?php if ($error === 'notfound'): ?>
        <p class="err-text"><?= htmlspecialchars(t('err_code_not_found')) ?></p>
    <?php elseif ($error === 'notstarted'): ?>
        <p class="err-text"><?= htmlspecialchars(t('err_exam_not_started')) ?></p>
    <?php elseif ($error === 'ended'): ?>
        <p class="err-text"><?= htmlspecialchars(t('err_exam_ended')) ?></p>
    <?php endif; ?>
</div>

<div class="warn-banner"><?= htmlspecialchars(t('warning_banner_join')) ?></div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
