<?php
/* GET  /api/attendance.php?since=ISO   -> list attendance
   POST /api/attendance.php  (JSON)     -> sign in, or update out_at (end duty) */
require __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  require_admin(); // reading everyone's attendance/selfies is admin-only
  $since = $_GET['since'] ?? '1970-01-01 00:00:00';
  $stmt = db()->prepare('SELECT * FROM attendance WHERE created_at > ? ORDER BY in_at DESC LIMIT 500');
  $stmt->execute([$since]);
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  require_key(); // a driver's phone signs in / ends duty with the shared app key
  $d = body();
  $id = (string)($d['id'] ?? uniqid('at', true));

  // Save the punch-in selfie (a data:image/...;base64 string) as a file, if sent.
  $selfie_url = null; $selfie_path = null;
  $data = $d['selfie_data'] ?? '';
  if (is_string($data) && preg_match('#^data:image/(\w+);base64,#', $data, $m)) {
    $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
    if (!in_array($ext, ['jpg', 'png', 'webp'], true)) fail('bad image type');
    $bin = base64_decode(substr($data, strpos($data, ',') + 1));
    if ($bin === false) fail('bad image data');
    if (strlen($bin) > 8 * 1024 * 1024) fail('image too large');
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $name = 's_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (file_put_contents(UPLOAD_DIR . '/' . $name, $bin) === false) fail('cannot save selfie', 500);
    $selfie_path = $name;
    $selfie_url  = UPLOAD_URL . '/' . $name;
  }

  // Upsert: first sign-in inserts the row (with selfie); end-duty updates out_at.
  // COALESCE keeps the original selfie/open_km so the end-duty update never wipes them.
  $stmt = db()->prepare(
    'INSERT INTO attendance
       (id, date, vehicle_id, vehicle_plate, driver_name, in_at, out_at, lat, lng, auto_closed, open_km, close_km, selfie_url, selfie_path)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       out_at = VALUES(out_at),
       auto_closed = VALUES(auto_closed),
       open_km = COALESCE(open_km, VALUES(open_km)),
       close_km = VALUES(close_km),
       selfie_url = COALESCE(selfie_url, VALUES(selfie_url)),
       selfie_path = COALESCE(selfie_path, VALUES(selfie_path))'
  );
  $stmt->execute([
    $id,
    isset($d['date']) ? $d['date'] : date('Y-m-d'),
    (string)($d['vehicle_id'] ?? ''),
    (string)($d['vehicle_plate'] ?? ''),
    (string)($d['driver_name'] ?? ''),
    isset($d['in_at']) && $d['in_at'] !== '' ? gmdate('Y-m-d H:i:s', strtotime($d['in_at'])) : null,
    isset($d['out_at']) && $d['out_at'] !== '' ? gmdate('Y-m-d H:i:s', strtotime($d['out_at'])) : null,
    isset($d['lat']) && $d['lat'] !== '' ? (float)$d['lat'] : null,
    isset($d['lng']) && $d['lng'] !== '' ? (float)$d['lng'] : null,
    !empty($d['auto_closed']) ? 1 : 0,
    isset($d['open_km']) && $d['open_km'] !== '' ? (int)$d['open_km'] : null,
    isset($d['close_km']) && $d['close_km'] !== '' ? (int)$d['close_km'] : null,
    $selfie_url,
    $selfie_path,
  ]);

  echo json_encode(['ok' => true, 'id' => $id, 'selfie_url' => $selfie_url]);
  exit;
}

fail('method not allowed', 405);
