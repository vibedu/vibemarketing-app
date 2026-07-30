<?php
/* POST /api/admin_setup.php  {setup_key, email, password, name}
   Creates the FIRST admin account. Only works while no admin exists yet AND the
   setup_key matches config.php's ADMIN_SETUP_KEY (known only to whoever has server access). */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

$d     = body();
$setup = (string) ($d['setup_key'] ?? '');
$email = strtolower(trim((string) ($d['email'] ?? '')));
$pass  = (string) ($d['password'] ?? '');
$name  = trim((string) ($d['name'] ?? ''));

usleep(300000); // slow down guessing

// First admin only: this endpoint stops working the moment an admin exists.
$n = (int) db()->query('SELECT COUNT(*) AS c FROM admins')->fetch()['c'];
if ($n > 0) fail('an admin is already set up', 409);

// If a real setup code is configured on the server, require it as an extra layer.
$hasKey = defined('ADMIN_SETUP_KEY') && ADMIN_SETUP_KEY !== '' && ADMIN_SETUP_KEY !== 'CHANGE_ME_to_a_secret_only_you_know';
if ($hasKey && !hash_equals(ADMIN_SETUP_KEY, $setup)) { http_response_code(401); echo json_encode(['error' => 'wrong setup code']); exit; }

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('enter a valid email address');
if (strlen($pass) < 6) fail('password must be at least 6 characters');

$hash = password_hash($pass, PASSWORD_DEFAULT);
db()->prepare('INSERT INTO admins (email, pass_hash, name) VALUES (?,?,?)')
    ->execute([$email, $hash, $name !== '' ? $name : $email]);

echo json_encode(['ok' => true]);
