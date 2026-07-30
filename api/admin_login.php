<?php
/* POST /api/admin_login.php  {email, password}
   Verifies the admin against the hashed password and returns a session token
   the app must send (as X-Admin-Token) to read the shared driver/attendance/proof data. */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

$d     = body();
$email = strtolower(trim((string) ($d['email'] ?? '')));
$pass  = (string) ($d['password'] ?? '');

usleep(300000); // constant-ish delay to blunt brute force
if ($email === '' || $pass === '') { http_response_code(401); echo json_encode(['error' => 'wrong email or password']); exit; }

$stmt = db()->prepare('SELECT id, email, pass_hash, name FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row || !password_verify($pass, $row['pass_hash'])) {
  http_response_code(401);
  echo json_encode(['error' => 'wrong email or password']);
  exit;
}

$token = bin2hex(random_bytes(32));
$exp   = gmdate('Y-m-d H:i:s', time() + 30 * 24 * 3600);
db()->prepare('INSERT INTO admin_sessions (token, admin_id, expires_at) VALUES (?,?,?)')
    ->execute([$token, $row['id'], $exp]);

// Opportunistic cleanup of expired sessions.
db()->query('DELETE FROM admin_sessions WHERE expires_at < UTC_TIMESTAMP()');

echo json_encode(['ok' => true, 'token' => $token, 'name' => $row['name'] !== '' ? $row['name'] : $row['email']]);
