<?php
/* POST /api/admin_password.php  {current_password, new_password}
   Changes the signed-in admin's password. The current password is required as
   well as a valid session, so a stolen token on its own cannot lock the owner
   out. Every OTHER session is dropped afterwards, so anyone who had got in
   with the old password is signed out; the caller stays signed in. */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

$me = current_admin();
if (!$me) { http_response_code(401); echo json_encode(['error' => 'admin auth required']); exit; }

$d   = body();
$cur = (string) ($d['current_password'] ?? '');
$new = (string) ($d['new_password'] ?? '');

usleep(300000); // slow down guessing

$stmt = db()->prepare('SELECT pass_hash FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$me['id']]);
$row = $stmt->fetch();
if (!$row || !password_verify($cur, $row['pass_hash'])) {
  http_response_code(401);
  echo json_encode(['error' => 'current password is wrong']);
  exit;
}

if (strlen($new) < 6)  fail('new password must be at least 6 characters');
if ($new === $cur)     fail('new password must be different from the current one');

db()->prepare('UPDATE admins SET pass_hash = ? WHERE id = ?')
    ->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);

// Sign out every other device.
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
db()->prepare('DELETE FROM admin_sessions WHERE admin_id = ? AND token <> ?')
    ->execute([$me['id'], $token]);

echo json_encode(['ok' => true]);
