-- ============================================================
--  Vibe Marketing — Phase 1 (Hostinger MySQL)
--  Shared driver PHOTOS + ATTENDANCE with GPS location.
--  Run in hPanel -> Databases -> phpMyAdmin -> SQL tab -> Go
-- ============================================================

CREATE TABLE IF NOT EXISTS proofs (
  id            VARCHAR(64) PRIMARY KEY,
  created_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  at            DATETIME    NULL,
  kind          VARCHAR(40),
  vehicle_id    VARCHAR(40),
  vehicle_plate VARCHAR(40),
  driver_name   VARCHAR(120),
  order_id      VARCHAR(64),
  vendor_name   VARCHAR(160),
  lat           DOUBLE      NULL,
  lng           DOUBLE      NULL,
  place         VARCHAR(255),
  note          TEXT,
  photo_url     VARCHAR(255),
  photo_path    VARCHAR(160),
  INDEX idx_proofs_created (created_at),
  INDEX idx_proofs_vehicle (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS attendance (
  id            VARCHAR(64) PRIMARY KEY,
  created_at    TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
  date          DATE        NULL,
  vehicle_id    VARCHAR(40),
  vehicle_plate VARCHAR(40),
  driver_name   VARCHAR(120),
  in_at         DATETIME    NULL,
  out_at        DATETIME    NULL,
  lat           DOUBLE      NULL,
  lng           DOUBLE      NULL,
  auto_closed   TINYINT(1)  DEFAULT 0,
  INDEX idx_att_created (created_at),
  INDEX idx_att_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
