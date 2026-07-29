<?php
/* GET  /api/managers.php          -> list active managers (no passwords)
   POST /api/managers.php  (JSON)  -> upsert a manager (active:0 removes)
        photo_data = data:image base64 (optional) */
require __DIR__ . '/db.php';
require_key();

function mg_save($dataUrl) {
  if (!is_string($dataUrl) || !preg_match('#^data:image/(\w+);base64,#', $dataUrl, $m)) return '';
  $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
  if (!in_array($ext, ['jpg', 'png', 'webp'], true)) return '';
  $bin = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1));
  if ($bin === false || strlen($bin) > 8 * 1024 * 1024) return '';
  if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
  $name = 'm_' . bin2hex(random_bytes(8)) . '.' . $ext;
  if (file_put_contents(UPLOAD_DIR . '/' . $name, $bin) === false) return '';
  return UPLOAD_URL . '/' . $name;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $stmt = db()->query('SELECT id, name, phone, address, userid, active, photo_url FROM managers WHERE active = 1 ORDER BY name');
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  $d  = body();
  $id = (string)($d['id'] ?? uniqid('mg', true));

  $cur = [];
  $q = db()->prepare('SELECT photo_url FROM managers WHERE id = ?');
  $q->execute([$id]);
  $cur = $q->fetch() ?: [];
  $photo_url = mg_save($d['photo_data'] ?? '') ?: ($cur['photo_url'] ?? '');

  $stmt = db()->prepare(
    'INSERT INTO managers (id, name, phone, address, userid, password, active, photo_url)
     VALUES (?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE name=VALUES(name), phone=VALUES(phone), address=VALUES(address),
       userid=VALUES(userid), password=VALUES(password), active=VALUES(active), photo_url=VALUES(photo_url)'
  );
  $stmt->execute([
    $id,
    (string)($d['name'] ?? ''),
    (string)($d['phone'] ?? ''),
    (string)($d['address'] ?? ''),
    (string)($d['userid'] ?? ''),
    (string)($d['password'] ?? ''),
    isset($d['active']) ? (int)!!$d['active'] : 1,
    $photo_url,
  ]);
  echo json_encode(['ok' => true, 'photo_url' => $photo_url]);
  exit;
}

fail('method not allowed', 405);
