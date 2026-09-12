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

/* Say plainly when a column hasn't been added yet, rather than dying with an
   empty 500 that reaches the app as "could not save driver". */
try { db()->query('SELECT wage, bank_account FROM drivers LIMIT 1'); }
catch (Throwable $e) {
  http_response_code(503);
  echo json_encode(['error' => 'Run db/db-drivers-financials.sql in phpMyAdmin first.']);
  exit;
}
try { db()->query('SELECT updated_at FROM drivers LIMIT 1'); }
catch (Throwable $e) {
  http_response_code(503);
  echo json_encode(['error' => 'Run db/db-drivers-updated-at.sql in phpMyAdmin first.']);
  exit;
}

if ($method === 'GET') {
  $stmt = db()->query('SELECT id, name, phone, emergency, dl, dl_expiry, active, photo_url, dl_front_url, dl_back_url,
                               wage, batta, joined, aadhaar, pan, address, bank_account, ifsc, bank_name, updated_at
                        FROM drivers WHERE active = 1 ORDER BY name');
  $rows = $stmt->fetchAll();
  // Same shape sync.php already hands the client for everything else, so one
  // "is the server's copy newer than mine" comparison works everywhere.
  foreach ($rows as &$r) { $r['updated_at'] = $r['updated_at'] ? str_replace(' ', 'T', $r['updated_at']) . 'Z' : null; }
  echo json_encode($rows);
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
    'INSERT INTO drivers (id, name, phone, pin, emergency, dl, dl_expiry, active, photo_url, dl_front_url, dl_back_url,
                           wage, batta, joined, aadhaar, pan, address, bank_account, ifsc, bank_name, updated_at)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP())
     ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), pin=VALUES(pin),
       emergency=VALUES(emergency), dl=VALUES(dl), dl_expiry=VALUES(dl_expiry), active=VALUES(active),
       photo_url=VALUES(photo_url), dl_front_url=VALUES(dl_front_url), dl_back_url=VALUES(dl_back_url),
       wage=VALUES(wage), batta=VALUES(batta), joined=VALUES(joined), aadhaar=VALUES(aadhaar),
       pan=VALUES(pan), address=VALUES(address), bank_account=VALUES(bank_account), ifsc=VALUES(ifsc), bank_name=VALUES(bank_name),
       updated_at=UTC_TIMESTAMP()'
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
    (int)($d['wage'] ?? 0),
    (int)($d['batta'] ?? 0),
    (isset($d['joined']) && $d['joined'] !== '') ? $d['joined'] : null,
    (string)($d['aadhaar'] ?? ''),
    (string)($d['pan'] ?? ''),
    (string)($d['address'] ?? ''),
    (string)($d['bank_account'] ?? ''),
    (string)($d['ifsc'] ?? ''),
    (string)($d['bank_name'] ?? ''),
  ]);
  echo json_encode(['ok' => true, 'photo_url' => $photo_url, 'dl_front_url' => $dl_front, 'dl_back_url' => $dl_back]);
  exit;
}

fail('method not allowed', 405);
