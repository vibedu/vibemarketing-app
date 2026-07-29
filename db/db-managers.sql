-- ============================================================
--  Vibe Marketing — managers table (view-only + campaigns role)
--  Run in phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
CREATE TABLE IF NOT EXISTS managers (
  id         VARCHAR(64) PRIMARY KEY,
  created_at TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  name       VARCHAR(120),
  phone      VARCHAR(20),
  address    VARCHAR(255),
  userid     VARCHAR(60),
  password   VARCHAR(120),
  photo_url  VARCHAR(255),
  active     TINYINT(1)  DEFAULT 1,
  INDEX idx_managers_userid (userid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
