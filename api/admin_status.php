<?php
/* GET /api/admin_status.php -> whether an admin exists yet, and whether setup is enabled.
   The app uses this to decide between the login screen and the first-time setup screen. */
require __DIR__ . '/db.php';
require_key();

$n = (int) db()->query('SELECT COUNT(*) AS c FROM admins')->fetch()['c'];
echo json_encode([
  'configured'    => $n > 0,
  'setup_enabled' => defined('ADMIN_SETUP_KEY') && ADMIN_SETUP_KEY !== '' && ADMIN_SETUP_KEY !== 'CHANGE_ME_to_a_secret_only_you_know',
]);
