<?php
require_once __DIR__ . '/includes/session_boot.php';

$_SESSION['lang'] = (isset($_SESSION['lang']) && $_SESSION['lang'] === 'bn') ? 'en' : 'bn';

$redirect = $_POST['redirect'] ?? 'index.php';
// Only allow relative redirects to avoid open-redirect abuse.
if (preg_match('#^https?://#i', $redirect) || strpos($redirect, '//') === 0) {
    $redirect = 'index.php';
}

header('Location: ' . $redirect);
exit;
