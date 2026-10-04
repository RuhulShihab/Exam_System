<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: submissions.php');
    exit;
}
require_csrf();

$action = $_POST['action'] ?? '';
$subId = (int)($_POST['submission_id'] ?? 0);
$examId = (int)($_POST['exam_id'] ?? 0);

if ($action === 'delete' && $subId > 0) {
    $pdo->prepare('DELETE FROM submissions WHERE id = ?')->execute([$subId]);
}

header('Location: submissions.php' . ($examId ? '?exam_id=' . $examId : ''));
exit;
