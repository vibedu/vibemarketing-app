# Vibe Marketing app

Field + office app for Vibe Marketing (mobile hoardings, Coimbatore). Self-contained
`index.html` (React + Babel compiled in-browser, libraries self-hosted in `vendor/`),
with a small PHP + MySQL backend in `api/`. Data + photos live on Hostinger.

## Layout
- `index.html`, `manifest.json`, `sw.js`, `.htaccess`, `icon*` — the app, served at **app.vibemarketing.in**
- `vendor/` — self-hosted React / ReactDOM / Babel
- `api/` — PHP endpoints (photos, attendance, drivers, managers, login checks)
- `db/` — MySQL schema/migration scripts (run manually in phpMyAdmin; not deployed)

## Deploy
Pushing to `main` auto-deploys to Hostinger over FTP via `.github/workflows/deploy.yml`.
`api/config.php` (DB password) and `uploads/` live **only on the server** and are never
committed or overwritten.

**Database changes** (new tables/columns) are NOT automated — run the matching file in
`db/` in phpMyAdmin when a change needs them.
