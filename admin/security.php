<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/totp.php';
require_role($pdo, 'admin', 'login.php');

$user = current_user();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row = $stmt->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'enable_start') {
        $_SESSION['pending_totp_secret'] = totp_generate_secret();
    } elseif ($action === 'enable_confirm') {
        $secret = $_SESSION['pending_totp_secret'] ?? '';
        $code = trim($_POST['code'] ?? '');
        if ($secret !== '' && totp_verify($secret, $code)) {
            $pdo->prepare('UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?')
                ->execute([$secret, $row['id']]);
            unset($_SESSION['pending_totp_secret']);
            log_action($pdo, $user, '2fa_enabled', '');
            $success = 'twofa_enabled_note';
            $row['totp_enabled'] = 1;
        } else {
            $error = 'err_invalid_2fa_code';
        }
    } elseif ($action === 'disable') {
        $password = (string)($_POST['password'] ?? '');
        if (password_verify($password, $row['password_hash'])) {
            $pdo->prepare('UPDATE users SET totp_secret = NULL, totp_enabled = 0 WHERE id = ?')->execute([$row['id']]);
            log_action($pdo, $user, '2fa_disabled', '');
            $success = 'twofa_disabled_note';
            $row['totp_enabled'] = 0;
            unset($_SESSION['pending_totp_secret']);
        } else {
            $error = 'err_invalid_login';
        }
    } elseif ($action === 'cancel_setup') {
        unset($_SESSION['pending_totp_secret']);
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$pendingSecret = $_SESSION['pending_totp_secret'] ?? null;
$otpauth = $pendingSecret ? totp_otpauth_uri($pendingSecret, $row['email'], get_setting($pdo, 'site_name', t('site_title'))) : '';
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<?php if ($success): ?>
    <div class="notice-box"><?= htmlspecialchars(t($success)) ?></div>
<?php endif; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('security_title')) ?></h3>

    <?php if (!empty($row['totp_enabled'])): ?>
        <p>
            <span class="badge badge-active"><?= htmlspecialchars(t('twofa_on')) ?></span>
        </p>
        <form method="post" action="security.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="disable">
            <label><?= htmlspecialchars(t('twofa_disable_note')) ?></label>
            <input type="password" name="password" required>
            <?php if ($error): ?><p class="err-text"><?= htmlspecialchars(t($error)) ?></p><?php endif; ?>
            <button type="submit" class="btn-danger"><?= htmlspecialchars(t('twofa_disable_btn')) ?></button>
        </form>

    <?php elseif ($pendingSecret): ?>
        <p><span class="badge badge-pending"><?= htmlspecialchars(t('twofa_off')) ?></span></p>
        <p class="muted"><?= htmlspecialchars(t('twofa_scan_note')) ?></p>
        <p>
            <?= htmlspecialchars(t('twofa_manual_key_label')) ?>:
            <span class="code-tag small"><?= htmlspecialchars($pendingSecret) ?></span>
        </p>
        <p class="muted small" style="word-break: break-all;"><?= htmlspecialchars($otpauth) ?></p>
        <form method="post" action="security.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="enable_confirm">
            <label><?= htmlspecialchars(t('twofa_code_label')) ?></label>
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]*" maxlength="6" required autofocus>
            <?php if ($error): ?><p class="err-text"><?= htmlspecialchars(t($error)) ?></p><?php endif; ?>
            <button type="submit" class="btn-primary"><?= htmlspecialchars(t('twofa_confirm_btn')) ?></button>
        </form>
        <form method="post" action="security.php" style="margin-top:8px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cancel_setup">
            <button type="submit" class="btn-ghost"><?= htmlspecialchars(t('cancel_btn')) ?></button>
        </form>

    <?php else: ?>
        <p><span class="badge badge-blocked"><?= htmlspecialchars(t('twofa_off')) ?></span></p>
        <form method="post" action="security.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="enable_start">
            <button type="submit" class="btn-primary"><?= htmlspecialchars(t('twofa_enable_btn')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
