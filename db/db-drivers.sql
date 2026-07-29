-- ============================================================
--  Vibe Marketing — drivers roster (for registered-driver login)
--  Run in hPanel -> Databases -> phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
CREATE TABLE IF NOT EXISTS drivers (
  id         VARCHAR(64) PRIMARY KEY,
  created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  name       VARCHAR(120),
  phone      VARCHAR(20),
  pin        VARCHAR(12),
  active     TINYINT(1)  DEFAULT 1,
  INDEX idx_drivers_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
