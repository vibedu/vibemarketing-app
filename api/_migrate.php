<?php
/* TEMPORARY: remove connectivity-test rows. Deleted from the repo right after use. */
require __DIR__ . '/db.php';
require_key();
if (($_GET['confirm'] ?? '') !== 'cleanup-vibe-2026') { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }

$before = (int) db()->query('SELECT COUNT(*) c FROM attendance')->fetch()['c'];
db()->exec("DELETE FROM attendance WHERE id LIKE '\_\_probe%' OR id LIKE '\_\_final\_check\_\_' OR driver_name = 'Probe' OR driver_name = 'probe'");
$after = (int) db()->query('SELECT COUNT(*) c FROM attendance')->fetch()['c'];

$left = db()->query("SELECT id, driver_name FROM attendance WHERE id LIKE '\_\_%' OR driver_name IN ('probe','Probe')")->fetchAll();
echo json_encode(['ok' => true, 'rows_before' => $before, 'rows_after' => $after, 'removed' => $before - $after, 'test_rows_left' => $left], JSON_PRETTY_PRINT);
