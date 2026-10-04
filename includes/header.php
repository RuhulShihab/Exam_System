<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/auth.php';
try_auto_login($pdo);
$baseUrl = $baseUrl ?? '';
$__user = current_user();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($LANG) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars(t('site_title')) ?></title>
<link rel="stylesheet" href="<?= htmlspecialchars($baseUrl) ?>assets/style.css">
</head>
<body>
<div class="topbar">
    <a href="<?= htmlspecialchars($baseUrl) ?>index.php" class="brand"><?= htmlspecialchars(t('site_title')) ?></a>
    <div class="topbar-right">
        <?php if ($__user): ?>
            <span class="topbar-user"><?= htmlspecialchars(t('welcome_name')) ?>, <?= htmlspecialchars($__user['name']) ?></span>
            <a class="btn-lang btn-logout" href="<?= htmlspecialchars($baseUrl) ?><?= $__user['role'] === 'admin' ? 'admin/logout.php' : 'teacher/logout.php' ?>"><?= htmlspecialchars(t('logout_btn')) ?></a>
        <?php endif; ?>
        <form method="post" action="<?= htmlspecialchars($baseUrl) ?>toggle_lang.php" class="lang-form">
            <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
            <button type="submit" class="btn-lang"><?= $LANG === 'bn' ? 'English' : 'বাংলা' ?></button>
        </form>
    </div>
</div>
<div class="wrap">
