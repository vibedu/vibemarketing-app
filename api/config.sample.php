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
