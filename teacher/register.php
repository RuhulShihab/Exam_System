<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rate_limit.php';

if (is_logged_in()) {
    header('Location: ' . (user_role() === 'admin' ? '../admin/dashboard.php' : 'dashboard.php'));
    exit;
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (!rate_limit_check($pdo, 'teacher_register', 5, 3600)) {
        $error = 'err_rate_limited';
    } elseif ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'err_all_fields_required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'err_invalid_login';
    } elseif (strlen($password) < 6) {
        $error = 'err_password_short';
    } elseif ($password !== $confirm) {
        $error = 'err_password_mismatch';
    } else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $check->execute([$email]);
        if ((int)$check->fetchColumn() > 0) {
            $error = 'err_email_taken';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute([$name, $email, $hash, 'teacher', 'pending', time()]);
            log_action($pdo, null, 'teacher_registered', $email . ' registered and is awaiting approval');
            $success = true;
        }
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<p><a class="link-btn" href="../index.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('teacher_register_title')) ?></h2>

    <?php if ($success): ?>
        <p class="notice-box"><?= htmlspecialchars(t('register_success_note')) ?></p>
        <a class="btn-primary full" href="login.php"><?= htmlspecialchars(t('login_btn')) ?></a>
    <?php else: ?>
        <form method="post" action="register.php">
            <?= csrf_field() ?>
            <label><?= htmlspecialchars(t('full_name_label')) ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            <label><?= htmlspecialchars(t('email_label')) ?></label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <label><?= htmlspecialchars(t('password_label')) ?></label>
            <input type="password" name="password" required>
            <label><?= htmlspecialchars(t('confirm_password_label')) ?></label>
            <input type="password" name="confirm_password" required>
            <?php if ($error): ?>
                <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('register_btn')) ?></button>
        </form>
        <p class="auth-links"><?= htmlspecialchars(t('have_account_already')) ?> <a href="login.php"><?= htmlspecialchars(t('login_btn')) ?></a></p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
