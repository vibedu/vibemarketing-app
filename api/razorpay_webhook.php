<?php
/* POST /api/razorpay_webhook.php
   Called by Razorpay when a payment link is paid. This endpoint is public
   (Razorpay has no app key), so the ONLY thing that authenticates it is the
   HMAC signature — an unverified request is rejected outright.

   Set up: Razorpay Dashboard → Settings → Webhooks → add
     URL:    https://app.vibemarketing.in/api/razorpay_webhook.php
     Secret: the same string as RAZORPAY_WEBHOOK_SECRET in api/config.php
     Events: payment_link.paid  (payment.captured is also accepted) */
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function wh_done($msg, $code = 200) { http_response_code($code); echo json_encode(['status' => $msg]); exit; }

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') wh_done('method not allowed', 405);

$raw = file_get_contents('php://input');
$sig = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

if (!defined('RAZORPAY_WEBHOOK_SECRET') || RAZORPAY_WEBHOOK_SECRET === ''
    || strpos(RAZORPAY_WEBHOOK_SECRET, 'REPLACE') !== false) {
  wh_done('webhook secret not configured', 503);
}
if (!is_string($sig) || $sig === '' || !hash_equals(hash_hmac('sha256', $raw, RAZORPAY_WEBHOOK_SECRET), $sig)) {
  wh_done('invalid signature', 401);
}

// Signature is good — only now do we touch the database.
require __DIR__ . '/db.php';

$d     = json_decode($raw, true);
$event = $d['event'] ?? '';
$ent   = $d['payload'] ?? [];

$linkId = $ent['payment_link']['entity']['id']           ?? '';
$refId  = $ent['payment_link']['entity']['reference_id'] ?? '';
$payId  = $ent['payment']['entity']['id']                ?? '';
$method = $ent['payment']['entity']['method']            ?? '';
$amount = isset($ent['payment']['entity']['amount']) ? (int) round($ent['payment']['entity']['amount'] / 100) : null;

if ($event !== 'payment_link.paid' && $event !== 'payment.captured') wh_done('ignored');
if ($linkId === '' && $refId === '') wh_done('nothing to match');

// Mark our row paid. Only rows still 'created' are updated, so a repeated
// webhook (Razorpay retries) cannot double-record a payment.
$sql = $refId !== ''
  ? 'UPDATE payments SET status = "paid", payment_id = ?, method = ?, paid_at = UTC_TIMESTAMP()'
    . ($amount !== null ? ', amount = ?' : '') . ' WHERE id = ? AND status <> "paid"'
  : 'UPDATE payments SET status = "paid", payment_id = ?, method = ?, paid_at = UTC_TIMESTAMP()'
    . ($amount !== null ? ', amount = ?' : '') . ' WHERE link_id = ? AND status <> "paid"';

$args = [$payId, $method];
if ($amount !== null) $args[] = $amount;
$args[] = $refId !== '' ? $refId : $linkId;

$stmt = db()->prepare($sql);
$stmt->execute($args);

wh_done($stmt->rowCount() ? 'recorded' : 'already recorded');
