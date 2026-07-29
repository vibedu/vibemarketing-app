<?php
/* TEMPORARY one-time migration — runs against the SAME database the app uses,
   so it can't land on the wrong DB. Gated by the API key + a confirm token.
   Remove immediately after use. */
require __DIR__ . '/db.php';
require_key();
if (($_GET['confirm'] ?? '') !== 'migrate-vibe-2026') { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }

$log = [];
$run = function ($sql, $label) use (&$log) {
  try { db()->exec($sql); $log[] = "OK — $label"; }
  catch (Throwable $e) { $log[] = "ERR — $label — " . $e->getMessage(); }
};

$cols = [];
try { $cols = db()->query('SHOW COLUMNS FROM attendance')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}
if (!in_array('selfie_url', $cols, true))  $run('ALTER TABLE attendance ADD COLUMN selfie_url VARCHAR(255) NULL AFTER close_km', 'add selfie_url');
if (!in_array('selfie_path', $cols, true)) $run('ALTER TABLE attendance ADD COLUMN selfie_path VARCHAR(255) NULL AFTER selfie_url', 'add selfie_path');

$run('CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) DEFAULT "",
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)', 'create admins');

$run('CREATE TABLE IF NOT EXISTS admin_sessions (
  token CHAR(64) PRIMARY KEY,
  admin_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_admin_sessions_expires (expires_at)
)', 'create admin_sessions');

// Remove the connectivity test rows created during verification.
$run("DELETE FROM attendance WHERE id LIKE '__probe%'", 'delete probe rows');

$tables = [];
try { $tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}
$acols = [];
try { $acols = db()->query('SHOW COLUMNS FROM attendance')->fetchAll(PDO::FETCH_COLUMN); } catch (Throwable $e) {}

echo json_encode(['done' => true, 'db' => DB_NAME, 'log' => $log, 'tables' => $tables, 'attendance_columns' => $acols], JSON_PRETTY_PRINT);
