<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_role($pdo, 'teacher', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}
require_csrf();

$teacherId = current_user()['id'];
$examId = (int)($_POST['exam_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ? AND teacher_id = ?');
$stmt->execute([$examId, $teacherId]);
$exam = $stmt->fetch();

if ($exam && $action === 'delete') {
    $pdo->prepare('DELETE FROM submissions WHERE exam_id = ?')->execute([$examId]);
    $pdo->prepare('DELETE FROM questions WHERE exam_id = ?')->execute([$examId]);
    $pdo->prepare('DELETE FROM exams WHERE id = ?')->execute([$examId]);
    log_action($pdo, current_user(), 'exam_deleted', 'Exam "' . $exam['code'] . '" deleted by its owner');
}

header('Location: dashboard.php');
exit;
