<?php
/* Retired one-off maintenance endpoint. Kept as an inert stub because Hostinger's
   Git deploy does not delete files removed from the repo — deleting this from git
   would leave the old, working version live on the server. Safe to delete by hand
   in File Manager. */
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'not found']);
