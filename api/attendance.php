<?php
/* GET  /api/attendance.php?since=ISO   -> list attendance
   POST /api/attendance.php  (JSON)     -> sign in, or update out_at (end duty) */
require __DIR__ . '/db.php';
require_key();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $since = $_GET['since'] ?? '1970-01-01 00:00:00';
  $stmt = db()->prepare('SELECT * FROM attendance WHERE created_at > ? ORDER BY in_at DESC LIMIT 500');
  $stmt->execute([$since]);
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  $d = body();
  $id = (string)($d['id'] ?? uniqid('at', true));

  // Upsert: first sign-in inserts the row; end-duty updates out_at.
  $stmt = db()->prepare(
    'INSERT INTO attendance
       (id, date, vehicle_id, vehicle_plate, driver_name, in_at, out_at, lat, lng, auto_closed, open_km, close_km)
     VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       out_at = VALUES(out_at),
       auto_closed = VALUES(auto_closed),
       open_km = COALESCE(open_km, VALUES(open_km)),
       close_km = VALUES(close_km)'
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
  ]);

  echo json_encode(['ok' => true, 'id' => $id]);
  exit;
}

fail('method not allowed', 405);
