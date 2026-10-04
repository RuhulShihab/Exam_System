<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

$error = '';
$createdPassword = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = trim(strtolower($_POST['email'] ?? ''));

    if ($name === '' || $email === '') {
        $error = 'err_all_fields_required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'err_invalid_login';
    } else {
        $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $check->execute([$email]);
        if ((int)$check->fetchColumn() > 0) {
            $error = 'err_email_taken';
        } else {
            $createdPassword = bin2hex(random_bytes(5));
            $hash = password_hash($createdPassword, PASSWORD_DEFAULT);
            $ins = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute([$name, $email, $hash, 'teacher', 'active', time()]);
        }
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<p><a class="link-btn" href="teachers.php">&larr; <?= htmlspecialchars(t('back_to_admin')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('add_teacher_btn')) ?></h2>

    <?php if ($createdPassword): ?>
        <p class="notice-box"><?= htmlspecialchars(t('teacher_created_note')) ?></p>
        <p class="notice-box">
            <?= htmlspecialchars(t('new_password_is')) ?>:
            <span class="code-tag small"><?= htmlspecialchars($createdPassword) ?></span>
            <br><?= htmlspecialchars(t('new_password_note')) ?>
        </p>
        <a class="btn-primary full" href="teachers.php"><?= htmlspecialchars(t('back_to_admin')) ?></a>
    <?php else: ?>
        <form method="post" action="create_teacher.php">
            <?= csrf_field() ?>
            <label><?= htmlspecialchars(t('full_name_label')) ?></label>
            <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            <label><?= htmlspecialchars(t('email_label')) ?></label>
            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            <?php if ($error): ?>
                <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('register_btn')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
