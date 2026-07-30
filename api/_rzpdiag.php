<?php
/* TEMPORARY diagnostic for the Razorpay 500. Reports booleans and error text
   only — never key values. Remove once the cause is found. */
ini_set('display_errors', '0');
error_reporting(E_ALL);

$steps = [];
$fatal = null;
register_shutdown_function(function () use (&$steps) {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['fatal' => $e['message'], 'file' => basename($e['file']), 'line' => $e['line'], 'steps' => $steps]);
  }
});

$steps[] = 'start php ' . PHP_VERSION;

require __DIR__ . '/config.php';
$steps[] = 'config loaded';

if (($_GET['confirm'] ?? '') !== 'rzpdiag') { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }

$out = [
  'php'            => PHP_VERSION,
  'curl_available' => function_exists('curl_init'),
  'razorpay_php'   => file_exists(__DIR__ . '/razorpay.php'),
  'defined' => [
    'RAZORPAY_KEY_ID'         => defined('RAZORPAY_KEY_ID'),
    'RAZORPAY_KEY_SECRET'     => defined('RAZORPAY_KEY_SECRET'),
    'RAZORPAY_WEBHOOK_SECRET' => defined('RAZORPAY_WEBHOOK_SECRET'),
  ],
  'types' => [
    'RAZORPAY_KEY_ID'     => defined('RAZORPAY_KEY_ID') ? gettype(RAZORPAY_KEY_ID) : null,
    'RAZORPAY_KEY_SECRET' => defined('RAZORPAY_KEY_SECRET') ? gettype(RAZORPAY_KEY_SECRET) : null,
  ],
  'key_id_prefix' => defined('RAZORPAY_KEY_ID') && is_string(RAZORPAY_KEY_ID) ? substr(RAZORPAY_KEY_ID, 0, 8) : null,
];

try { require __DIR__ . '/razorpay.php'; $steps[] = 'razorpay.php required'; }
catch (Throwable $e) { $out['razorpay_require_error'] = $e->getMessage(); }

try { $out['rzp_ready'] = function_exists('rzp_ready') ? rzp_ready() : 'function missing'; $steps[] = 'rzp_ready ok'; }
catch (Throwable $e) { $out['rzp_ready_error'] = $e->getMessage(); }

try {
  require __DIR__ . '/db.php';
  $steps[] = 'db.php required';
  db()->query('SELECT 1');
  $steps[] = 'db connect ok';
} catch (Throwable $e) { $out['db_error'] = $e->getMessage(); }

try { db()->query('SELECT 1 FROM payments LIMIT 1'); $out['payments_table'] = true; }
catch (Throwable $e) { $out['payments_table'] = false; $out['payments_error'] = substr($e->getMessage(), 0, 160); }

try { $out['admins_table'] = (bool) db()->query('SELECT 1 FROM admins LIMIT 1'); }
catch (Throwable $e) { $out['admins_table'] = false; }

try { $out['functions'] = ['require_admin' => function_exists('require_admin'), 'current_admin' => function_exists('current_admin')]; }
catch (Throwable $e) {}

$out['steps'] = $steps;
header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_PRETTY_PRINT);
