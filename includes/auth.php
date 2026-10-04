<?php
require_once __DIR__ . '/session_boot.php';

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 15 * 60; // 15 minutes
const RESET_TOKEN_TTL = 60 * 60;    // 1 hour
const REMEMBER_COOKIE = 'exam_remember';
const REMEMBER_DAYS = 30;

/* -------------------------------------------------------------------- */
/* CSRF protection                                                       */
/* -------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verifies the CSRF token from a POST request. Kills the request with a 403
 * if it does not match, rather than silently continuing.
 */
function require_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        die('Security check failed (invalid or expired form token). Please go back and try again.');
    }
}

/* -------------------------------------------------------------------- */
/* Current user / session helpers                                       */
/* -------------------------------------------------------------------- */

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function user_role(): ?string
{
    $u = current_user();
    return $u['role'] ?? null;
}

/**
 * Redirects unauthenticated / wrong-role users away, halting script execution.
 * Also attempts a remember-me auto-login first, so a valid "remember me"
 * cookie survives an expired session.
 */
function require_role(PDO $pdo, string $role, string $loginPath): void
{
    try_auto_login($pdo);
    $u = current_user();
    if (!$u || $u['role'] !== $role) {
        header('Location: ' . $loginPath);
        exit;
    }
}

function logout_user(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

/* -------------------------------------------------------------------- */
/* Login (shared by teacher + admin, both stored in the users table)     */
/* -------------------------------------------------------------------- */

/**
 * Attempts to log a user in. Returns an associative array:
 *   ['ok' => true, 'user' => [...]]  on success
 *   ['ok' => false, 'error' => 'invalid'|'locked'|'pending'|'blocked']
 */
function attempt_login(PDO $pdo, string $email, string $password, string $expectedRole): array
{
    $email = trim(strtolower($email));
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, $expectedRole]);
    $user = $stmt->fetch();

    if (!$user) {
        // Do a dummy hash verify so login timing doesn't reveal whether the
        // email exists.
        password_verify($password, '$2y$10$abcdefghijklmnopqrstuuOeuQKp8b0i8b0i8b0i8b0i8b0i8b0i8');
        return ['ok' => false, 'error' => 'invalid'];
    }

    $now = time();
    if ((int)$user['locked_until'] > $now) {
        return ['ok' => false, 'error' => 'locked', 'locked_until' => (int)$user['locked_until']];
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)$user['failed_attempts'] + 1;
        $lockUntil = 0;
        if ($attempts >= LOGIN_MAX_ATTEMPTS) {
            $lockUntil = $now + LOGIN_LOCK_SECONDS;
            $attempts = 0;
        }
        $pdo->prepare('UPDATE users SET failed_attempts = ?, locked_until = ? WHERE id = ?')
            ->execute([$attempts, $lockUntil, $user['id']]);
        return ['ok' => false, 'error' => $lockUntil ? 'locked' : 'invalid', 'locked_until' => $lockUntil];
    }

    if ($user['status'] === 'pending') {
        return ['ok' => false, 'error' => 'pending'];
    }
    if ($user['status'] === 'blocked') {
        return ['ok' => false, 'error' => 'blocked'];
    }

    // Success: reset attempt counter, rotate session id to prevent fixation.
    $pdo->prepare('UPDATE users SET failed_attempts = 0, locked_until = 0 WHERE id = ?')->execute([$user['id']]);
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    return ['ok' => true, 'user' => $_SESSION['user'], 'row' => $user];
}

/**
 * Ensures a first admin account exists. Returns true if one already exists.
 */
function admin_exists(PDO $pdo): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    return (int)$stmt->fetchColumn() > 0;
}

/* -------------------------------------------------------------------- */
/* "Remember me" persistent login (selector/validator pattern)           */
/* -------------------------------------------------------------------- */

function issue_remember_cookie(PDO $pdo, int $userId): void
{
    $selector = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(33));
    $hash = hash('sha256', $validator);
    $expires = time() + REMEMBER_DAYS * 86400;

    $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)')
        ->execute([$userId, $selector, $hash, $expires]);

    setcookie(REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires'  => $expires,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
}

function clear_remember_cookie(PDO $pdo): void
{
    if (!empty($_COOKIE[REMEMBER_COOKIE])) {
        $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
        if (!empty($parts[0])) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$parts[0]]);
        }
    }
    setcookie(REMEMBER_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
}

/**
 * If there's no active session but a valid remember-me cookie exists, logs
 * the user back in and rotates the token (one-time-use pattern, so a stolen
 * cookie value stops working the moment the real owner uses it again).
 */
function try_auto_login(PDO $pdo): void
{
    if (is_logged_in() || empty($_COOKIE[REMEMBER_COOKIE])) {
        return;
    }
    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE], 2);
    if (count($parts) !== 2) {
        return;
    }
    [$selector, $validator] = $parts;

    $stmt = $pdo->prepare('SELECT * FROM remember_tokens WHERE selector = ?');
    $stmt->execute([$selector]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['expires_at'] < time() || !hash_equals($row['validator_hash'], hash('sha256', $validator))) {
        return;
    }

    $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $userStmt->execute([$row['user_id']]);
    $user = $userStmt->fetch();
    $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$row['id']]);

    if (!$user || $user['status'] !== 'active') {
        return;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'    => (int)$user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'role'  => $user['role'],
    ];

    issue_remember_cookie($pdo, (int)$user['id']);
}

/* -------------------------------------------------------------------- */
/* Password reset (forgot password)                                      */
/* -------------------------------------------------------------------- */

function create_password_reset(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = time() + RESET_TOKEN_TTL;

    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)')
        ->execute([$userId, $hash, $expires]);

    return $token;
}

/**
 * Validates a reset token and, if valid, deletes it (single use) and
 * returns the associated user row. Returns null if invalid/expired.
 */
function consume_password_reset(PDO $pdo, string $token): ?array
{
    if ($token === '') {
        return null;
    }
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ?');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row || (int)$row['expires_at'] < time()) {
        return null;
    }

    $userStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $userStmt->execute([$row['user_id']]);
    $user = $userStmt->fetch();
    $pdo->prepare('DELETE FROM password_resets WHERE id = ?')->execute([$row['id']]);

    return $user ?: null;
}
