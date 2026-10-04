<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit.php';
require_role($pdo, 'teacher', 'login.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_exam.php');
    exit;
}
require_csrf();

$teacherId = current_user()['id'];
$title = trim($_POST['title'] ?? '');
$duration = max(1, (int)($_POST['duration'] ?? 30));
$startAt = !empty($_POST['start_at']) ? strtotime($_POST['start_at']) : null;
$endAt = !empty($_POST['end_at']) ? strtotime($_POST['end_at']) : null;
$randomize = !empty($_POST['randomize_questions']) ? 1 : 0;

$texts = $_POST['q_text'] ?? [];
$opt1 = $_POST['q_opt1'] ?? [];
$opt2 = $_POST['q_opt2'] ?? [];
$opt3 = $_POST['q_opt3'] ?? [];
$opt4 = $_POST['q_opt4'] ?? [];
$correct = $_POST['q_correct'] ?? [];

if ($title === '' || empty($texts)) {
    header('Location: create_exam.php?error=missing');
    exit;
}

$code = generate_code($pdo);
$stmt = $pdo->prepare('INSERT INTO exams (code, title, duration_minutes, teacher_id, start_at, end_at, randomize_questions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$code, $title, $duration, $teacherId, $startAt ?: null, $endAt ?: null, $randomize, time()]);
$examId = (int)$pdo->lastInsertId();

$qStmt = $pdo->prepare('INSERT INTO questions (exam_id, question_text, opt1, opt2, opt3, opt4, correct_index) VALUES (?, ?, ?, ?, ?, ?, ?)');

$saved = 0;
foreach ($texts as $i => $text) {
    $text = trim($text);
    if ($text === '') {
        continue;
    }
    $o1 = trim($opt1[$i] ?? '');
    $o2 = trim($opt2[$i] ?? '');
    $o3 = trim($opt3[$i] ?? '');
    $o4 = trim($opt4[$i] ?? '');
    $c = isset($correct[$i]) ? (int)$correct[$i] : 0;
    if ($c < 0 || $c > 3) {
        $c = 0;
    }
    $qStmt->execute([$examId, $text, $o1, $o2, $o3, $o4, $c]);
    $saved++;
}

if ($saved === 0) {
    // Nothing valid was saved — clean up the empty exam row.
    $pdo->prepare('DELETE FROM exams WHERE id = ?')->execute([$examId]);
    header('Location: create_exam.php?error=missing');
    exit;
}

log_action($pdo, current_user(), 'exam_created', 'Exam "' . $code . '" created with ' . $saved . ' questions');

header('Location: created.php?code=' . urlencode($code));
exit;
