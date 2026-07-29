-- Server-side admin authentication.
-- Run once in phpMyAdmin. Safe to re-run (IF NOT EXISTS).
CREATE TABLE IF NOT EXISTS admins (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  email      VARCHAR(190) NOT NULL UNIQUE,
  pass_hash  VARCHAR(255) NOT NULL,
  name       VARCHAR(120) DEFAULT '',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_sessions (
  token      CHAR(64) PRIMARY KEY,
  admin_id   INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  INDEX idx_admin_sessions_expires (expires_at)
);
