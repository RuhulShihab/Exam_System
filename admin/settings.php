<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_role($pdo, 'admin', 'login.php');

$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $siteName = trim($_POST['site_name'] ?? '');
    if ($siteName !== '') {
        set_setting($pdo, 'site_name', $siteName);
        log_action($pdo, current_user(), 'settings_updated', 'Site name changed to "' . $siteName . '"');
        $saved = true;
    }
}

$baseUrl = '../';
require_once __DIR__ . '/../includes/header.php';

$currentName = get_setting($pdo, 'site_name', t('site_title'));
?>
<div class="card">
    <h2><?= htmlspecialchars(t('admin_panel')) ?></h2>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<?php if ($saved): ?>
    <div class="notice-box"><?= htmlspecialchars(t('settings_saved_note')) ?></div>
<?php endif; ?>

<div class="card">
    <h3><?= htmlspecialchars(t('settings_title')) ?></h3>
    <form method="post" action="settings.php">
        <?= csrf_field() ?>
        <label><?= htmlspecialchars(t('site_name_label')) ?></label>
        <input type="text" name="site_name" value="<?= htmlspecialchars($currentName) ?>" required>
        <button type="submit" class="btn-primary"><?= htmlspecialchars(t('save_settings_btn')) ?></button>
    </form>
</div>

<div class="card">
    <h3><?= htmlspecialchars(t('backup_section_title')) ?></h3>
    <p class="muted"><?= htmlspecialchars(t('backup_note')) ?></p>
    <a class="btn-ghost" href="backup.php"><?= htmlspecialchars(t('download_backup_btn')) ?></a>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
