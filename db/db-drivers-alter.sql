-- ============================================================
--  Vibe Marketing — add driver KYC + photo columns (Need 1)
--  Run in phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
ALTER TABLE drivers
  ADD COLUMN emergency    VARCHAR(20)  NULL,
  ADD COLUMN dl           VARCHAR(40)  NULL,
  ADD COLUMN dl_expiry    DATE         NULL,
  ADD COLUMN photo_url    VARCHAR(255) NULL,
  ADD COLUMN dl_front_url VARCHAR(255) NULL,
  ADD COLUMN dl_back_url  VARCHAR(255) NULL;
