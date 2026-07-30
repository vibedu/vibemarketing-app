<?php
/* POST /api/razorpay_link.php
   {order_id, order_no, vendor_name, phone, email, amount, kind}
   Creates a Razorpay payment link for a campaign and returns the short URL
   to send the customer. Admin only — creating payment requests is not
   something the shared app key should be able to do. */
require __DIR__ . '/db.php';
require __DIR__ . '/razorpay.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('method not allowed', 405);
if (!rzp_ready()) fail('Razorpay is not set up on the server yet. Add the keys in api/config.php.', 503);

$d       = body();
$amount  = (int) round((float) ($d['amount'] ?? 0));
$orderId = (string) ($d['order_id'] ?? '');
$orderNo = (string) ($d['order_no'] ?? '');
$vendor  = trim((string) ($d['vendor_name'] ?? ''));
$phone   = preg_replace('/\D/', '', (string) ($d['phone'] ?? ''));
$email   = trim((string) ($d['email'] ?? ''));
$kind    = (string) ($d['kind'] ?? 'Advance');

if ($amount < 1) fail('enter an amount greater than zero');

$id = 'pl_' . bin2hex(random_bytes(8));

$customer = [];
if ($vendor !== '') $customer['name'] = $vendor;
if ($email  !== '') $customer['email'] = $email;
if ($phone  !== '') $customer['contact'] = strlen($phone) === 10 ? '+91' . $phone : '+' . ltrim($phone, '+');

$payload = [
  'amount'      => rzp_paise($amount),
  'currency'    => 'INR',
  'accept_partial' => false,
  'description' => trim($kind . ' — ' . ($orderNo !== '' ? $orderNo : 'Vibe Marketing')),
  'reference_id'=> $id,
  'notes'       => ['order_id' => $orderId, 'order_no' => $orderNo, 'kind' => $kind],
  // Razorpay can notify the customer directly; we also share the link ourselves.
  'notify'      => ['sms' => false, 'email' => $email !== ''],
  'reminder_enable' => true,
];
if ($customer) $payload['customer'] = $customer;

list($code, $res) = rzp_post('/payment_links', $payload);

if ($code < 200 || $code >= 300 || empty($res['short_url'])) {
  $msg = $res['error']['description'] ?? 'Razorpay rejected the request';
  http_response_code(502);
  echo json_encode(['error' => $msg]);
  exit;
}

$stmt = db()->prepare(
  'INSERT INTO payments (id, order_id, order_no, vendor_name, amount, kind, status, link_id, link_url)
   VALUES (?,?,?,?,?,?,?,?,?)'
);
$stmt->execute([$id, $orderId, $orderNo, $vendor, $amount, $kind, 'created',
                (string) $res['id'], (string) $res['short_url']]);

echo json_encode(['ok' => true, 'id' => $id, 'url' => $res['short_url'], 'amount' => $amount]);
