<?php
/* POST /api/admin_logout.php  -> invalidate the caller's admin session token. */
require __DIR__ . '/db.php';
require_key();

$t = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
if (is_string($t) && $t !== '') {
  db()->prepare('DELETE FROM admin_sessions WHERE token = ?')->execute([$t]);
}
echo json_encode(['ok' => true]);
