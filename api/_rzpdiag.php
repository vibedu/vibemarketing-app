<?php
/* Retired diagnostic. Kept as an inert stub because the Git deploy does not
   remove files deleted from the repo. Safe to delete by hand in File Manager. */
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'not found']);
