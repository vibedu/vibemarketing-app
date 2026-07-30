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

/* Lock out after repeated failures. A 300ms delay alone still allowed roughly
   86,000 guesses a day; this caps a single IP at 8 tries per 15 minutes. */
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$lockDir = sys_get_temp_dir() . '/vibe_login';
@mkdir($lockDir, 0700, true);
$lockFile = $lockDir . '/' . sha1($ip) . '.json';
$now = time();
$att = [];
if (is_file($lockFile)) { $raw = @file_get_contents($lockFile); $att = $raw ? (json_decode($raw, true) ?: []) : []; }
$att = array_values(array_filter($att, function ($t) use ($now) { return $t > $now - 900; })); // last 15 min
if (count($att) >= 8) {
  http_response_code(429);
  header('Retry-After: 900');
  echo json_encode(['error' => 'Too many attempts. Try again in 15 minutes.']);
  exit;
}

usleep(300000); // constant-ish delay to blunt brute force
if ($email === '' || $pass === '') { http_response_code(401); echo json_encode(['error' => 'wrong email or password']); exit; }

$stmt = db()->prepare('SELECT id, email, pass_hash, name FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$row = $stmt->fetch();

if (!$row || !password_verify($pass, $row['pass_hash'])) {
  $att[] = $now;                                   // remember this failure
  @file_put_contents($lockFile, json_encode($att), LOCK_EX);
  http_response_code(401);
  $left = max(0, 8 - count($att));
  echo json_encode(['error' => 'wrong email or password'] + ($left <= 3 ? ['attempts_left' => $left] : []));
  exit;
}

@unlink($lockFile);                                 // success clears the counter
$token = bin2hex(random_bytes(32));
$exp   = gmdate('Y-m-d H:i:s', time() + 30 * 24 * 3600);
db()->prepare('INSERT INTO admin_sessions (token, admin_id, expires_at) VALUES (?,?,?)')
    ->execute([$token, $row['id'], $exp]);

// Opportunistic cleanup of expired sessions.
db()->query('DELETE FROM admin_sessions WHERE expires_at < UTC_TIMESTAMP()');

echo json_encode(['ok' => true, 'token' => $token, 'name' => $row['name'] !== '' ? $row['name'] : $row['email']]);
