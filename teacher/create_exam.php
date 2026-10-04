<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'teacher', 'login.php');

$teacherId = current_user()['id'];
$bankStmt = $pdo->prepare('SELECT * FROM question_bank WHERE teacher_id = ? ORDER BY id DESC');
$bankStmt->execute([$teacherId]);
$bank = $bankStmt->fetchAll();

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
$error = $_GET['error'] ?? '';
?>
<p><a class="link-btn" href="dashboard.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<form method="post" action="save_exam.php" id="examForm">
    <?= csrf_field() ?>
    <div class="card">
        <h2><?= htmlspecialchars(t('create_exam_title')) ?></h2>
        <label><?= htmlspecialchars(t('exam_name_label')) ?></label>
        <input type="text" name="title" placeholder="<?= htmlspecialchars(t('exam_name_placeholder')) ?>" required>
        <label><?= htmlspecialchars(t('duration_label')) ?></label>
        <input type="number" name="duration" min="1" value="30" required>
        <label><?= htmlspecialchars(t('exam_start_label')) ?></label>
        <input type="datetime-local" name="start_at">
        <label><?= htmlspecialchars(t('exam_end_label')) ?></label>
        <input type="datetime-local" name="end_at">
        <p class="muted small"><?= htmlspecialchars(t('exam_schedule_note')) ?></p>
<label class="checkbox-row"><input type="checkbox" name="randomize_questions" value="1"> 🔀 Randomize question order for each student</label>

    </div>

    <?php if ($bank): ?>
    <div class="card">
        <h3><?= htmlspecialchars(t('bank_insert_title')) ?></h3>
        <p class="muted small"><?= htmlspecialchars(t('bank_insert_note')) ?></p>
        <div class="bank-list">
            <?php foreach ($bank as $b): ?>
                <label class="bank-item">
                    <input type="checkbox" class="bank-check" value="<?= (int)$b['id'] ?>">
                    <?= htmlspecialchars(substr($b['question_text'], 0, 80) . (strlen($b['question_text']) > 80 ? '...' : '')) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn-ghost" id="insert-bank-btn"><?= htmlspecialchars(t('bank_insert_btn')) ?></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <h3><?= htmlspecialchars(t('questions_label')) ?></h3>
        <div id="q-container"></div>
        <button type="button" class="btn-ghost" id="add-q"><?= htmlspecialchars(t('add_question_btn')) ?></button>
    </div>

    <?php if ($error === 'missing'): ?>
        <p class="err-text"><?= htmlspecialchars(t('err_missing')) ?></p>
    <?php endif; ?>

    <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('save_exam_btn')) ?></button>
</form>

<template id="q-template">
    <div class="q-block">
        <div class="q-num"></div>
        <textarea name="q_text[]" required placeholder="<?= htmlspecialchars(t('question_placeholder')) ?>"></textarea>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" value="0" checked>
            <input type="text" name="q_opt1[]" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 1" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" value="1">
            <input type="text" name="q_opt2[]" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 2" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:6px;">
            <input type="radio" value="2">
            <input type="text" name="q_opt3[]" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 3" required style="flex:1; margin-bottom:0;">
        </div>
        <div class="row" style="align-items:center; margin-bottom:10px;">
            <input type="radio" value="3">
            <input type="text" name="q_opt4[]" placeholder="<?= htmlspecialchars(t('option_placeholder_prefix')) ?> 4" required style="flex:1; margin-bottom:0;">
        </div>
        <button type="button" class="link-btn remove-q"><?= htmlspecialchars(t('remove_question')) ?></button>
    </div>
</template>

<script>
window.QUESTION_LABEL = <?= json_encode(t('question_label_prefix')) ?>;
window.BANK_QUESTIONS = <?= json_encode(array_reduce($bank, function ($carry, $b) {
    $carry[$b['id']] = [
        'text' => $b['question_text'], 'opt1' => $b['opt1'], 'opt2' => $b['opt2'],
        'opt3' => $b['opt3'], 'opt4' => $b['opt4'], 'correct' => $b['correct_index'],
    ];
    return $carry;
}, [])) ?>;
</script>
<script src="../assets/exam_create.js"></script>
<script src="../assets/exam_bank_insert.js"></script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
