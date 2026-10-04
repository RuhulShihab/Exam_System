<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'teacher', 'login.php');

$teacherId = current_user()['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $text = trim($_POST['q_text'] ?? '');
    $o1 = trim($_POST['q_opt1'] ?? '');
    $o2 = trim($_POST['q_opt2'] ?? '');
    $o3 = trim($_POST['q_opt3'] ?? '');
    $o4 = trim($_POST['q_opt4'] ?? '');
    $c = max(0, min(3, (int)($_POST['q_correct'] ?? 0)));

    if ($text === '' || $o1 === '' || $o2 === '' || $o3 === '' || $o4 === '') {
        $error = 'err_missing';
    } else {
        $pdo->prepare(
            'INSERT INTO question_bank (teacher_id, question_text, opt1, opt2, opt3, opt4, correct_index, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([$teacherId, $text, $o1, $o2, $o3, $o4, $c, time()]);
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$bankStmt = $pdo->prepare('SELECT * FROM question_bank WHERE teacher_id = ? ORDER BY id DESC');
$bankStmt->execute([$teacherId]);
$bankItems = $bankStmt->fetchAll();
?>
<p><a class="link-btn" href="dashboard.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('bank_title')) ?></h2>
    <p class="muted"><?= htmlspecialchars(t('bank_note')) ?></p>
    <form method="post" action="bank.php">
        <?= csrf_field() ?>
        <label><?= htmlspecialchars(t('question_placeholder')) ?></label>
        <textarea name="q_text" required></textarea>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" name="q_correct" value="0" checked>
            <input type="text" name="q_opt1" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 1" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" name="q_correct" value="1">
            <input type="text" name="q_opt2" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 2" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" name="q_correct" value="2">
            <input type="text" name="q_opt3" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 3" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:10px;">
            <input type="radio" name="q_correct" value="3">
            <input type="text" name="q_opt4" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 4" required style="flex:1; margin-bottom:0;">
        </div>
        <?php if ($error): ?><p class="err-text"><?= htmlspecialchars(t($error)) ?></p><?php endif; ?>
        <button type="submit" class="btn-primary"><?= htmlspecialchars(t('bank_add_btn')) ?></button>
    </form>
</div>

<div class="card">
    <h3><?= htmlspecialchars(t('bank_saved_title')) ?></h3>
    <?php if (!$bankItems): ?>
        <p class="muted"><?= htmlspecialchars(t('bank_empty_note')) ?></p>
    <?php else: ?>
        <table class="admin-table">
            <tr><th><?= htmlspecialchars(t('table_question')) ?></th><th><?= htmlspecialchars(t('table_actions')) ?></th></tr>
            <?php foreach ($bankItems as $b): ?>
                <tr>
                    <td><?= htmlspecialchars(substr($b['question_text'], 0, 100) . (strlen($b['question_text']) > 100 ? '...' : '')) ?></td>
                    <td>
                        <form method="post" action="bank_action.php" onsubmit="return confirm('<?= htmlspecialchars(t('action_delete'), ENT_QUOTES) ?>?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="bank_id" value="<?= (int)$b['id'] ?>">
                            <button type="submit" class="btn-danger"><?= htmlspecialchars(t('action_delete')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
