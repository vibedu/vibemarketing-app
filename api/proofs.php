<?php
/* GET  /api/proofs.php?since=ISO&vehicle_id=veh2   -> list photos
   POST /api/proofs.php  (JSON with photo_data data-URL)  -> save photo */
require __DIR__ . '/db.php';
require_key();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $since = $_GET['since'] ?? '1970-01-01 00:00:00';
  $veh   = $_GET['vehicle_id'] ?? '';
  $sql = 'SELECT * FROM proofs WHERE created_at > ?';
  $params = [$since];
  if ($veh !== '') { $sql .= ' AND vehicle_id = ?'; $params[] = $veh; }
  $sql .= ' ORDER BY at DESC LIMIT 500';
  $stmt = db()->prepare($sql);
  $stmt->execute($params);
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  $d = body();

  // Save the photo (a data:image/...;base64 string) as a file.
  $photo_url = ''; $photo_path = '';
  $data = $d['photo_data'] ?? '';
  if (is_string($data) && preg_match('#^data:image/(\w+);base64,#', $data, $m)) {
    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
    if (!in_array($ext, ['jpg', 'png', 'webp'], true)) fail('bad image type');
    $bin = base64_decode(substr($data, strpos($data, ',') + 1));
    if ($bin === false) fail('bad image data');
    if (strlen($bin) > 8 * 1024 * 1024) fail('image too large');
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $name = 'p_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (file_put_contents(UPLOAD_DIR . '/' . $name, $bin) === false) fail('cannot save photo', 500);
    $photo_path = $name;
    $photo_url  = UPLOAD_URL . '/' . $name;
  }

  $stmt = db()->prepare(
    'INSERT INTO proofs
       (id, at, kind, vehicle_id, vehicle_plate, driver_name, order_id, vendor_name, lat, lng, place, note, photo_url, photo_path)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
  );
  $stmt->execute([
    (string)($d['id'] ?? uniqid('pr', true)),
    isset($d['at']) ? gmdate('Y-m-d H:i:s', strtotime($d['at'])) : gmdate('Y-m-d H:i:s'),
    (string)($d['kind'] ?? ''),
    (string)($d['vehicle_id'] ?? ''),
    (string)($d['vehicle_plate'] ?? ''),
    (string)($d['driver_name'] ?? ''),
    (string)($d['order_id'] ?? ''),
    (string)($d['vendor_name'] ?? ''),
    isset($d['lat']) && $d['lat'] !== '' ? (float)$d['lat'] : null,
    isset($d['lng']) && $d['lng'] !== '' ? (float)$d['lng'] : null,
    (string)($d['place'] ?? ''),
    (string)($d['note'] ?? ''),
    $photo_url,
    $photo_path,
  ]);

  echo json_encode(['ok' => true, 'photo_url' => $photo_url]);
  exit;
}

fail('method not allowed', 405);
