<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/rate_limit.php';

try_auto_login($pdo);
if (is_logged_in() && user_role() === 'admin') {
    header('Location: dashboard.php');
    exit;
}

if (!admin_exists($pdo)) {
    header('Location: setup.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (!rate_limit_check($pdo, 'admin_login', 20, 900)) {
        $error = 'err_rate_limited';
    } elseif ($email === '' || $password === '') {
        $error = 'err_all_fields_required';
    } else {
        $result = attempt_login($pdo, $email, $password, 'admin');
        if ($result['ok']) {
            if (!empty($result['row']['totp_enabled'])) {
                // Password was correct, but 2FA is required — don't finish
                // logging in yet. Stash the pending state and hand off.
                unset($_SESSION['user']);
                $_SESSION['pending_2fa_uid'] = $result['user']['id'];
                $_SESSION['pending_2fa_remember'] = !empty($_POST['remember']);
                header('Location: verify_2fa.php');
                exit;
            }
            if (!empty($_POST['remember'])) {
                issue_remember_cookie($pdo, $result['user']['id']);
            }
            log_action($pdo, $result['user'], 'login', 'Admin logged in');
            header('Location: dashboard.php');
            exit;
        }
        $error = [
            'invalid' => 'err_invalid_login',
            'locked'  => 'err_account_locked',
            'pending' => 'err_account_pending',
            'blocked' => 'err_account_blocked',
        ][$result['error']] ?? 'err_invalid_login';
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<p><a class="link-btn" href="../index.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('admin_login_title')) ?></h2>
    <form method="post" action="login.php">
        <?= csrf_field() ?>
        <label><?= htmlspecialchars(t('email_label')) ?></label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
        <label><?= htmlspecialchars(t('password_label')) ?></label>
        <input type="password" name="password" required>
        <label class="checkbox-row"><input type="checkbox" name="remember" value="1"> <?= htmlspecialchars(t('remember_me_label')) ?></label>
        <?php if ($error): ?>
            <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
        <?php endif; ?>
        <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('login_btn')) ?></button>
    </form>
    <p class="auth-links"><a href="forgot_password.php"><?= htmlspecialchars(t('forgot_password_link')) ?></a></p>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
