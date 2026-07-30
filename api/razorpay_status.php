<?php
/* GET /api/razorpay_status.php -> is the Razorpay setup complete?
   Admin only, and it reports booleans only — never the keys themselves. */
require __DIR__ . '/db.php';
require __DIR__ . '/razorpay.php';
require_admin();

$webhook = defined('RAZORPAY_WEBHOOK_SECRET') && RAZORPAY_WEBHOOK_SECRET !== ''
        && strpos(RAZORPAY_WEBHOOK_SECRET, 'REPLACE') === false;

$table = false;
try { db()->query('SELECT 1 FROM payments LIMIT 1'); $table = true; } catch (Throwable $e) { $table = false; }

$mode = '';
if (rzp_ready()) $mode = strpos(RAZORPAY_KEY_ID, 'rzp_test') === 0 ? 'test' : 'live';

echo json_encode([
  'keys'    => rzp_ready(),   // Key ID + Secret present
  'webhook' => $webhook,      // webhook secret present
  'table'   => $table,        // payments table exists
  'mode'    => $mode,         // test | live | ''
  'ready'   => rzp_ready() && $webhook && $table,
]);
