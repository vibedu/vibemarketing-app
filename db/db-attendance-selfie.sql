-- Punch-in selfie for driver duty sign-in.
-- Adds two columns to the existing attendance table. Safe to run once.
ALTER TABLE attendance
  ADD COLUMN selfie_url  VARCHAR(255) NULL AFTER close_km,
  ADD COLUMN selfie_path VARCHAR(255) NULL AFTER selfie_url;
