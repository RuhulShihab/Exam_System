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

$stmt = $pdo->prepare('SELECT * FROM exams WHERE id = ? AND teacher_id = ?');
$stmt->execute([$examId, $teacherId]);
$exam = $stmt->fetch();
if (!$exam) {
    header('Location: dashboard.php');
    exit;
}

$title = trim($_POST['title'] ?? '');
$duration = max(1, (int)($_POST['duration'] ?? 30));
$startAt = !empty($_POST['start_at']) ? strtotime($_POST['start_at']) : null;
$endAt = !empty($_POST['end_at']) ? strtotime($_POST['end_at']) : null;

$texts = $_POST['q_text'] ?? [];
$opt1 = $_POST['q_opt1'] ?? [];
$opt2 = $_POST['q_opt2'] ?? [];
$opt3 = $_POST['q_opt3'] ?? [];
$opt4 = $_POST['q_opt4'] ?? [];
$correct = $_POST['q_correct'] ?? [];

$validRows = [];
foreach ($texts as $i => $text) {
    $text = trim($text);
    if ($text === '') {
        continue;
    }
    $validRows[] = [
        $text,
        trim($opt1[$i] ?? ''),
        trim($opt2[$i] ?? ''),
        trim($opt3[$i] ?? ''),
        trim($opt4[$i] ?? ''),
        max(0, min(3, (int)($correct[$i] ?? 0))),
    ];
}

if ($title === '' || !$validRows) {
    header('Location: edit_exam.php?id=' . $examId . '&error=missing');
    exit;
}

$pdo->prepare('UPDATE exams SET title = ?, duration_minutes = ?, start_at = ?, end_at = ? WHERE id = ?')
    ->execute([$title, $duration, $startAt ?: null, $endAt ?: null, $examId]);

$pdo->prepare('DELETE FROM questions WHERE exam_id = ?')->execute([$examId]);

$qStmt = $pdo->prepare('INSERT INTO questions (exam_id, question_text, opt1, opt2, opt3, opt4, correct_index) VALUES (?, ?, ?, ?, ?, ?, ?)');
foreach ($validRows as $row) {
    [$text, $o1, $o2, $o3, $o4, $c] = $row;
    $qStmt->execute([$examId, $text, $o1, $o2, $o3, $o4, $c]);
}

log_action($pdo, current_user(), 'exam_updated', 'Exam "' . $exam['code'] . '" updated (' . count($validRows) . ' questions)');

header('Location: dashboard.php');
exit;
