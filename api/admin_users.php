<?php
/* Office logins. A Director may add the finance team, see who has an account,
   and remove one. Passwords are never returned or logged — only the hash is
   stored, and a forgotten one is fixed by deleting the account and making it
   again.

   GET    /api/admin_users.php                          -> [{id,email,name,role,created_at}]
   POST   /api/admin_users.php {email,password,name,role}-> create
   POST   /api/admin_users.php {delete_id}               -> remove that account
*/
require __DIR__ . '/db.php';
require_director();

const ADMIN_ROLES = ['Director', 'Finance'];

/* Say plainly when the role column hasn't been added yet, rather than dying
   with an empty 500 that reaches the app as "could not load". */
try { db()->query('SELECT role FROM admins LIMIT 1'); }
catch (Throwable $e) {
  http_response_code(503);
  echo json_encode(['error' => 'Run db/db-admin-roles.sql in phpMyAdmin first.']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $rows = db()->query('SELECT id, email, name, role, created_at FROM admins ORDER BY id ASC')->fetchAll();
  foreach ($rows as &$r) { $r['id'] = (int) $r['id']; if ($r['role'] === '') $r['role'] = 'Director'; }
  echo json_encode($rows);
  exit;
}

if ($method === 'POST') {
  $d  = body();
  $me = current_admin();

  // ---- remove an account ----
  if (isset($d['delete_id'])) {
    $id = (int) $d['delete_id'];
    if ($id <= 0) fail('which account?');
    if ($id === (int) $me['id']) fail('you cannot delete the account you are signed in with');
    // Never leave the business with no way in.
    $dirs = (int) db()->query("SELECT COUNT(*) AS c FROM admins WHERE role = 'Director'")->fetch()['c'];
    $row  = db()->prepare('SELECT role FROM admins WHERE id = ? LIMIT 1');
    $row->execute([$id]);
    $victim = $row->fetch();
    if (!$victim) fail('no such account', 404);
    if ($victim['role'] === 'Director' && $dirs <= 1) fail('that is the only Director account left');
    db()->prepare('DELETE FROM admin_sessions WHERE admin_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM admins WHERE id = ?')->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
  }

  // ---- create an account ----
  $email = strtolower(trim((string) ($d['email'] ?? '')));
  $pass  = (string) ($d['password'] ?? '');
  $name  = trim((string) ($d['name'] ?? ''));
  $role  = (string) ($d['role'] ?? 'Finance');

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('enter a valid email address');
  if (strlen($pass) < 8) fail('password must be at least 8 characters');
  if (!in_array($role, ADMIN_ROLES, true)) fail('unknown role');

  $q = db()->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
  $q->execute([$email]);
  if ($q->fetch()) fail('that email already has an account', 409);

  db()->prepare('INSERT INTO admins (email, pass_hash, name, role) VALUES (?,?,?,?)')
      ->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $name !== '' ? $name : $email, $role]);

  echo json_encode(['ok' => true]);
  exit;
}

fail('method not allowed', 405);
