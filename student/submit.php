<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (empty($_SESSION['exam_code']) || empty($_SESSION['student_name'])) {
    header('Location: join.php');
    exit;
}

// The time-up path arrives as a plain GET redirect from exam.php (no form
// data), so it can't carry a CSRF token — only the real form POST needs one.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
}

$code = $_SESSION['exam_code'];
$name = $_SESSION['student_name'];

$stmt = $pdo->prepare('SELECT * FROM exams WHERE code = ?');
$stmt->execute([$code]);
$exam = $stmt->fetch();

if (!$exam) {
    header('Location: join.php');
    exit;
}

$qStmt = $pdo->prepare('SELECT * FROM questions WHERE exam_id = ?');
$qStmt->execute([$exam['id']]);
$questions = $qStmt->fetchAll();

// Scoring always happens server-side against the stored correct answers,
// never trusting a score value from the client.
$answers = $_POST['answers'] ?? [];
$score = 0;
$total = count($questions);
foreach ($questions as $q) {
    $given = isset($answers[$q['id']]) ? (int)$answers[$q['id']] : null;
    if ($given !== null && $given === (int)$q['correct_index']) {
        $score++;
    }
}

$timeUp = isset($_GET['timeup']);
$autoSubmitted = $timeUp ? 1 : (int)($_POST['auto_submitted'] ?? 0);
$reason = $timeUp ? 'time_up' : ($_POST['reason'] ?? '');

$ins = $pdo->prepare('INSERT INTO submissions (exam_id, student_name, score, total, auto_submitted, reason, submitted_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
$ins->execute([$exam['id'], $name, $score, $total, $autoSubmitted, $reason, time()]);

$_SESSION['result_title'] = $exam['title'];
$_SESSION['result_score'] = $score;
$_SESSION['result_total'] = $total;
$_SESSION['result_auto'] = $autoSubmitted;

unset($_SESSION['exam_code'], $_SESSION['student_name'], $_SESSION['exam_started_at']);

header('Location: result.php');
exit;
