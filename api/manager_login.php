<?php
/* POST /api/manager_login.php  {userid, password}
   Returns {ok:true, manager:{id,name,photo_url}} only if a registered
   (active) manager matches the user id + password exactly. */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

$d   = body();
$uid = trim((string)($d['userid'] ?? ''));
$pw  = (string)($d['password'] ?? '');

if ($uid === '' || $pw === '') { echo json_encode(['ok' => false]); exit; }

$stmt = db()->prepare('SELECT id, name, password, photo_url FROM managers WHERE active = 1 AND userid = ? LIMIT 1');
$stmt->execute([$uid]);
$row = $stmt->fetch();

if ($row && hash_equals((string)$row['password'], $pw)) {
  echo json_encode(['ok' => true, 'manager' => ['id' => $row['id'], 'name' => $row['name'], 'photo_url' => $row['photo_url']]]);
  exit;
}

echo json_encode(['ok' => false]);
