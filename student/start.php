<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: join.php');
    exit;
}
require_csrf();

$name = trim($_POST['student_name'] ?? '');
$code = strtoupper(trim($_POST['code'] ?? ''));

if ($name === '' || $code === '') {
    header('Location: join.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM exams WHERE code = ?');
$stmt->execute([$code]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: join.php?error=notfound');
    exit;
}

$now = time();
if (!empty($exam['start_at']) && $now < (int)$exam['start_at']) {
    header('Location: join.php?error=notstarted');
    exit;
}
if (!empty($exam['end_at']) && $now > (int)$exam['end_at']) {
    header('Location: join.php?error=ended');
    exit;
}

$_SESSION['student_name'] = $name;
$_SESSION['exam_code'] = $code;
$_SESSION['exam_started_at'] = time();

header('Location: exam.php');
exit;
