<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// This page only works until the very first admin account is created.
if (admin_exists($pdo)) {
    $baseUrl = '../';
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="card">
        <p class="muted"><?= htmlspecialchars(t('admin_setup_done')) ?></p>
        <a class="btn-primary full" href="login.php"><?= htmlspecialchars(t('login_btn')) ?></a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    // Re-check right before inserting to close the race between two people
    // loading this form at the same time.
    if (admin_exists($pdo)) {
        header('Location: setup.php');
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $error = 'err_all_fields_required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'err_invalid_login';
    } elseif (strlen($password) < 6) {
        $error = 'err_password_short';
    } elseif ($password !== $confirm) {
        $error = 'err_password_mismatch';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $ins->execute([$name, $email, $hash, 'admin', 'active', time()]);
        header('Location: login.php');
        exit;
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_setup_title')) ?></h2>
    <p class="muted"><?= htmlspecialchars(t('admin_setup_note')) ?></p>
    <form method="post" action="setup.php">
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
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
