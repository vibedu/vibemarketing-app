<?php
/* TEMPORARY diagnostic — remove after use. */
require __DIR__ . '/db.php';
require_key();
$out = ['db_name' => DB_NAME];
try { $out['tables'] = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN); }
catch (Throwable $e) { $out['tables_error'] = $e->getMessage(); }
try { $out['attendance_columns'] = db()->query('SHOW COLUMNS FROM attendance')->fetchAll(PDO::FETCH_COLUMN); }
catch (Throwable $e) { $out['attendance_error'] = $e->getMessage(); }
echo json_encode($out);
