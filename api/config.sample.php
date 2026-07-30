<?php
/* TEMPLATE ONLY. The real config.php lives only on the server (never in git).
   To set up a fresh server, copy this to config.php and fill in the DB values. */

define('DB_HOST', 'localhost');
define('DB_NAME', 'REPLACE_WITH_DB_NAME');   // e.g. u350687320_vibeapp
define('DB_USER', 'REPLACE_WITH_DB_USER');
define('DB_PASS', 'REPLACE_WITH_DB_PASSWORD');

// Shared key the app sends with every request (already baked into the app — NOT secret).
define('API_KEY', '4e6447365c464425131eddf1291d83a5ceff9d40c5cf29f9');

// One-time setup code for creating the FIRST admin account. Pick something only you
// know. It is only usable until an admin exists, then it stops working. Server-only.
define('ADMIN_SETUP_KEY', 'CHANGE_ME_to_a_secret_only_you_know');

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');

/* ---- Razorpay ----------------------------------------------------------
   From the Razorpay Dashboard → Settings → API Keys. The SECRET must never
   leave the server (this file is not in git). Start with TEST keys
   (rzp_test_...), confirm a payment end to end, then switch to LIVE.
   The webhook secret is the one you type when creating the webhook at
   Dashboard → Settings → Webhooks. */
define('RAZORPAY_KEY_ID',         'rzp_test_REPLACE_ME');
define('RAZORPAY_KEY_SECRET',     'REPLACE_WITH_RAZORPAY_KEY_SECRET');
define('RAZORPAY_WEBHOOK_SECRET', 'REPLACE_WITH_WEBHOOK_SECRET');
