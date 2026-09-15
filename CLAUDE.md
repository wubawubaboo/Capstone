# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A barangay (Philippine local government unit) case/records management system: residents file reports, blotters, document/service requests, and emergency SOS alerts; barangay staff (secretary, VAWC desk, police, admin) handle intake, mediation scheduling, analytics, and account approvals. Laravel 12 backend, Inertia.js + React 19 frontend (no separate API/SPA split — pages are server-routed).

## Commands

Backend (PHP):
- `composer install` — install PHP deps
- `php artisan migrate` — run migrations (SQLite by default, `database/database.sqlite`)
- `php artisan db:seed` — seed via `DatabaseSeeder` (barangays, users, blotters, etc.)
- `composer test` / `php artisan test` — run PHPUnit suite (Feature + Unit); `php artisan test --filter=TestName` for a single test
- `vendor/bin/pint` — format PHP (Laravel Pint)

Frontend (JS):
- `npm install`
- `npm run dev` — Vite dev server (HMR)
- `npm run build` — production build

Full local stack (server + queue worker + log tail + Vite, all at once):
- `composer dev`

Note: `tests/Feature/ExampleTest.php` and `tests/Unit/ExampleTest.php` are the only tests present — there is no existing real test suite to pattern-match against.

## Architecture

**Role-based routing, not permission flags.** `routes/web.php` defines four separate route groups by prefix (`resident`, `secretary`, `vawc`, `admin`), each wrapped in an inline closure middleware that hard-checks `$request->user()->role !== '<role>'` and aborts 403. There's a fifth role, `barangay_police`, which currently has no dedicated route group (redirects to `resident.home`). When adding a route for a role, add it inside that role's existing group in `web.php`, not as a new top-level route — and mirror the corresponding `Layout` in `resources/js/Layouts/`.

`AuthController::redirectBasedOnRole()` is the single source of truth for where each role lands after login — update it when adding/renaming a role's landing page.

**Inertia page resolution.** `resources/js/app.jsx` globs `./Pages/**/*.jsx`. A controller's `Inertia::render('Secretary/BlotterManagement')` maps directly to `resources/js/Pages/Secretary/BlotterManagement.jsx` — filenames and folder names must match the string passed to `Inertia::render` exactly, including case. Pages under `Pages/<Role>/` are self-contained (no shared `Components/` directory exists yet); each role's pages typically wrap themselves in that role's `Layout` (e.g. `Layouts/SecretaryLayout.jsx`).

**Shared Inertia props.** `app/Http/Middleware/HandleInertiaRequests.php` shares `auth.user` (id, full_name, role, barangay) and flash `success`/`error` messages on every request — read these from Inertia props in pages rather than re-fetching user/session state.

**Domain model shape.** Core models: `User` (has `role`, `barangay_id`), `Barangay`, `BlotterRecord` (+ `MediationSchedule`, `VawcDetail` for VAWC-flagged cases), `Report` (resident-filed incidents, including SOS/emergency), `DocumentRequest` / `DocumentType`, `ServiceRequest`, `BarangayAsset` (resources secretaries assign to service requests), `Attachment` (polymorphic-ish file uploads tied to `uploaded_by`), `SystemLog` (audit trail, actor-tracked — check this when adding actions that should be auditable, see `AdminController::auditLogs`).

**Blotter vs VAWC split.** `BlotterController` (secretary) and `VawcController` (VAWC desk) both manage blotter records with near-identical route shapes (`blotters`, `blotters/create`, `case-history/{id}`, `mediation-calendar`, `cases/{id}/schedule-mediation`) but are separate controllers/pages for the two roles. A VAWC-flagged case gets a `VawcDetail` record (`BlotterController::storeVawcDetail`) and becomes visible to the VAWC role. When fixing bugs in blotter/mediation flow, check whether the equivalent code path exists in *both* controllers.

**Emergency SOS flow.** `ReportController::storeEmergency` creates a `Report`, fires `App\Events\SosTriggered` (`ShouldBroadcastNow`, broadcasts on the `emergency-alerts` channel via Reverb/Pusher), and dispatches `SendEmergencySmsJob` (queued) which uses `App\Services\PhilSmsService` to text on-duty officers. Broadcasting auth is in `routes/channels.php`. If SOS changes are needed, all three pieces (event, queued job, channel) usually need to move together.

**Account approval flow.** Resident self-registration (`/register`) creates unverified accounts (`is_verified = false`, with `id_photo_path`/`selfie_id_path` uploads); secretaries approve/reject via `secretary/account-requests` (`AuthController::approveAccount/rejectAccount`), which is distinct from staff registration (`/portal/secure-register`, `barangay_police` accounts created directly by secretaries via `AuthController::storePolice`).

**Services layer.** `app/Services/PhilSmsService.php` (SMS) and `app/Services/OpenStreetMapService.php` (geocoding/mapping — used alongside the `leaflet`/`react-leaflet` frontend deps) are injected into controllers/jobs via constructor DI, not called statically.
