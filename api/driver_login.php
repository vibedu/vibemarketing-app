<?php
/* POST /api/driver_login.php  {name, pin}
   Returns {ok:true, driver:{id,name}} only if the name + PIN match a
   registered (active) driver. PIN = the driver's custom pin, or the
   last 4 digits of their phone number if no custom pin was set. */
require __DIR__ . '/db.php';
require_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);

$d    = body();
$name = trim((string)($d['name'] ?? ''));
$pin  = preg_replace('/\D/', '', (string)($d['pin'] ?? ''));

if ($name === '' || strlen($pin) < 4) { echo json_encode(['ok' => false]); exit; }

$stmt = db()->prepare('SELECT id, name, phone, pin, photo_url FROM drivers WHERE active = 1 AND LOWER(TRIM(name)) = LOWER(?)');
$stmt->execute([$name]);

foreach ($stmt->fetchAll() as $row) {
  $expected = trim((string)$row['pin']);
  if ($expected === '') {
    $digits = preg_replace('/\D/', '', (string)$row['phone']);
    $expected = substr($digits, -4);
  }
  if ($expected !== '' && hash_equals($expected, $pin)) {
    echo json_encode(['ok' => true, 'driver' => ['id' => $row['id'], 'name' => $row['name'], 'photo_url' => $row['photo_url']]]);
    exit;
  }
}

echo json_encode(['ok' => false]);
