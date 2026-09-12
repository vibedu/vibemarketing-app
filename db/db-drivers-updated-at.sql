-- ============================================================
--  Vibe Marketing — add drivers.updated_at
--  Needed so an edit to an existing driver (wage, bank details, etc.) on
--  one device actually reaches another device, instead of only a
--  brand-new driver ever showing up there.
--  Run in phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
ALTER TABLE drivers
  ADD COLUMN updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Backfill existing rows so they don't all look simultaneously "just edited".
UPDATE drivers SET updated_at = NOW() WHERE updated_at IS NULL;
