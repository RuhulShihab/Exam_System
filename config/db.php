<?php
$dbDir = __DIR__ . '/../data';
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0777, true);
}
$dbFile = $dbDir . '/exam.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    http_response_code(500);
    die('A server error occurred. Please try again later.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'teacher',      -- 'admin' or 'teacher'
    status TEXT NOT NULL DEFAULT 'pending',    -- 'pending', 'active', 'blocked'
    failed_attempts INTEGER NOT NULL DEFAULT 0,
    locked_until INTEGER NOT NULL DEFAULT 0,
    totp_secret TEXT,
    totp_enabled INTEGER NOT NULL DEFAULT 0,
    created_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS exams (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL,
    teacher_id INTEGER,
    start_at INTEGER,
    end_at INTEGER,
    created_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    opt1 TEXT NOT NULL,
    opt2 TEXT NOT NULL,
    opt3 TEXT NOT NULL,
    opt4 TEXT NOT NULL,
    correct_index INTEGER NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    exam_id INTEGER NOT NULL,
    student_name TEXT NOT NULL,
    score INTEGER NOT NULL,
    total INTEGER NOT NULL,
    auto_submitted INTEGER NOT NULL DEFAULT 0,
    reason TEXT,
    submitted_at INTEGER NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS question_bank (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    teacher_id INTEGER NOT NULL,
    question_text TEXT NOT NULL,
    opt1 TEXT NOT NULL,
    opt2 TEXT NOT NULL,
    opt3 TEXT NOT NULL,
    opt4 TEXT NOT NULL,
    correct_index INTEGER NOT NULL,
    created_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    token_hash TEXT NOT NULL,
    expires_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    selector TEXT UNIQUE NOT NULL,
    validator_hash TEXT NOT NULL,
    expires_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    actor_id INTEGER,
    actor_name TEXT,
    actor_role TEXT,
    action TEXT NOT NULL,
    details TEXT,
    created_at INTEGER NOT NULL
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS login_throttle (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_key TEXT NOT NULL,
    action TEXT NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 1,
    window_start INTEGER NOT NULL,
    UNIQUE(ip_key, action)
)");

/**
 * Lightweight migrations: add columns to tables that may have been created
 * by an older version of this app, without wiping existing data.
 */
function column_exists(PDO $pdo, string $table, string $column): bool
{
    $cols = $pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        if ($col['name'] === $column) {
            return true;
        }
    }
    return false;
}

if (!column_exists($pdo, 'exams', 'teacher_id')) {
    $pdo->exec('ALTER TABLE exams ADD COLUMN teacher_id INTEGER');
}
if (!column_exists($pdo, 'exams', 'start_at')) {
    $pdo->exec('ALTER TABLE exams ADD COLUMN start_at INTEGER');
}
if (!column_exists($pdo, 'exams', 'end_at')) {
    $pdo->exec('ALTER TABLE exams ADD COLUMN end_at INTEGER');
}
if (!column_exists($pdo, 'exams', 'randomize_questions')) {
    $pdo->exec('ALTER TABLE exams ADD COLUMN randomize_questions INTEGER NOT NULL DEFAULT 0');
}
$pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    audience TEXT NOT NULL DEFAULT 'all',
    sender_id INTEGER,
    sender_role TEXT,
    created_at INTEGER NOT NULL
)");

/* Notification sender migrations for teacher/admin announcements. */
if (!column_exists($pdo, 'notifications', 'sender_id')) {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN sender_id INTEGER');
}
if (!column_exists($pdo, 'notifications', 'sender_role')) {
    $pdo->exec('ALTER TABLE notifications ADD COLUMN sender_role TEXT');
}

if (!column_exists($pdo, 'users', 'totp_secret')) {
    $pdo->exec('ALTER TABLE users ADD COLUMN totp_secret TEXT');
}
if (!column_exists($pdo, 'users', 'totp_enabled')) {
    $pdo->exec('ALTER TABLE users ADD COLUMN totp_enabled INTEGER NOT NULL DEFAULT 0');
}

/**
 * Generate a unique 6-character exam code.
 */
function generate_code(PDO $pdo): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM exams WHERE code = ?');
        $stmt->execute([$code]);
    } while ((int)$stmt->fetchColumn() > 0);

    return $code;
}

require_once __DIR__ . '/settings.php';
