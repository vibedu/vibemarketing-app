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

  // Cancel at Razorpay too — marking it cancelled only here would leave the
  // customer holding a link they could still pay.
  $q = db()->prepare('SELECT link_id, status FROM payments WHERE id = ? LIMIT 1');
  $q->execute([$id]);
  $row = $q->fetch();
  if (!$row) fail('unknown payment link', 404);
  if ($row['status'] !== 'created') { echo json_encode(['ok' => true, 'note' => 'already ' . $row['status']]); exit; }

  $remote = null;
  if (!empty($row['link_id'])) {
    require_once __DIR__ . '/razorpay.php';
    if (rzp_ready()) { list($code, $res) = rzp_post('/payment_links/' . $row['link_id'] . '/cancel', []); $remote = $code; }
  }

  db()->prepare('UPDATE payments SET status = "cancelled" WHERE id = ? AND status = "created"')->execute([$id]);
  echo json_encode(['ok' => true, 'razorpay' => $remote]);
  exit;
}

fail('method not allowed', 405);
