-- Server copy of the office records (vendors, campaigns, payments, vehicles).
-- Until now these lived only in one phone's browser storage.
--
-- The row payload is JSON rather than columns on purpose: the app's record
-- shapes change often, and nothing here needs to be queried field-by-field
-- server-side. Sync only needs id, when it changed, and whether it was deleted.
--
-- Run once in phpMyAdmin with the app database selected. Safe to re-run.
CREATE TABLE IF NOT EXISTS records (
  kind       VARCHAR(20)  NOT NULL,          -- vendor | order | payment | vehicle
  id         VARCHAR(64)  NOT NULL,
  updated_at DATETIME     NOT NULL,          -- UTC, decides who wins
  deleted    TINYINT(1)   NOT NULL DEFAULT 0,-- tombstone, so deletes travel too
  data       LONGTEXT     NOT NULL,
  PRIMARY KEY (kind, id),
  INDEX idx_records_kind_updated (kind, updated_at),
  INDEX idx_records_updated (updated_at)
);
