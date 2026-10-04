<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'admin', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: teachers.php');
    exit;
}
require_csrf();

$action = $_POST['action'] ?? '';
$teacherId = (int)($_POST['teacher_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'teacher'");
$stmt->execute([$teacherId]);
$teacher = $stmt->fetch();

if (!$teacher) {
    header('Location: teachers.php');
    exit;
}

switch ($action) {
    case 'approve':
        $pdo->prepare("UPDATE users SET status = 'active', failed_attempts = 0, locked_until = 0 WHERE id = ?")->execute([$teacherId]);
        break;

    case 'block':
        $pdo->prepare("UPDATE users SET status = 'blocked' WHERE id = ?")->execute([$teacherId]);
        break;

    case 'unblock':
        $pdo->prepare("UPDATE users SET status = 'active', failed_attempts = 0, locked_until = 0 WHERE id = ?")->execute([$teacherId]);
        break;

    case 'delete':
        // Remove the teacher's exams, their questions, and student submissions
        // for those exams, then the account itself.
        $examStmt = $pdo->prepare('SELECT id FROM exams WHERE teacher_id = ?');
        $examStmt->execute([$teacherId]);
        $examIds = array_column($examStmt->fetchAll(), 'id');

        foreach ($examIds as $examId) {
            $pdo->prepare('DELETE FROM submissions WHERE exam_id = ?')->execute([$examId]);
            $pdo->prepare('DELETE FROM questions WHERE exam_id = ?')->execute([$examId]);
        }
        if ($examIds) {
            $pdo->prepare('DELETE FROM exams WHERE teacher_id = ?')->execute([$teacherId]);
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$teacherId]);
        break;

    case 'reset_password':
        $newPassword = bin2hex(random_bytes(5)); // 10-char random password
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE users SET password_hash = ?, failed_attempts = 0, locked_until = 0 WHERE id = ?')->execute([$hash, $teacherId]);
        $_SESSION['flash_new_password'] = $newPassword;
        $_SESSION['flash_new_password_for'] = $teacher['email'];
        break;
}

header('Location: teachers.php');
exit;
