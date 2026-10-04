<?php
/**
 * Returns true if $action is currently allowed for the caller's IP, false if
 * they've hit the limit and should be blocked. This sits alongside (not
 * instead of) the per-account lockout in auth.php — it protects endpoints
 * like registration and "forgot password" that don't have an account to
 * lock yet, and adds a second layer on login.
 */
function rate_limit_check(PDO $pdo, string $action, int $maxAttempts, int $windowSeconds): bool
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $now = time();

    $stmt = $pdo->prepare('SELECT * FROM login_throttle WHERE ip_key = ? AND action = ?');
    $stmt->execute([$ip, $action]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->prepare('INSERT INTO login_throttle (ip_key, action, attempt_count, window_start) VALUES (?, ?, 1, ?)')
            ->execute([$ip, $action, $now]);
        return true;
    }

    if ($now - (int)$row['window_start'] > $windowSeconds) {
        $pdo->prepare('UPDATE login_throttle SET attempt_count = 1, window_start = ? WHERE id = ?')
            ->execute([$now, $row['id']]);
        return true;
    }

    if ((int)$row['attempt_count'] >= $maxAttempts) {
        return false;
    }

    $pdo->prepare('UPDATE login_throttle SET attempt_count = attempt_count + 1 WHERE id = ?')->execute([$row['id']]);
    return true;
}
