<?php
/**
 * Records an action in the audit log for later review by the admin.
 * $actor is the current_user() array (or null for anonymous/system events).
 */
function log_action(PDO $pdo, ?array $actor, string $action, string $details = ''): void
{
    $pdo->prepare(
        'INSERT INTO audit_log (actor_id, actor_name, actor_role, action, details, created_at)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $actor['id'] ?? null,
        $actor['name'] ?? 'system',
        $actor['role'] ?? 'system',
        $action,
        $details,
        time(),
    ]);
}
