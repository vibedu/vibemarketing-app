-- ============================================================
--  Vibe Marketing — add opening/closing kilometres (Need 2)
--  Run in phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
ALTER TABLE attendance
  ADD COLUMN open_km  INT NULL,
  ADD COLUMN close_km INT NULL;
