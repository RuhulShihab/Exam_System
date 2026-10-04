<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_role($pdo, 'admin', 'login.php');

$dbFile = __DIR__ . '/../data/exam.sqlite';
if (!is_file($dbFile)) {
    http_response_code(404);
    die('No database file found yet.');
}

log_action($pdo, current_user(), 'backup_downloaded', basename($dbFile));

// Close our own connection so we don't hold a lock while streaming the file.
$pdo = null;

$filename = 'exam_backup_' . date('Y-m-d_His') . '.sqlite';
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($dbFile));
header('Cache-Control: no-store');
readfile($dbFile);
exit;
