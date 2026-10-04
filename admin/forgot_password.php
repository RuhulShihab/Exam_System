<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/rate_limit.php';

$sent = false;
$error = '';
$devLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    if (!rate_limit_check($pdo, 'admin_forgot_password', 5, 900)) {
        $error = 'err_rate_limited';
    } else {
        $email = trim(strtolower($_POST['email'] ?? ''));
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = create_password_reset($pdo, (int)$user['id']);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            $resetLink = $scheme . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']) . '/reset_password.php?token=' . $token;

            $siteName = get_setting($pdo, 'site_name', t('site_title'));
            $mailed = send_app_mail(
                $user['email'],
                $siteName . ' - ' . t('forgot_password_title'),
                '<p>' . htmlspecialchars(t('reset_email_body')) . '</p><p><a href="' . htmlspecialchars($resetLink) . '">' . htmlspecialchars($resetLink) . '</a></p>'
            );
            if (!$mailed) {
                $devLink = $resetLink;
            }
        }
        $sent = true;
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';
?>
<p><a class="link-btn" href="login.php">&larr; <?= htmlspecialchars(t('back_home')) ?></a></p>

<div class="card">
    <h2><?= htmlspecialchars(t('forgot_password_title')) ?></h2>

    <?php if ($sent): ?>
        <p class="notice-box"><?= htmlspecialchars(t('forgot_password_sent_note')) ?></p>
        <?php if ($devLink): ?>
            <p class="notice-box">
                <?= htmlspecialchars(t('dev_mail_fallback_note')) ?><br>
                <a href="<?= htmlspecialchars($devLink) ?>"><?= htmlspecialchars($devLink) ?></a>
            </p>
        <?php endif; ?>
        <a class="btn-primary full" href="login.php"><?= htmlspecialchars(t('login_btn')) ?></a>
    <?php else: ?>
        <form method="post" action="forgot_password.php">
            <?= csrf_field() ?>
            <label><?= htmlspecialchars(t('email_label')) ?></label>
            <input type="email" name="email" required autofocus>
            <?php if ($error): ?>
                <p class="err-text"><?= htmlspecialchars(t($error)) ?></p>
            <?php endif; ?>
            <button type="submit" class="btn-primary full"><?= htmlspecialchars(t('forgot_password_btn')) ?></button>
        </form>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
