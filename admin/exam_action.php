<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exams.php');
    exit;
}
require_csrf();

$action = $_POST['action'] ?? '';
$examId = (int)($_POST['exam_id'] ?? 0);

if ($action === 'delete' && $examId > 0) {
    $pdo->prepare('DELETE FROM submissions WHERE exam_id = ?')->execute([$examId]);
    $pdo->prepare('DELETE FROM questions WHERE exam_id = ?')->execute([$examId]);
    $pdo->prepare('DELETE FROM exams WHERE id = ?')->execute([$examId]);
}

header('Location: exams.php');
exit;
