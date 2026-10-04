<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/totp.php';
require_once __DIR__ . '/../includes/rate_limit.php';

if (empty($_SESSION['pending_2fa_uid'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!rate_limit_check($pdo, 'admin_2fa', 10, 900)) {
        $error = 'err_rate_limited';
    } else {
        $code = trim($_POST['code'] ?? '');
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['pending_2fa_uid']]);
        $user = $stmt->fetch();

        if ($user && $user['totp_enabled'] && $user['status'] === 'active' && totp_verify($user['totp_secret'], $code)) {
            $remember = !empty($_SESSION['pending_2fa_remember']);
            unset($_SESSION['pending_2fa_uid'], $_SESSION['pending_2fa_remember']);

            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ];
            if ($remember) {
                issue_remember_cookie($pdo, (int)$user['id']);
            }
            log_action($pdo, $_SESSION['user'], 'login_2fa', 'Admin completed 2FA login');
            header('Location: dashboard.php');
            exit;
        }
        $error = 'err_invalid_2fa_code';
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <h2><?= htmlspecialchars(t('twofa_verify_title')) ?></h2>
    <p class="muted"><?= htmlspecialchars(t('twofa_verify_note')) ?></p>
    <form method="post" action="verify_2fa.php">
        <?= csrf_field() ?>
        <label><?= htmlspecialchars(t('twofa_code_label')) ?></label>
        <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
        <?php if ($error): ?>
            <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
        <?php endif; ?>
        <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('login_btn')) ?></button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
