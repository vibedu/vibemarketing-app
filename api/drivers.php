<?php
/* GET  /api/drivers.php          -> list active drivers (with photo URLs)
   POST /api/drivers.php  (JSON)  -> upsert a driver (active:0 removes)
        photo_data / dl_front_data / dl_back_data = data:image base64 (optional) */
require __DIR__ . '/db.php';
require_admin(); // the roster (read and write) is admin-only; drivers sign in via driver_login.php

function dr_save($dataUrl) {
  if (!is_string($dataUrl) || !preg_match('#^data:image/(\w+);base64,#', $dataUrl, $m)) return '';
  $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
  if (!in_array($ext, ['jpg', 'png', 'webp'], true)) return '';
  $bin = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
  if ($bin === false || strlen($bin) > 8 * 1024 * 1024) return '';
  if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
  $name = 'd_' . bin2hex(random_bytes(8)) . '.' . $ext;
  if (file_put_contents(UPLOAD_DIR . '/' . $name, $bin) === false) return '';
  return UPLOAD_URL . '/' . $name;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $stmt = db()->query('SELECT id, name, phone, emergency, dl, dl_expiry, active, photo_url, dl_front_url, dl_back_url FROM drivers WHERE active = 1 ORDER BY name');
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  $d  = body();
  $id = (string)($d['id'] ?? uniqid('dr', true));

  // preserve existing photo URLs when no new photo is sent
  $cur = [];
  $q = db()->prepare('SELECT photo_url, dl_front_url, dl_back_url FROM drivers WHERE id = ?');
  $q->execute([$id]);
  $cur = $q->fetch() ?: [];

  $photo_url = dr_save($d['photo_data']    ?? '') ?: ($cur['photo_url']    ?? '');
  $dl_front  = dr_save($d['dl_front_data'] ?? '') ?: ($cur['dl_front_url'] ?? '');
  $dl_back   = dr_save($d['dl_back_data']  ?? '') ?: ($cur['dl_back_url']  ?? '');

  $stmt = db()->prepare(
    'INSERT INTO drivers (id, name, phone, pin, emergency, dl, dl_expiry, active, photo_url, dl_front_url, dl_back_url)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), pin=VALUES(pin),
       emergency=VALUES(emergency), dl=VALUES(dl), dl_expiry=VALUES(dl_expiry), active=VALUES(active),
       photo_url=VALUES(photo_url), dl_front_url=VALUES(dl_front_url), dl_back_url=VALUES(dl_back_url)'
  );
  $stmt->execute([
    $id,
    (string)($d['name'] ?? ''),
    (string)($d['phone'] ?? ''),
    (string)($d['pin'] ?? ''),
    (string)($d['emergency'] ?? ''),
    (string)($d['dl'] ?? ''),
    (isset($d['dl_expiry']) && $d['dl_expiry'] !== '') ? $d['dl_expiry'] : null,
    isset($d['active']) ? (int)!!$d['active'] : 1,
    $photo_url, $dl_front, $dl_back,
  ]);
  echo json_encode(['ok' => true, 'photo_url' => $photo_url, 'dl_front_url' => $dl_front, 'dl_back_url' => $dl_back]);
  exit;
}

fail('method not allowed', 405);
