<?php
/* Quick test: /api/ping.php?key=THE_API_KEY
   Confirms PHP works, the key matches, and the DB connects. */
require __DIR__ . '/db.php';
require_key();
try {
  $n = db()->query('SELECT COUNT(*) AS c FROM proofs')->fetch();
  echo json_encode(['ok' => true, 'db' => 'connected', 'proofs' => (int)$n['c']]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'db' => 'error', 'message' => $e->getMessage()]);
}
