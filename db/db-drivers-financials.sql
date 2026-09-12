-- ============================================================
--  Vibe Marketing — add driver wage/KYC/bank columns
--  Needed for: payroll totals to actually be shared across devices,
--  and the weekly bank-transfer file (needs account number + IFSC).
--  Run in phpMyAdmin -> your DB -> SQL -> Go
-- ============================================================
ALTER TABLE drivers
  ADD COLUMN wage         INT          NULL,
  ADD COLUMN batta        INT          NULL,
  ADD COLUMN joined       DATE         NULL,
  ADD COLUMN aadhaar      VARCHAR(20)  NULL,
  ADD COLUMN pan          VARCHAR(15)  NULL,
  ADD COLUMN address      VARCHAR(255) NULL,
  ADD COLUMN bank_account VARCHAR(30)  NULL,
  ADD COLUMN ifsc         VARCHAR(15)  NULL,
  ADD COLUMN bank_name    VARCHAR(80)  NULL;
