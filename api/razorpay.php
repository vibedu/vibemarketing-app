<?php
/* Shared Razorpay helpers. The key secret stays on the server and is never
   sent to the app. */

function rzp_ready() {
  return defined('RAZORPAY_KEY_ID') && defined('RAZORPAY_KEY_SECRET')
      && RAZORPAY_KEY_ID !== '' && RAZORPAY_KEY_SECRET !== ''
      && strpos(RAZORPAY_KEY_ID, 'REPLACE') === false
      && strpos(RAZORPAY_KEY_SECRET, 'REPLACE') === false;
}

/* POST JSON to the Razorpay API. Returns [httpStatus, decodedBody]. */
function rzp_post($path, $payload) {
  $ch = curl_init('https://api.razorpay.com/v1' . $path);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_USERPWD        => RAZORPAY_KEY_ID . ':' . RAZORPAY_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_TIMEOUT        => 20,
  ]);
  $raw  = curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $err  = curl_error($ch);
  curl_close($ch);
  if ($raw === false) return [0, ['error' => ['description' => $err ?: 'network error']]];
  return [$code, json_decode($raw, true)];
}

/* Razorpay works in paise. */
function rzp_paise($rupees) { return (int) round(((float) $rupees) * 100); }
