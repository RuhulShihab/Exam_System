<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/db.php';
clear_remember_cookie($pdo);
logout_user();
header('Location: login.php');
exit;
