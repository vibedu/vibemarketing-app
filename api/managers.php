<?php
/* Retired alongside manager_login.php — see the note there. */
http_response_code(410);
header('Content-Type: application/json');
echo json_encode(['error' => 'This endpoint was retired.']);
