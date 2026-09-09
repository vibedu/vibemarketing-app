<?php
/* POST /api/admin_reset_password.php  {email, setup_key, password}
   Lets whoever holds the server's ADMIN_SETUP_KEY set a new password for an
   existing admin account, without knowing the old one — the one recovery
   path for "the only Director forgot the password and there's no way in."

   Gated by the same secret that guards creating the very first admin:
   anyone who can read it already has real access to this server (it lives
   only in config.php, which is never in git). Every existing session for
   that admin is dropped on success, so a stolen old token stops working the
   moment the password changes. */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

// Same throttle shape as admin_login.php, kept in its own bucket so a wave of
// failed logins elsewhere can't also burn through the reset attempt budget.
$ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$lockDir = sys_get_temp_dir() . '/vibe_reset';
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

$d     = body();
$email = strtolower(trim((string) ($d['email'] ?? '')));
$key   = (string) ($d['setup_key'] ?? '');
$pass  = (string) ($d['password'] ?? '');

// Unlike admin_setup.php (where a missing key still allows the very first
// admin, since nothing exists yet to protect), a reset touches an account
// that already exists — the key is mandatory here, not optional.
$hasKey = defined('ADMIN_SETUP_KEY') && ADMIN_SETUP_KEY !== '' && ADMIN_SETUP_KEY !== 'CHANGE_ME_to_a_secret_only_you_know';
if (!$hasKey) fail('No setup key is configured on the server — set ADMIN_SETUP_KEY in api/config.php first.');

usleep(300000); // constant-ish delay to blunt brute force
if (!hash_equals(ADMIN_SETUP_KEY, $key)) {
  $att[] = $now;
  @file_put_contents($lockFile, json_encode($att), LOCK_EX);
  http_response_code(401);
  $left = max(0, 8 - count($att));
  echo json_encode(['error' => 'wrong setup key'] + ($left <= 3 ? ['attempts_left' => $left] : []));
  exit;
}

if ($email === '') fail('enter the account email');
if (strlen($pass) < 8) fail('password must be at least 8 characters');

$stmt = db()->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
$stmt->execute([$email]);
$row = $stmt->fetch();
if (!$row) fail('no admin account with that email', 404);

@unlink($lockFile); // success clears the counter

db()->prepare('UPDATE admins SET pass_hash = ? WHERE id = ?')
    ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['id']]);
// Force every device — including whoever still has the old token — to sign in again.
db()->prepare('DELETE FROM admin_sessions WHERE admin_id = ?')->execute([$row['id']]);

echo json_encode(['ok' => true]);
