# Mister Babadook — Laravel + Livewire

Port of the static prototype (`../index.html`) into a real backend: Laravel 12 + Livewire 3,
no separate API/React app, no WebSocket server — `wire:poll` handles the "live updates" feel.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create the MySQL database and a dedicated app user (adjust password):

```sql
CREATE DATABASE mister_babadook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'babadook_app'@'localhost' IDENTIFIED BY 'your-password-here';
GRANT ALL PRIVILEGES ON mister_babadook.* TO 'babadook_app'@'localhost';
```

Fill in `DB_PASSWORD` in `.env` to match, then:

```bash
php artisan migrate --seed
php artisan serve
```

Visit `http://localhost:8000`. The seeder creates one admin account — email defaults to
`itsupport@fdcp.gov.ph` (override with `ADMIN_EMAIL`/`ADMIN_PASSWORD` env vars before seeding,
or just create real accounts via the in-app **Users** page after first login). No demo/employee
accounts are seeded — create those through **Users** as well.

SQLite also works for quick local testing — set `DB_CONNECTION=sqlite` and
`DB_DATABASE=database/database.sqlite` (create the empty file first). Not recommended beyond
local dev: SQLite serializes writes, so concurrent users can hit lock contention.

## What's here

- **Migrations** (`database/migrations/`): users, equipment, requests (the loan lifecycle),
  extensions, damage_reports, audit_logs, plus Laravel's built-in notifications table.
- **Models** (`app/Models/`): `User`, `Equipment`, `Request`, `Extension`, `DamageReport`, `AuditLog`.
- **Policies** (`app/Policies/`): admin-vs-employee gating for equipment CRUD and the request
  lifecycle (approve/reject/checkout/checkin/extension/damage).
- **Livewire components** (`app/Livewire/`): one per screen from the original prototype —
  Dashboard, Inventory, Requests, Loans, Reports, Audit (admin); Browse, MyRequests, History
  (employee); shared `NotificationBell` and `ManagesRequests` trait for the approve/reject/
  checkout/checkin/extension actions used by both Dashboard and Requests\Index.
- **CSV export** (`app/Http/Controllers/ExportController.php`): plain controller + streamed
  download, since file downloads don't fit Livewire's request/response model.
- **`App\Support\Ui`**: PHP port of the prototype's `js/constants.js` + `js/utils.js` —
  category icon SVGs, avatar colors, initials.

## Notes

- Auth is Laravel's session guard (no Sanctum/API tokens needed since there's no separate
  frontend app).
- Realtime is `wire:poll` (5s on requests/notifications, 10s on audit/my-requests) — no Reverb,
  no WebSocket server. Upgrading later is a per-component config change, not a rewrite.
- `public/css/styles.css` is copied from the prototype's `css/styles.css` — if you tweak the
  Babadook theme further, edit it in `laravel/public/css/styles.css` (or symlink it back to
  the prototype's copy if you want a single source of truth).
