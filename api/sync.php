<?php
/* GET  /api/sync.php?since=ISO   -> records changed since that moment (server clock)
   POST /api/sync.php {records:[…]} -> upsert; each record wins only if it is newer
                                       than the stored copy (last-write-wins).

   Admin only. This is the office's commercial record — vendors, campaigns,
   payments and vehicles — which previously existed on a single device. */
require __DIR__ . '/db.php';
require_admin();

const SYNC_KINDS = ['vendor', 'order', 'payment', 'vehicle'];

/* Say plainly when the table hasn't been created yet, rather than dying with an
   empty 500 that reaches the app as "could not send changes". */
try { db()->query('SELECT 1 FROM records LIMIT 1'); }
catch (Throwable $e) {
  http_response_code(503);
  echo json_encode(['error' => 'The sync table does not exist yet — run db/db-sync.sql in phpMyAdmin.']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

/* Always report the server's clock so the client can page from it next time
   rather than trusting the device's own (which may be wrong). */
$now = gmdate('Y-m-d H:i:s');

if ($method === 'GET') {
  $since = $_GET['since'] ?? '';
  $since = $since !== '' ? gmdate('Y-m-d H:i:s', strtotime($since)) : '1970-01-01 00:00:00';

  $stmt = db()->prepare(
    'SELECT kind, id, updated_at, deleted, data FROM records
      WHERE updated_at > ? ORDER BY updated_at ASC LIMIT 2000'
  );
  $stmt->execute([$since]);

  $out = [];
  foreach ($stmt->fetchAll() as $r) {
    $out[] = [
      'kind'      => $r['kind'],
      'id'        => $r['id'],
      'updatedAt' => str_replace(' ', 'T', $r['updated_at']) . 'Z',
      'deleted'   => (bool) (int) $r['deleted'],
      'data'      => json_decode($r['data'], true),
    ];
  }
  echo json_encode(['ok' => true, 'now' => str_replace(' ', 'T', $now) . 'Z', 'records' => $out]);
  exit;
}

if ($method === 'POST') {
  $d    = body();
  $recs = isset($d['records']) && is_array($d['records']) ? $d['records'] : [];
  if (count($recs) > 1000) fail('too many records in one push');

  $stmt = db()->prepare(
    'INSERT INTO records (kind, id, updated_at, deleted, data)
     VALUES (?,?,?,?,?)
     ON DUPLICATE KEY UPDATE
       updated_at = IF(VALUES(updated_at) > updated_at, VALUES(updated_at), updated_at),
       deleted    = IF(VALUES(updated_at) > updated_at, VALUES(deleted),    deleted),
       data       = IF(VALUES(updated_at) > updated_at, VALUES(data),       data)'
  );

  $saved = 0; $skipped = 0;
  foreach ($recs as $r) {
    $kind = (string) ($r['kind'] ?? '');
    $id   = (string) ($r['id'] ?? '');
    if ($id === '' || !in_array($kind, SYNC_KINDS, true)) { $skipped++; continue; }

    $ts = isset($r['updatedAt']) && $r['updatedAt'] !== ''
      ? gmdate('Y-m-d H:i:s', strtotime($r['updatedAt']))
      : $now;
    // Never accept a timestamp from the future — a device with a wrong clock
    // would otherwise win every conflict from then on.
    if ($ts > $now) $ts = $now;

    $payload = json_encode($r['data'] ?? new stdClass());
    if ($payload === false || strlen($payload) > 400000) { $skipped++; continue; }

    $stmt->execute([$kind, $id, $ts, !empty($r['deleted']) ? 1 : 0, $payload]);
    $saved++;
  }

  echo json_encode(['ok' => true, 'now' => str_replace(' ', 'T', $now) . 'Z', 'saved' => $saved, 'skipped' => $skipped]);
  exit;
}

fail('method not allowed', 405);
