<?php
/* Shared helpers: DB connection, auth, JSON body, CORS. */
require __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
// App is same-origin (app.vibemarketing.in), but allow the headers we use.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Api-Key, X-Admin-Token');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }

function db() {
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO(
      'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
      DB_USER, DB_PASS,
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
  }
  return $pdo;
}

function require_key() {
  $key = $_SERVER['HTTP_X_API_KEY'] ?? ($_GET['key'] ?? '');
  if (!is_string($key) || !hash_equals(API_KEY, $key)) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
  }
}

function body() {
  $raw = file_get_contents('php://input');
  $d = json_decode($raw, true);
  return is_array($d) ? $d : [];
}

/* Returns the admin row for a valid, unexpired session token, or null.
   Extends the session (sliding 30-day expiry) on each valid use. */
function current_admin() {
  $t = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? '';
  if (!is_string($t) || strlen($t) < 32) return null;
  $stmt = db()->prepare(
    'SELECT a.id, a.email, a.name
       FROM admin_sessions s JOIN admins a ON a.id = s.admin_id
      WHERE s.token = ? AND s.expires_at > UTC_TIMESTAMP() LIMIT 1'
  );
  $stmt->execute([$t]);
  $row = $stmt->fetch();
  if (!$row) return null;
  db()->prepare('UPDATE admin_sessions SET expires_at = ? WHERE token = ?')
      ->execute([gmdate('Y-m-d H:i:s', time() + 30 * 24 * 3600), $t]);
  return $row;
}

/* Admin-only endpoints: baseline key AND a valid admin session token. */
function require_admin() {
  require_key();
  if (!current_admin()) {
    http_response_code(401);
    echo json_encode(['error' => 'admin auth required']);
    exit;
  }
}

function fail($msg, $code = 400) {
  http_response_code($code);
  echo json_encode(['error' => $msg]);
  exit;
}
