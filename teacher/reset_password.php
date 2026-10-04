<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (strlen($password) < 6) {
        $error = 'err_password_short';
    } elseif ($password !== $confirm) {
        $error = 'err_password_mismatch';
    } else {
        $user = consume_password_reset($pdo, $token);
        if (!$user || $user['role'] !== 'teacher') {
            $error = 'err_invalid_token';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = 0 WHERE id = ?')
                ->execute([$hash, $user['id']]);
            $done = true;
        }
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2><?= htmlspecialchars(t('reset_password_title')) ?></h2>

    <?php if ($done): ?>
        <p class="notice-box"><?= htmlspecialchars(t('reset_success_note')) ?></p>
        <a class="btn-primary full" href="login.php"><?= htmlspecialchars(t('login_btn')) ?></a>
    <?php else: ?>
        <form method="post" action="reset_password.php">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <label><?= htmlspecialchars(t('password_label')) ?></label>
            <input type="password" name="password" required autofocus>
            <label><?= htmlspecialchars(t('confirm_password_label')) ?></label>
            <input type="password" name="confirm_password" required>
            <?php if ($error): ?>
                <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('reset_password_btn')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
