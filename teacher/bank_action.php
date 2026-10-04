<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_role($pdo, 'teacher', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bank.php');
    exit;
}
require_csrf();

$teacherId = current_user()['id'];
$bankId = (int)($_POST['bank_id'] ?? 0);
$pdo->prepare('DELETE FROM question_bank WHERE id = ? AND teacher_id = ?')->execute([$bankId, $teacherId]);

header('Location: bank.php');
exit;
