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
<div class="card score-hero">
    <div class="muted"><?= htmlspecialchars(t('exam_created_note')) ?></div>
    <div style="margin:16px 0;"><span class="code-tag"><?= htmlspecialchars($code) ?></span></div>
    <?php if ($exam): ?><div class="muted"><?= htmlspecialchars($exam['title']) ?></div><?php endif; ?>
</div>
<div class="row center">
    <a class="btn-ghost" href="dashboard.php"><?= htmlspecialchars(t('teacher_dashboard')) ?></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
