<?php
/* Retired. This was the field-manager phone login from before the app became
   office-only; nothing calls it any more, and its managers table held plain
   passwords. Kept as a stub (not deleted) because Hostinger's deploy never
   removes files — only an overwrite actually takes the old code off the
   server. Office logins live in api/admin_login.php. */
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['error' => 'This login was retired. Office users sign in on the app itself.']);
