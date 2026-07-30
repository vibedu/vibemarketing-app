<?php
/* GET  /api/payments.php            -> payment links and their status (admin only)
   POST /api/payments.php {id}       -> cancel a link that hasn't been paid */
require __DIR__ . '/db.php';
require_admin();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
  $stmt = db()->query(
    'SELECT id, order_id, order_no, vendor_name, amount, kind, status,
            link_url, payment_id, method, created_at, paid_at
       FROM payments ORDER BY created_at DESC LIMIT 500'
  );
  echo json_encode($stmt->fetchAll());
  exit;
}

if ($method === 'POST') {
  $d  = body();
  $id = (string) ($d['id'] ?? '');
  if ($id === '') fail('missing id');
  db()->prepare('UPDATE payments SET status = "cancelled" WHERE id = ? AND status = "created"')->execute([$id]);
  echo json_encode(['ok' => true]);
  exit;
}

fail('method not allowed', 405);
