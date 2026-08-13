-- Office logins can now be a Director or the Finance team.
-- The finance team sits elsewhere and files the GST returns, so they get their
-- own account rather than sharing the director's password.
--
-- Run once in phpMyAdmin with the app database selected. Safe to re-run.
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'Director';

-- Anyone already set up stays a Director.
UPDATE admins SET role = 'Director' WHERE role = '' OR role IS NULL;
