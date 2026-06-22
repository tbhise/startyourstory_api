# Backend Changelog

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

---

## 2026-06-22 — Error logs: store full raw error + full stack trace

Admins can now see the complete backend error (not a 1000-char slice) plus the stack
trace from the dashboard, instead of only a truncated message. Secrets stay redacted.

- **`database/migrations/2026_06_22_000001_widen_error_summary_to_text_on_error_logs.php`**
  (new) — Widen `error_logs.error_summary` from `VARCHAR(1000)` to `TEXT` so the full
  raw (secret-redacted) exception message is stored untruncated. `stack` was already TEXT.
- **`app/Services/ErrorLogRecorder.php`** — `record()` now captures and stores the full
  PHP stack trace into the previously-always-`null` `stack` column. Added `stackTrace()`
  (prepends `Class @ file:line`, then `getTraceAsString()`) and `redactSecretsKeepLines()`
  (redacts secret key=value pairs but preserves newlines so the trace stays readable).
  Raw message cap raised 1000 → `RAW_MAX` (10000); stack capped at `STACK_MAX` (15000) to
  stay within the TEXT byte limit. `recordLog()` (no exception object) stores no stack.
  The complete trace still also goes to `storage/logs/laravel.log`.

---

## 2026-06-22 — Firm upload validation + graceful "Post data is too large"

Hardening for the firm profile update endpoint (Task 1) and a friendlier 413 response.

- **`app/Http/Controllers/API/FirmController.php`** (`firm_profile_update`) — Added file
  validation to the existing `Validator::make`: `logo => nullable|image|max:5120` (5MB),
  `office_images => nullable|array|max:5`, `office_images.* => image|max:5120` (5MB each),
  with user-friendly messages. Returns the same `{status:false, message}` shape on failure.
  No change to the existing `address` rule or any storage logic; updates with no new files
  still work (rules are `nullable`).
- **`bootstrap/app.php`** — Added a `render()` hook for `PostTooLargeException`: API/JSON
  requests now get `{status:false, message:"Upload too large…"}` with HTTP 413 instead of a
  raw HTML error page (PHP rejects oversized bodies via `post_max_size` before the
  controller runs). Still recorded in `error_logs` by the existing `report()` hook.

### Task 5 (error logging) — verified, NO code change

`error_summary` already stores the actual (secret-redacted) exception message capped at
1000 chars: `ErrorLogRecorder::record()` → `rawMessage()` for uncaught exceptions, and the
`MessageLogged` listener (`AppServiceProvider`) → `recordLog()` for caught-and-logged
controller failures. `PostTooLargeException` is not in the SKIP list, so "The POST data is
too large." already lands in `error_summary`. The only deviation from the literal spec
(`substr($e->getMessage(),0,1000)`) is deliberate secret redaction, kept on purpose; full
file/line/stack remain in `storage/logs/laravel.log` by design. Confirmed the
`error_summary` width migration (`2026_06_18_000001_widen_error_summary_on_error_logs.php`)
is present. **Server config reminder:** raise PHP `post_max_size`/`upload_max_filesize`
(see DEPLOYMENT.txt) so legitimate multi-image uploads aren't rejected pre-controller.

---

## 2026-06-21 — Expose firm_type + verification_status in /getCompanyDetails

`FirmController@getCompanyDetails` response now includes `firm_type` (uncommented) and
`verification_status` (new). Additive only — no existing field changed. Powers the
firm-type label and Verified badge on the redesigned company detail page.

## 2026-06-21 — Expose firm verification_status in /getCompanies

### Why
The `/companies` listing needs a "Verified Firm" trust badge. The firm's
`firm_profiles.verification_status` (enum `pending|approved|rejected`) was
available on the query (`firm_profiles.*`) but not included in the response map.

### Modified: `app/Http/Controllers/API/FirmController.php`
- `getCompanies()` response map now includes
  `'verification_status' => $company->verification_status` (after `is_premium`).
- Additive, non-breaking — existing consumers ignore the new field. No filter,
  query, or pagination change; the listing is not restricted to verified firms.

### Rollback
- Remove the single `'verification_status' => ...` line from the map.

## 2026-06-20 — Blog image performance: cache headers + downscaling

### Why
PageSpeed flagged blog images (`/storage/blog-images/...`) with "Cache TTL: None"
(repeat downloads) and oversized images (300–400 KB shown in small cards).

### Issue 3 — Missing cache headers (P1)
Root cause: images are served statically by Apache from `public/storage/` with no
`Cache-Control`/`Expires`, so browsers re-download them every visit.

#### Modified: `public/.htaccess`
- Added `mod_headers` + `mod_expires` blocks setting
  `Cache-Control: public, max-age=31536000, immutable` (1 year) for
  webp/avif/jpg/png/gif/svg/ico. Safe because uploads get unique random
  filenames and are never mutated in place (a re-upload is a new file).

### Issue 4 — Oversized blog images (P1)
Root cause: `ImageHelper::optimizeToWebp()` converted uploads to WebP but never
**resized** them, so a multi-megapixel original stayed full-resolution and was
rendered into small listing cards.

#### Modified: `app/Helpers/ImageHelper.php`
- Added a `$maxWidth` param (default 1600px) and a private `downscale()` helper
  that scales oversized images down (never up) via GD `imagescale`, preserving
  aspect ratio + alpha. Backward compatible — only new uploads pass through;
  existing stored images are untouched; falls back to the original on any failure.
- RECOMMENDED (not implemented — needs schema/API/frontend changes): generate a
  dedicated small thumbnail (~600px) for listing cards and expose it as
  `featured_image_thumb_url`, keeping the full image for the detail page.

### Rollback Plan
- `public/.htaccess`: remove the added `mod_headers`/`mod_expires` blocks.
- `ImageHelper.php`: remove the `$maxWidth` param + `downscale()` method and the
  `$src = self::downscale(...)` call.

---

## 2026-06-20 — Gate student job feed behind "Show Companies To Students"

Jobs and companies are now hidden together for students. Previously `show_companies_to_students = false` only hid the Companies directory; the student job feed stayed visible. Now when the flag is off, students see no jobs.

### Modified: `app/Http/Controllers/API/FirmController.php`
- `getJobs`: after resolving the requesting user, if they are a **student** and the `show_companies_to_students` platform setting is off (same parsing as `AdminSettingsController::getPublicSettings`), returns an **empty paginated payload** (`jobs: [], total: 0`) before running the listing query. Server-side enforcement so the API can't leak jobs while the setting is off. Non-students (firms, public) are unaffected.

---

## 2026-06-20 — Admin Students/Firms: stat-card counts + firm filters

Adds aggregate stat endpoints for the admin directory pages and extends the firm listing with the same filters the students list already had. No changes to pagination, existing filters, actions, or response shapes for existing fields.

### Modified: `app/Http/Controllers/API/AdminController.php`
- **New `getStudentStats` / `getFirmStats`** — each returns `{ total, verified, profile_completed }` for the page's stat cards. Computed in a **single grouped aggregate query** (no N+1) over `users` (`role` = student/firm, `is_deleted = 0`); `verified` = `email_verified_at IS NOT NULL`, `profile_completed` = `users.profile_completed = 1`. Counts cast to int.
- **`getFirms` extended** (All Firms tab): search now also matches `fp.hr_name` (contact person) and `fp.frn`; added `email_verified` (verified|not_verified) and `profile_completion` (completed|incomplete) filters mirroring `getStudents`; select now also returns `fp.frn`, `fp.hr_name`, `u.profile_completed`, and `is_verified` (derived from `email_verified_at`). Existing fields unchanged.

### Modified: `routes/api.php`
- `GET /admin/students-stats` → `getStudentStats`; `GET /admin/firms-stats` → `getFirmStats` (distinct paths; no conflict with existing `/admin/students/{id}` or `/admin/firms`).

---

## 2026-06-20 — Re-engagement email campaign (Artisan command)

Adds a backend-only, manually-triggered tool to email users who never finished onboarding (profile incomplete). One command sweeps the entire user base in a single run, auto-detecting each user's segment (student / firm / creator × verified / unverified) and sending the matching copy + CTAs. Reuses the existing mail stack — shared Blade layout, `EmailLog` tracking, and the database queue. No frontend changes; no existing mail flow touched.

### Eligibility & segmentation (reuses existing columns)
- Eligible = `users.is_deleted = 0` AND `users.profile_completed = 0` AND a valid email (`FILTER_VALIDATE_EMAIL`).
- Type: `firm` if `role='firm'`; else `creator` if `student_profiles.looking_for='creator'`; else `student`.
- Verified: from `email_verified_at` (NULL ⇒ unverified, shows a Verify Email CTA).

### New: `app/Console/Commands/SendReEngagementEmails.php` (`mail:reengagement`)
- Single LEFT JOIN query (`users` ⨝ `student_profiles`). Options: `--type=`, `--verified=0|1`, `--dry-run`, `--limit=`, `--sleep=`, `--queue`; all default to "everything".
- Default send is **synchronous** (`Mail::to()->send()`) with live per-recipient `Sent`/`Failed` output and a pending→sent/failed `EmailLog` row per email; `--queue` routes through the existing `DispatchMailJob`. Sender identity resolved via `EmailSenderResolver` (marketing).
- Each send wrapped in try/catch (one failure never aborts the run). Prints `Total eligible users: N`, a per-segment + grand-total summary; returns non-zero if any send failed.

### New: `app/Mail/ReEngagementMail.php`
- Reusable Mailable implementing `HasEmailPurpose` → new `EmailPurpose::REENGAGEMENT`. Renders `emails.reengagement` with name, userType, verified flag, subject, and CTA URL map.

### New: `resources/views/emails/reengagement.blade.php`
- Extends `emails.layouts.app`; type-specific heading/lead/benefit copy with verified vs unverified variants. CTAs via the existing `emails.partials.cta-button` partial: **Complete Profile** (firm → `/firm-profile`, else `/profile`), **Verify Email** (unverified only → `/verify-email`), and a **Login** link (`/login`). Base from `config('app.frontend_url')`.

### Modified: `app/Enums/EmailPurpose.php`
- Added `case REENGAGEMENT = 'reengagement';` mapped to the `marketing` sender key (distinguishable in `email_logs`).

---

## 2026-06-19 — Resume Builder — Backend-managed templates (Parts 4–5)

Moves the 4 mPDF resume templates out of the hardcoded Blade `@switch` into a DB-managed, admin-editable system. PDF rendering now reads the active template from the DB, with a safe fallback to the static view so nothing breaks. Engine unchanged (mPDF, pure PHP) per the chosen architecture.

### New: `database/migrations/2026_06_19_000003_create_resume_templates_table.php`
- Creates `resume_templates` (`id`, `template_name`, `template_key` unique, `html_content` longText, `css_content` longText, `preview_image` nullable, `is_active` bool default true, timestamps). Guarded (`hasTable`).
- **Seeds the 4 existing templates** (classic/modern/executive/creative) — the exact mPDF HTML+CSS previously inline in `resources/views/resume/pdf.blade.php`, with two substitutions so admin-editable templates need no PHP helpers: responsibilities use precomputed `$x['lines']`; the Executive photo uses precomputed `$d['initials']`. Seed is skipped if the table already has rows (never clobbers admin edits).

### New: `app/Http/Controllers/API/ResumeTemplateController.php`
- Admin CRUD (query-builder, `{status,message,data}` shape): `index`, `store`, `update`, `toggleActive`, `uploadPreview` (multipart → `ImageHelper::optimizeToWebp` on the public disk, deletes the old file), `destroy` (also removes the preview file). `template_key` validated `^[a-z0-9_]+$` + unique. Auth via `AdminAuthMiddleware` on `/admin/*`.

### Modified: `app/Http/Controllers/API/ResumeController.php`
- New private `renderTemplateHtml($t, $d)`: renders the **active** `resume_templates` row via `Blade::render(<style>css</style> + html, ['d' => $d])`. Falls back to `view('resume.pdf')` when the table is missing/empty or the key has no active row → PDF generation never breaks.
- `downloadPdf` now calls `renderTemplateHtml` instead of rendering the static view directly. mPDF config unchanged.
- `normalizeResume` now also emits per-experience `lines` (split responsibilities) and top-level `initials`, so DB templates avoid custom Blade helpers. Removed the dead `showPhoto` key (Part 2).

### Modified: `routes/api.php`
- Added admin routes: `GET/POST /admin/resume-templates`, `POST /admin/resume-templates/{id}`, `POST /admin/resume-templates/{id}/toggle-active`, `POST /admin/resume-templates/{id}/preview`, `DELETE /admin/resume-templates/{id}`.

### Architecture note (Part 5)
- Per the chosen design, the **engine stays mPDF** (pure PHP) and the **live preview stays React**. Therefore: admin edits to a template's HTML/CSS change the **PDF output only**, not the on-screen React preview, and the PDF remains a close mPDF render (no flexbox/grid) rather than a pixel-identical browser render. Admin-authored templates are trusted Blade (admin-only) — same capability the inline templates already had.

### Verified
- `php -l` clean on all touched/new PHP. Migration applied via `--path` (the project DB predates the base migrations, so a full `migrate` is not runnable here). Confirmed all 4 seeded rows render through `Blade::render` with a representative payload (no Blade errors).

### Rollback Plan
- `php artisan migrate:rollback` the new migration (drops `resume_templates`); revert `downloadPdf` to `view('resume.pdf', …)->render()` and drop `renderTemplateHtml`; remove the admin routes + `ResumeTemplateController`. The static `resume/pdf.blade.php` is retained and remains the fallback, so reverting is non-destructive.

---

## 2026-06-19 — Resume Builder — UX cleanup (Parts 1–3)

Companion to the same-day frontend cleanup. No schema, route or contract changes.

### Modified: `app/Http/Controllers/API/ResumeController.php`
- `normalizeResume()` — dropped the dead `showPhoto` key from the normalized PDF payload. Photo is intrinsic to the template (Executive Sidebar only) and was never read by any Blade template, so this removes a no-op field rather than changing any output. `showCertifications` / `showAchievements` / `sectionOrder` are unchanged.

### Validation (Part 1 — no code change needed)
- Confirmed resume drafts are persisted in the existing `resumes` table with the preferred structure (`id`, `user_id`, `template_key`, `resume_data` JSON, `created_at`, `updated_at`; unique per `user_id`). The `resume_data` JSON already carries personal fields (flat), `summary`, `education`, `experience`, `skills`, `certifications`, `achievements`, plus `completion_percentage` and `is_draft`. Upsert-by-`user_id` in `saveResume()` is correct.

### Rollback Plan
- Re-add `'showPhoto' => (bool) ($d['showPhoto'] ?? true),` to `normalizeResume()`.

---

## 2026-06-18 — Admin "Login as User" (impersonation), read-only

Super admins can open a student/firm account read-only for debugging — no password, without disturbing the admin's own session or the user's real sessions.

### DB Changes — ⚠️ MUST BE APPLIED MANUALLY (or `php artisan migrate`)
- New `admin_impersonation_sessions` table (audit: admin_id, target_user_id, target_role, token, ip_address, login_time, logout_time). Migration `2026_06_18_000002`.
- `user_sessions`: added `is_impersonation TINYINT(1) DEFAULT 0` + `impersonated_by BIGINT NULL`. Migration `2026_06_18_000003` (idempotent). Normal logins leave both at defaults → existing auth untouched.
- SQL also appended to `db_changes.txt`. **Gate setup:** `UPDATE admin_users SET role='super_admin' WHERE email='…';` (no schema change — `role` already exists).

### How it works (no existing flow modified destructively)
- The two auth systems already use separate cookies (`auth_token` for users, `admin_token` for admins) and can coexist. Impersonation mints a **separate, short-lived (1h) `user_sessions` row** flagged `is_impersonation=1` and sets it as `auth_token`. It **never** writes `users.api_token` or touches `admin_token`, so the real user's sessions and the admin's panel session are both intact. Exit clears only `auth_token`.

### New: `app/Http/Controllers/API/AdminImpersonationController.php`
- `start($userId)` (POST `/admin/impersonate/{userId}`): super_admin-only; target must be a non-deleted student/firm; auto-ends any prior impersonation by this admin; inserts session + audit rows; logs `impersonation_started`; returns `auth_token` cookie + `{ redirect }`.
- `stop()` (POST `/admin/impersonate/stop`): stamps `logout_time`, deletes the impersonation session row, clears only `auth_token`; logs `impersonation_ended`. Admin returns to the panel with `admin_token` intact.

### New: `app/Http/Middleware/BlockImpersonationWrites.php`
- Registered globally on the `api` group (like `AdminAuthMiddleware`); **no-op unless `auth_token` is an active impersonation session**. For impersonation sessions it enforces read-only via a deny-list: all PUT/PATCH/DELETE blocked, plus a curated list of sensitive POST paths (apply, wallet/recharge, payments, profile/firm-profile update, password change, account deletion, downloads, messaging, subscriptions, creator-marketplace writes, free-content). POST-for-read endpoints keep working. Returns 403 `impersonation_read_only`.

### Modified: `app/Http/Controllers/API/AuthController.php`
- `me()`: now resolves the token via `user_sessions` first (mirrors `ApiAuthMiddleware`), then falls back to legacy `users.api_token` — required because impersonation tokens live only in `user_sessions`. **Backward-compatible**: normal tokens (present in both) resolve identically. Adds an `impersonation: { active, admin_id, admin_name } | null` field to the response.
- `logout()`: if the `auth_token` belongs to an impersonation session, stamps `admin_impersonation_sessions.logout_time` before clearing the cookie. Normal logout behaviour otherwise unchanged.

### Modified: `app/Services/AdminActivityLogger.php`
- Added `IMPERSONATION_STARTED` / `IMPERSONATION_ENDED` action constants (also surface in the existing Activity Logs screen).

### Modified: `routes/api.php`, `bootstrap/app.php`
- Two new `/admin/impersonate/*` routes (stop before `{userId}` so it isn't captured). `BlockImpersonationWrites` appended to the `api` group.

### Testing
- `php -l` clean on all changed/new PHP files.
- Pending live verification after migrations: super_admin starts impersonation of a student → `auth_token` set, `/me` returns `impersonation.active=true`, dashboards/reads work, a write (e.g. `/updateProfile`, `/jobs/{id}/apply`) returns 403; Exit clears `auth_token` and admin still has panel; non-super admin gets 403 on start; the admin's own `admin_token` and the user's real sessions are untouched throughout.

### Rollback Plan
- Remove the two routes + the `BlockImpersonationWrites` registration; delete `AdminImpersonationController` and the middleware.
- `AuthController::me()`: restore the direct `users.api_token` lookup and drop the `impersonation` field. `logout()`: remove the `admin_impersonation_sessions` update.
- `AdminActivityLogger`: remove the two constants.
- DB: `DROP TABLE admin_impersonation_sessions;` + drop the two `user_sessions` columns (or `php artisan migrate:rollback`).

---

## 2026-06-17 — Fix: email-verification redirect 404 (env() null under config:cache)

The email-verification link redirected to `https://<api-host>/email-verification-result?status=success` and returned **404**. `email-verification-result` is a **React SPA route**, not an API route. Root cause: `UserController::verify()` built the redirect with `env('FRONTEND_URL')`, which returns **null** once `php artisan config:cache` has run (env is not loaded at runtime after caching). With a null host, `redirect()->away('/email-verification-result?...')` resolved relative to the **API** host → 404.

### Modified: `config/app.php`
- Added `'frontend_url' => env('FRONTEND_URL', 'https://startyourstory.in')`. This key was already read via `config('app.frontend_url', ...)` in ~10 places (emails, digests, reminders, messages) but was **never defined**, so all of them silently fell back to the hardcoded production default. Now `FRONTEND_URL` is honoured everywhere and survives config caching.

### Modified: `app/Http/Controllers/API/UserController.php`
- `verify()` now reads `$frontendUrl = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/')` once and uses it for all four redirects (3× failed, 1× success), replacing the four `env('FRONTEND_URL')` calls. `rtrim` also prevents the `//email-verification-result` double-slash when `FRONTEND_URL` has a trailing slash.

**Deploy note:** set `FRONTEND_URL` correctly per environment (RC = the RC frontend host) and run `php artisan config:clear && php artisan config:cache`.

---

## 2026-06-17 — Error Logs: capture EVERY backend error (caught controller exceptions)

Closed the gap where the admin **Error Logs** page (`/admin/errors`) only showed *uncaught* backend exceptions. The `report()` hook in `bootstrap/app.php` records exceptions that bubble up, but the **143 `Log::error()` calls across 24 controllers** that catch their own exception and return `'Server error'` never reach that hook — so those failures were invisible in `error_logs` (only in `storage/logs/laravel.log`). Now **every** error-level (and above) application log is mirrored into `error_logs`. **Purely additive — no controller, route, schema, or existing logging behaviour was changed**; the full file log is untouched.

### Modified: `app/Providers/AppServiceProvider.php`
- `boot()` now also calls a new `configureErrorLogCapture()` which registers an `Illuminate\Log\Events\MessageLogged` listener. For each logged message it calls `ErrorLogRecorder::recordLog($event->level, $event->message, $event->context)`. This automatically captures all existing **and future** `Log::error/critical/alert/emergency` calls with **zero** edits to any controller.

### Modified: `app/Services/ErrorLogRecorder.php`
- Added `recordLog(string $level, string $message, array $context)`:
  - Only acts on `error|critical|alert|emergency` levels (ignores debug/info/notice/warning noise).
  - **Skips entries that carry `['exception' => $e]` in context** — those are uncaught exceptions already recorded by the `report()` hook, so nothing is **double-recorded**. (Verified no controller passes exception context; they all string-concat `$e->getMessage()`.)
  - Sanitizes the message (same SQL/binding stripping + secret redaction as exceptions) and stores `source = 'api'`, `status = 500`, `stack = null`.
- Refactored the row insert into a shared private `writeRow()` with a **re-entrancy guard** (`self::$writing`) so a logging failure can never recurse, and a reusable `sanitize()` (extracted from `safeMessage`, behaviour-preserving). `record(Throwable)` output is unchanged.
- Removed the now-unused `QueryException` import.

### Untouched (already complete)
- **Frontend** already logs every API error (axios interceptor in `services/api.ts`), unhandled JS errors and promise rejections (`__root.tsx`) → `POST /error-logs`, with a localStorage fallback. No frontend change was needed.
- `bootstrap/app.php` `report()` hook, `ErrorLogController` (store/index/stats/destroy), the `error_logs` schema, and the admin page all unchanged.

### DB Changes
None. Uses the existing `error_logs` table (incl. `error_summary`).

### Testing
- `php -l` clean on both files.
- `Log::error("…password=secret … (SQL: …)")` → row stored with `source=api`, `status=500`, password **redacted**, SQL tail **stripped**, `error_summary` ≤100 chars. Test row cleaned up.
- `Log::error("…", ['exception' => $e])` → **0 rows** (correctly left to the report() hook — no duplicate). Cleaned up.

### Rollback Plan
- Remove `configureErrorLogCapture()` + the `MessageLogged`/`Event`/`ErrorLogRecorder` imports from `AppServiceProvider`; delete `recordLog()` and revert `record()`/`safeMessage()` to inline the insert/sanitize (restore the `QueryException` import). No data/schema impact.

---

## 2026-06-17 — Fix: Manually-approved premium not appearing in Billing & Payments

After the firm_id fix, a manually-approved subscription still didn't show on the firm Billing page (API returned `premium: []`, `active_plan: "Free Plan"`, totals 0). Cause: `approvePremiumRequest()` set `status = 'active'` but never set `payment_status` (left at the `'pending'` default) or `amount` (`NULL`). The Billing controller filters `payment_status != 'pending'` for listing and `= 'paid'` for totals/active-plan, so manual subs were hidden. The online PhonePe flow already set `payment_status = 'paid'` + `amount`; manual approval did not.

### Modified: `app/Http/Controllers/API/AdminController.php` — `approvePremiumRequest()`
- Both the insert and update of `firm_subscriptions` now also set `payment_status = 'paid'`, `amount = $premiumRequest->amount`, `currency = 'INR'`, `payment_gateway = 'manual'`, `payment_method = 'manual'`. A manually-approved payment is genuinely paid, so it is now stored as such and becomes first-class for billing/reporting. No change to the activation/`is_premium` logic.

### Modified: `app/Http/Controllers/API/FirmBillingController.php` — `planMeta()`
- Added a mapping for the legacy plan value `'premium'` (manual approval normalises `premium-yearly` → `premium`) → "Premium Yearly Plan" / "12 Months", so the Billing row shows a proper plan name + duration instead of "Premium / —".

### Data repair (dev DB)
- `firm_subscriptions.id=5`: set `payment_status = 'paid'`, backfilled `amount = 9999.00` (from its approved `premium_requests` row), `currency = INR`, `payment_method = manual`.
- Verified via the live endpoint: `premium` now returns the row (`payment_status: paid`, amount 9999), `active_plan: "Premium Yearly Plan"`, `total_premium: 9999` — the table renders it.

### Rollback Plan
- Remove the added `payment_status/amount/currency/payment_gateway/payment_method` keys from the two `firm_subscriptions` writes in `approvePremiumRequest()`; remove the `'premium'` case in `FirmBillingController::planMeta()`.

## 2026-06-16 — Fix: Manual premium approval never activated premium (firm_id mismatch)

Admins could approve a firm's manual premium-payment request and see "approved", yet the firm never became premium. Root cause: `premium_requests.firm_id` stores the **users.id** (the firm payment page sends `firm_id: user.id`), but `approvePremiumRequest()` used it directly as **firm_profiles.id** for every activation write. Those writes therefore targeted the wrong/non-existent row:
- `firm_profiles->where('id', <user id>)->update(is_premium=1)` matched **0 rows** → the real firm kept `is_premium = 0`, so `AuthController::getUser` never reported premium.
- `firm_subscriptions` rows were inserted with `firm_id = <user id>`, orphaned from the real firm (and invisible to the Billing page, which keys on `firm_profiles.id`).
The online PhonePe flow was unaffected because it consistently uses `$firmProfile->id`.

### Modified: `app/Http/Controllers/API/AdminController.php` — `approvePremiumRequest()`
- Added a tolerant resolver: look up the real `firm_profiles` row by `user_id` first, then fall back to `id` (handles both legacy/user-id and correct firm-profile-id values). Returns 404 + rollback if no firm profile is found.
- All activation writes now key on the resolved `$firmProfileId`: the existing-subscription lookup, the `firm_subscriptions` insert `firm_id`, the `firm_profiles.is_premium` update, and `ReferralHelper::onFirmPremiumActivated()`.
- No change to `rejectPremiumRequest()` (it only sets request status) or to the online PhonePe flow.

### Data repair (dev DB)
- 3 previously-approved `premium_requests` (all for users.id 11 → firm_profiles.id 3) had left the firm non-premium with one orphaned `firm_subscriptions` row (`sub#5`, firm_id 11).
- Repaired: `firm_subscriptions.id=5` → `firm_id = 3`; `firm_profiles.id=3` → `is_premium = 1`. Verified the firm now resolves as premium via the `getUser` gate and the active subscription is visible to Billing.
- Referral payouts were intentionally **not** retro-triggered during repair (avoids creating unintended ₹2,000 payouts); future approvals fire it correctly via the resolved id.

### Testing
- `php -l` clean. Verified against the live DB: firm_profiles.id=3 `is_premium=1`, active `sub#5` (plan=premium, expires 2027) now keyed on firm_id=3; simulated `getUser` returns `is_premium=TRUE`.

### Rollback Plan
- Revert the resolver block + the four `$firmProfileId` references in `approvePremiumRequest()` back to `$premiumRequest->firm_id`. (Data repair is not auto-reversible; it corrects previously-broken rows.)

## 2026-06-16 — Feature: Backend exception summaries in error_logs

Backend exceptions now record a short, **safe** one-line summary into the `error_logs` table for quick admin visibility, while the COMPLETE exception + stack trace continues to be written to `storage/logs/laravel.log` exactly as before. The DB is the quick summary; the file log remains the source of full debugging detail.

### DB
- Added `error_summary VARCHAR(100) NULL` to `error_logs` (after `message`).
- Migration `2026_06_16_000002_add_error_summary_to_error_logs.php` (idempotent — guarded by `Schema::hasColumn`). Also added an idempotent `ALTER TABLE` to `db_changes.txt`. Applied to the dev DB.

### New: `app/Services/ErrorLogRecorder.php`
- `record(Throwable, ?Request)` — NON-THROWING. Builds a sanitized summary and inserts one `error_logs` row (`source = 'api'`, `stack = null` always).
- **Sanitization** (`safeMessage`): strips the `(Connection: …, SQL: …)` tail from `QueryException` (so **no SQL or bindings** are stored), redacts `password/secret/token/authorization/api_key/bearer/session/cookie/otp`-style `key=value` pairs, and collapses whitespace to a single line. `message` ≤ 1000 chars, `error_summary` ≤ 100 chars. Stack traces, bindings, passwords, tokens, secrets and session ids are never persisted.
- Status derived from the exception (`HttpExceptionInterface::getStatusCode()`, else 500). User context resolved best-effort from the `auth_token` cookie, mirroring `ErrorLogController@store`.
- Skips routine noise: `NotFoundHttpException`, `MethodNotAllowedHttpException`, `ValidationException`, `AuthenticationException`.

### Modified: `bootstrap/app.php`
- `withExceptions()` now registers `$exceptions->report(fn ($e) => ErrorLogRecorder::record($e))`. The callback returns void and does **not** call `stop()`, so Laravel's default reporter still logs the full exception to `laravel.log` — existing logging behavior is untouched.

### Modified: `app/Http/Controllers/API/ErrorLogController.php`
- `index()` search now also matches `error_summary` (no other changes; `get()` already returns the new column).

### Scope note
Laravel's `report()` hook fires for **unhandled** exceptions. The 29 controllers that already `try/catch` + `Log::error()` + return `'Server error'` keep their existing file-log behavior unchanged (per the "keep existing logging untouched" requirement) — they are not rewired.

### Testing
- `php -l` clean on all touched files; migration applied (`error_summary` present).
- Unit-checked `safeMessage` against the three spec examples → exact: `Attempt to read property "id" on null`, `Call to undefined method App\Models\User::profile()`, `SQLSTATE[42S22]: Unknown column "city_name"` (SQL/bindings stripped). Secret/token strings redacted.
- End-to-end `record()` of a `QueryException` with bindings → row stored with `source=api`, `status=500`, `stack=NULL`, correct summary, and **no** leaked SQL/email; then cleaned up.

### Rollback Plan
- Revert `bootstrap/app.php` `withExceptions` to empty + remove the import; delete `ErrorLogRecorder.php`; revert the `error_summary` clause in `ErrorLogController@index`; run the `db_changes.txt` rollback (`ALTER TABLE error_logs DROP COLUMN error_summary;`) or `php artisan migrate:rollback`.

## 2026-06-16 — Feature: Firm Billing & Payments (read-only reporting)

A single read-only endpoint backing the new firm Billing & Payments page. It introduces **no new tables, no wallet logic, and no writes** — it only READS a firm's own records from existing tables and never touches subscription activation, payment, payout, commission or settlement logic.

### New: `app/Http/Controllers/API/FirmBillingController.php`
- `index()` — `GET /firm/billing-payments` (auth + firm-verified). Resolves the firm from `auth_user`, then returns three datasets + summary:
  - **premium**: this firm's `firm_subscriptions` (excluding raw `pending`/abandoned checkouts; `manual_verification` surfaces as Pending). Includes plan name/duration, amount, normalised status, active flag, expiry, invoice number (`INV-PRM-#####`), payment reference, and firm name/email for the invoice.
  - **branch**: premium subscriptions of **branch accounts** under this firm — `firm_profiles.parent_frn = this firm's frn AND is_branch = 1` (only when the current account is a parent). Labeled by branch firm name/city. Invoice `INV-BRN-#####`.
  - **creator**: `creator_engagement_payments` for this firm joined to `creator_engagements → creator_projects` (project title) and `users` (creator name). Reads only the amount the firm paid — **no payout/commission/settlement**. Invoice `INV-CRE-#####`.
  - **summary**: active plan name (+ expiry) from the firm's active paid subscription, and per-category totals (sum of successful payments only).
- Statuses are normalised to the four the UI filters on: `paid | pending | failed | refunded`. Invoice numbers are derived deterministically from the source row id (no storage).
- Wrapped in try/catch; logs and returns a safe 500 on error so the page degrades gracefully.

### Modified: `routes/api.php`
- Registered `GET /firm/billing-payments` inside the existing `ApiAuthMiddleware + FirmVerifiedMiddleware` group (so only approved firms reach it), plus the controller import.

### DB Changes
None — no schema changes, no new tables. Reads existing `firm_subscriptions`, `firm_profiles`, `creator_engagement_payments`, `creator_engagements`, `creator_projects`, `users`.

### Testing
- `php -l` clean; `route:list` shows the route.
- Executed the controller against the dev DB for a real firm: `status:true`, premium row returned, correct `active_plan` ("Premium Yearly Plan") and totals. Branch and creator join queries execute cleanly (0 rows — no such test data in dev yet).

### Rollback Plan
- Remove the route + import from `routes/api.php` and delete `FirmBillingController.php`. No data migration to revert.

## 2026-06-16 — Fix: Creator profile never completes when experience_years = 0

Creators (and student creator opt-ins) with **0 years of experience** could never reach `profile_completed = 1`, blocking SYS Coin welcome/referral bonuses and keeping their profile flagged incomplete.

### Cause
`UserController::updateProfile()` gated completion on `!empty((int)$request->experience_years)` (pure-creator branch) and `!empty($request->experience_years)` (student creator opt-in branch). For a legitimate value of `0`, `empty(0)` is `true`, so the term was `false` and collapsed the whole `&&` chain. Reproduced from `laravel.log`: all creator fields present, `experience_years = 0`, `isProfileComplete` logged empty (false).

### Modified: `app/Http/Controllers/API/UserController.php` — `updateProfile()`
- Pure-creator branch (`looking_for === 'creator'`): `!empty((int)$request->experience_years)` → `is_numeric($request->experience_years)`.
- Student creator opt-in branch (`is_creator` true, `looking_for !== 'creator'`): `!empty($request->experience_years)` → `is_numeric($request->experience_years)`.
- `is_numeric` treats `0`/`"0"` and any numeric value as valid, while `null`/`""`/missing remain invalid — so the field is still required, just no longer non-zero. This also realigns the backend with the frontend, which already marked the section complete via `experience_years != null` ([profile.tsx:459](../start-your-story-ui/src/routes/profile.tsx#L459)).

### DB Changes
None.

### Testing
- `php -l` clean.

### Rollback Plan
- Restore `!empty((int)$request->experience_years)` and `!empty($request->experience_years)` in the two branches.

## 2026-06-16 — Fixes: free-content file URLs, admin resume access, optional job salary

Three fixes. No breaking changes; permissions/architecture preserved.

### Issue 1 — Free content deliverable/attachment URLs (frontend crash root cause)
- **Root cause:** `FreeContentController` built file links with `Storage::url($path)`, which returns a **root-relative** `/storage/...` path (confirmed via tinker: `Storage::url('free-content-deliverables/x.jpg')` → `/storage/free-content-deliverables/x.jpg`). The frontend `<a href>` then resolved it against the **frontend** origin (`https://rc.startyourstory.in/storage/...jpg`), hitting the SPA catch-all and throwing TanStack Router "Invariant failed". The working paid-engagement flow uses `asset('storage/'.$path)` (absolute, API-domain).
- **Fix — `app/Http/Controllers/API/FreeContentController.php`:** deliverable `file_url` (3 spots: firm list, admin list, admin-upload response) and firm-request `attachments[].path` (firm + admin list) now use `asset('storage/'.ltrim($path,'/'))` — absolute API-domain URLs that serve the actual file. Null-safe (returns `null` when no path). Response shape unchanged (`attachments` stays `{name, path}`).

### Issue 2 — Admin cannot view/download student resume
- **Root cause:** the streaming `downloadFile` endpoint that reads from `storage_path('app/public/...')` is locked behind `FirmVerifiedMiddleware` (firm-only). Admin instead opened a direct public `/storage/resumes/...` link, which depends on the `public/storage` symlink and diverges from the firm path — failing when the symlink/path isn't served.
- **Fix — new admin-token-guarded endpoint:** `AdminController@downloadStudentFile` + `GET /admin/students/{id}/file?type=resume|marksheet[&download=1]`. Validated via `adminFromRequest` (admin_token cookie), streams from `storage_path('app/public/'.$path)` (no symlink dependency, like the firm flow). Inline view by default; `?download=1` forces download. Students/firms are untouched.
- **Security verified:** no token → 401; invalid type → 422; missing student/file → 404; valid admin → 200 (inline `application/pdf` view + `Content-Disposition: attachment` download).

### Issue 3 — Job salary now optional
- **Root cause:** `'salary' => 'required|string|max:255'` in `FirmController@createJob` and `@updateJob`.
- **Fix — `app/Http/Controllers/API/FirmController.php`:** validation → `'salary' => 'nullable|string|max:255'` (both methods); storage → `'salary' => $request->input('salary') ?: null` (stores NULL when blank, for consistent "Not Disclosed" display).
- **DB:** none required — `jobs.salary` is already `varchar(100) NULL` (verified). Existing salary values are preserved on edit.

### Files Modified
- `app/Http/Controllers/API/FreeContentController.php`, `app/Http/Controllers/API/AdminController.php`, `app/Http/Controllers/API/FirmController.php`, `routes/api.php`.

### APIs Modified / Added
- `GET /admin/students/{id}/file` (new, admin_token). `getMyRequests`/`getAdminRequests`/`adminUploadDeliverable` (free content) now return absolute file URLs. `createJob`/`updateJob` salary optional.

---

## 2026-06-15 — Fix: AdminAnalytics@dashboard crash on non-existent `applications.created_at`

Bugfix. The new admin dashboard-stats endpoint threw `SQLSTATE[42S22] Unknown column 'created_at'` because the `applications` table has no `created_at` column — it tracks creation via `applied_at`.

### Files Modified
- `app/Http/Controllers/API/AdminAnalyticsController.php` — in `dashboard()`:
  - "Applications this month" count now filters on `applied_at` (was `created_at`).
  - Recent-applications query now orders by `a.applied_at` and selects `a.applied_at as created_at` (output shape/JSON key unchanged).

### Notes
- Only the `applications` table was affected; `firm_subscriptions`, `wallet_recharges`, `creator_payouts`, `firm_profiles`, `referral_payouts` all have real `created_at` columns and were left as-is.
- No schema/DB changes. Verified live against the dev DB (count + recent list both return rows).

---

## 2026-06-15 — Moderation: "Incorrect Information" report workflow (admin-reviewed)

Controlled, abuse-resistant workflow for firms to flag incorrect student-profile info. **No automatic penalties/suspensions/hiding/ranking** — every action is admin-driven.

### Database (see db_changes.txt — RUN before deploy)
- `reported_profiles`: `status` ENUM extended with `awaiting_student`, `warning_issued`; new columns `reported_field`, `description`, `evidence_path`, `admin_remarks`.

### Files Modified
- `app/Http/Controllers/API/UserController.php` — `reportStudentProfile` now accepts `reported_field`, `description` (required when reason=`incorrect_information`), and optional `evidence` (base64 image/PDF → `storage/reported-evidence`). Duplicate guard relaxed to *open report, same firm+student+reason+field* (different firms/fields tracked independently). **Now creates an admin notification** ("Incorrect Information Reported") via `AdminNotificationService`.
- `app/Services/Notifications/AdminNotificationService.php` — added `TYPE_PROFILE_REPORT`.
- `app/Http/Controllers/API/AdminController.php`
  - `getReportedProfiles` — returns `reporting_firm`, `reported_field`, `description`, `current_value` (live profile value for the flagged field), `evidence_url`, `admin_remarks`; status counts include the new states.
  - `updateReportStatus` — accepts `dismissed | awaiting_student | warning_issued` (+ legacy `reviewed`/`pending`); writes admin reason to `admin_remarks` (not the reporter's `remarks`); **fires student notifications**: `awaiting_student` → "Profile Review Requested", `warning_issued` → "Warning Issued". Profile stays active in all cases.

### Status flow
`pending` → `dismissed` | `awaiting_student` | `warning_issued` (any non-pending → reopen to `pending`).

---

## 2026-06-15 — Admin: revenue analytics, dashboard stats, moderation, blog WebP

Additive admin features. **No existing business logic, payment calculations, or blog content structure changed.** No new tables/columns (all referenced tables already exist).

### Files Added
- `app/Http/Controllers/API/AdminAnalyticsController.php` — read-only, admin-token guarded.
  - `revenue` (`GET /admin/revenue-analytics?period=&from=&to=`) — metrics (total / premium / wallet / creator commissions / referral payouts / net) + trend series (revenue, premium, wallet). Period keywords `today|week|month|year|custom`; custom uses `from`/`to`. Trend buckets auto-pick hour/day/month granularity and fill gaps with 0. Sources: `firm_subscriptions.amount` (status=active), `wallet_recharges.amount` (status=approved), `creator_payouts.commission_amount`, `referral_payouts.reward_amount` (status approved/paid). Net = (premium+wallet+commission) − referral payouts.
  - `dashboard` (`GET /admin/dashboard-stats`) — KPI rows (total students, total firms, applications this month, revenue this month, premium firms, wallet recharges this month, pending verifications, unread notifications) + recent activity (firm registrations, premium purchases, applications, wallet recharges).
- `app/Helpers/ImageHelper.php` — `optimizeToWebp(UploadedFile, $dir, $disk, $quality)`. Pure PHP **GD** (no new composer deps). Reads jpg/jpeg/png/webp, preserves PNG/WebP alpha, re-encodes to WebP at quality **82**. **Graceful fallback**: if GD/WebP is unavailable or the source is unreadable, it stores the original file untouched.

### Files Modified
- `app/Http/Controllers/API/AdminController.php` — added moderation endpoints `getReportedProfiles` (list + status counts; joins reporter/student users) and `updateReportStatus` (validated `pending|reviewed|dismissed`; stamps `reviewed_by`/`reviewed_at`). Uses existing `reported_profiles` table.
- `app/Http/Controllers/API/AdminBlogController.php` — `createBlog`/`updateBlog` now route `featured_image` through `ImageHelper::optimizeToWebp(...)` (stored as `.webp`). Upload cap raised 4 MB → **5 MB**; mime allow-list unchanged (jpg/jpeg/png/webp — svg/gif/bmp/tiff/heic still rejected). **Existing blog images are untouched** (conversion applies to new uploads only).
- `routes/api.php` — added `POST /admin/reported-profiles`, `POST /admin/reported-profiles/{id}/status`, `GET /admin/revenue-analytics`, `GET /admin/dashboard-stats`.

### Database Changes
None. `reported_profiles` already exists (status ENUM pending/reviewed/dismissed, reviewed_by, reviewed_at). All revenue columns (`firm_subscriptions.amount`, `wallet_recharges.amount`, `creator_payouts.commission_amount`, `referral_payouts.reward_amount`) already present.

### Notes
- Server must have the PHP **GD** extension with WebP support for conversion; without it uploads still succeed (original stored).

---

## 2026-06-14 — Platform Settings (dynamic business configuration)

Centralized, cached, admin-editable business values replacing hardcoded constants. **Generic** — future settings drop in with no code. Existing key/value `platform_settings` table + `AdminSettingsController` left **untouched** (this is a separate `system_settings` table).

### Files Added
- `database/migrations/2026_06_14_000003_create_system_settings_table.php` — `system_settings` (`setting_key` unique, `setting_value`, `setting_type`, `title`, `description`, `category`, `is_editable`, timestamps) + `system_setting_audits` (audit log; no prior activity-log infra existed).
- `database/seeders/SystemSettingsSeeder.php` — idempotent; seeds the 6 settings (never clobbers an admin-edited value on re-seed).
- `app/Models/SystemSetting.php`.
- `app/Services/SystemSettingService.php` — **cache layer + typed getters**. `get($key,$default)` uses `Cache::rememberForever` and casts by `setting_type`; `set($key,$value,$admin)` updates, writes an **audit row**, and **busts the cache**. Getters: `getStudentReferralReward()`, `getFirmPremiumPurchaseReward()`, `getWelcomeBonusCoins()`, `getApplicationFeeAmount()`, `getFreeApplicationsCount()`, `getMinimumWalletRecharge()` — each falls back to a safe default if the row is missing.
- `app/Http/Controllers/API/AdminSystemSettingController.php` — `index` (grouped by category), `update` (type-aware validation: numeric `min:0`, no negatives, zero allowed; blocks `is_editable=0`), `audit`. Admin-token guarded.

### Files Modified (refactor — behaviour preserved, amounts now dynamic)
- `app/Helpers/SysCoinHelper.php` — student referral bonus now grants `getStudentReferralReward()` (10 → **50** via seed); welcome bonus grants `getWelcomeBonusCoins()`. Constants kept as fallbacks (corrected to 50/100).
- `app/Helpers/ReferralHelper.php` — firm premium payout `reward_amount` now `getFirmPremiumPurchaseReward()`. **Trigger unchanged** — granted only on premium *activation* (admin approve / PhonePe verify+webhook), **never on registration**; idempotency/unique-payout preserved.
- `app/Http/Controllers/API/ReferralController.php` — referral dashboard `reward_label` built from the service (no more hardcoded `'₹2,000'` / `'+10 SYS Coins'`).
- `routes/api.php` — `GET /admin/system-settings`, `GET /admin/system-settings/audit`, `POST /admin/system-settings/{key}`.

### Scope note
`application_fee_amount`, `free_applications_count`, `minimum_wallet_recharge` are seeded + exposed via getters but left **seed-only** (their existing code paths — the recently-hardened wallet/apply flow and the existing `platform_settings.free_applications_limit` — are intentionally untouched). Future wiring is a drop-in.

### Database Changes
New `system_settings` + `system_setting_audits` tables (see `db_changes.txt`). Apply: migrate `--path` then `db:seed --class=SystemSettingsSeeder`.

### Rollback
Drop both tables; revert the helper/controller getters to the prior constants/literals. No impact on existing flows (getters fall back to the same defaults).

---

## 2026-06-14 — Admin Notifications Phase 2–4: Bell APIs + FCM Push (backend)

Builds on Phase 1. **No existing notification-creation logic or approval workflow was changed** — the only edit to existing logic is an additive, non-throwing push fan-out inside the Phase-1 service.

### Files Added
- `database/migrations/2026_06_14_000002_create_admin_fcm_tokens_table.php` — `admin_fcm_tokens` (`admin_user_id`, unique `token`, `device_info`, `last_active_at`, timestamps). One row per device → multiple devices per admin.
- `app/Models/AdminFcmToken.php` — Eloquent model.
- `app/Services/Notifications/FcmService.php` — FCM **HTTP v1** sender. Mints an OAuth2 access token from the service-account key using native `openssl` (RS256 JWT → token endpoint, cached) — **no extra Composer dependency**. `sendToAllAdmins()` loops admin tokens, sends `notification` + `data` + `webpush.fcm_options.link` (absolute admin URL), prunes dead tokens (404/403/UNREGISTERED), and is a **safe no-op when `services.fcm` is unconfigured**. Fully non-throwing.

### Files Modified
- `app/Services/Notifications/AdminNotificationService.php` — after the Phase-1 DB insert, additionally calls `FcmService::sendToAllAdmins(title, message, action_url, {type, notification_id})`. Additive + non-throwing; the stored notification is unchanged. **All future notification types automatically get push.**
- `app/Http/Controllers/API/AdminNotificationController.php` — added `registerFcmToken` (POST), `deleteFcmToken` (DELETE), and a `search` filter (title/message LIKE) on `index`. All admin-token guarded.
- `config/services.php` — added `fcm` block (`project_id`, `client_email`, `private_key`, `frontend_url`).
- `routes/api.php` — added `POST /admin/fcm/token`, `DELETE /admin/fcm/token`.
- `.env` — added blank `FCM_*` placeholders (push disabled until filled).

### Endpoints (admin_token cookie auth)
- `POST /admin/fcm/token` — register/refresh this device's token (bound to the authed admin).
- `DELETE /admin/fcm/token` — unregister (on logout).
- (Phase 1, still active) `GET /admin/notifications` (now also `?search=`), `GET /admin/notifications/unread-count`, `POST /admin/notifications/{id}/read`, `POST /admin/notifications/read-all`.

### Security
Only authenticated admins register tokens, and pushes target `admin_fcm_tokens` exclusively → students/firms can never receive admin notifications.

### Database Changes
- New `admin_fcm_tokens` table (see `db_changes.txt`; applied via the migration above with `--path`).

### Rollback
Drop `admin_fcm_tokens`; remove the `fcm` config, the 2 routes, the 2 controller methods, and the one additive `FcmService::sendToAllAdmins` call. Phase-1 behaviour is untouched.

---

## 2026-06-14 — Admin Notification System (Phase 1: infrastructure only)

Backend storage + service + API + generation points for admin notifications. **No UI**, no notification bell, no browser notifications, no FCM (later phases).

### Files Added
- `database/migrations/2026_06_14_000001_create_admin_notifications_table.php` — `admin_notifications` (`id, type, title, message, action_url, metadata json, is_read, read_at, timestamps`; indexes on `type` and `(is_read, created_at)`). Designed for expansion: `type` is a free string driven by service constants; `metadata` JSON carries arbitrary context.
- `app/Models/AdminNotification.php` — Eloquent model (`metadata`→array, `is_read`→bool, `read_at`→datetime casts).
- `app/Services/Notifications/AdminNotificationService.php` — centralized `create(type, title, message, action_url, metadata)` (non-throwing — logs and returns null on failure so it never breaks the host flow). Type constants (`TYPE_FIRM_VERIFICATION`, `TYPE_PAYMENT_VERIFICATION`, `TYPE_CREATOR_PAYOUT`, `TYPE_CONTACT`, `TYPE_SYSTEM_ALERT`) + typed helpers for the 4 Phase-1 sources. **All future admin notifications should go through this service.**
- `app/Http/Controllers/API/AdminNotificationController.php` — admin-token-guarded (existing pattern): `index` (paginated, `?type` / `?is_read` filters, includes `unread_count`), `unreadCount`, `markRead`, `markAllRead`.

### Endpoints (admin_token cookie auth, in controller)
- `GET  /admin/notifications`
- `GET  /admin/notifications/unread-count`
- `POST /admin/notifications/{id}/read`
- `POST /admin/notifications/read-all`

### Generation points wired (each one-line, non-throwing)
- **Firm verification** — `FirmController@registerFirm` (after commit, once per firm).
- **Payment verification** — `WalletController@submitManualRecharge` (after the recharge row is created).
- **Creator payout** — `CreatorMarketplaceController@approveDeliverable` (inside the once-per-engagement payout-creation block).
- **Contact form** — `PublicController@submitContact` (after the submission is stored).

### Database Changes
- New `admin_notifications` table (see `db_changes.txt`; applied via the migration above with `--path`, since this project's schema baseline is tracked in `db_changes.txt`).

### Rollback Plan
- Drop `admin_notifications`; remove the controller/model/service, the 4 routes, and the 4 one-line generation calls. No existing behaviour depends on these calls (service is non-throwing).

---

## 2026-06-17 — Standardized all rate limiters to 10/min

Raised every named rate limiter in `app/Providers/AppServiceProvider.php` to **10 requests/minute** (scopes unchanged). Previously: `auth-login` 5→10, `auth-register` 5→10, `auth-forgot` 3→10, `email-verify` 3→10, `payment-initiate` 5→10, `payment-proof` 5→10, `contact` 5→10. `apply` was already 10.

No route, scope, 429 body, or DB changes.

---

## 2026-06-14 — Rate Limiting for Critical Endpoints (audit remediation)

Additive security hardening only — no business/wallet/payment/application/auth logic changed. Limits are generous (5–10/min) so genuine users are unaffected; only excessive bursts get a clean HTTP 429.

### Files Modified
- `app/Providers/AppServiceProvider.php` — `boot()` now registers 8 **named** `RateLimiter::for()` limiters. Each returns a shared 429 body `{"success":false,"status":false,"message":"Too many requests. Please try again in a few minutes."}` (both `success` per spec and `status` for the app's frontend convention).
- `routes/api.php` — appended `->middleware('throttle:<name>')` to 14 routes (no other change).

### Named limiters & scope
| Limiter | Limit | Scope |
|---|---|---|
| `auth-login` | 5/min | per IP **and** per email |
| `auth-register` | 5/min | per IP |
| `auth-forgot` | 3/min | per IP **and** per email |
| `email-verify` | 3/min | per user (`auth_token` cookie) |
| `apply` | 10/min | per user |
| `payment-initiate` | 5/min | per user |
| `payment-proof` | 5/min | per user |
| `contact` | 5/min | per IP |

Per-user scope keys off the `auth_token` cookie (stable per session, available before any middleware → independent of middleware order; falls back to IP).

### Routes protected
- Auth: `POST /login`, `/registerStudent`, `/registerFirm`, `/auth/forgot-password`, `/email/send-verification-link`.
- Applications: `POST /jobs/{id}/apply` (covers job **and** articleship — same endpoint).
- Payments (initiate): `POST /wallet/recharge/phonepe/initiate`, `/payments/phonepe/initiate` (firm), `/creator-marketplace/engagements/{id}/payment/phonepe/initiate`.
- Payments (proof): `POST /wallet/recharge/manual`, `/student/premium-request`, `/creator-marketplace/engagements/{id}/payment/manual`.
- Public forms: `POST /contact-submission`, `/newsletter/subscribe`.

### Explicitly NOT throttled
All `/admin/*`, all PhonePe **webhooks**, PhonePe **verify** endpoints (auto-called on redirect return), queues/jobs, and all read/GET endpoints.

### DB Changes
None.

### Rollback Plan
- Remove the `->middleware('throttle:*')` calls in `routes/api.php` and the `configureRateLimiters()` method in `AppServiceProvider`. No data/schema impact.

---

## 2026-06-14 — Financial Integrity & Webhook Security Hardening (audit remediation)

Scope: wallet/SYS-coin race conditions, duplicate-application protection, PhonePe webhook security. No unrelated modules touched.

### Task 1 — Wallet & SYS Coin race conditions
- **NEW** `app/Exceptions/InsufficientFundsException.php` — thrown when a locked, re-validated balance is below the required amount.
- `app/Helpers/WalletHelper.php`
  - `hold()` — now selects the `student_wallets` row with **`lockForUpdate()`** and **re-validates `available_balance >= APPLICATION_FEE` inside the transaction**; throws `InsufficientFundsException` instead of writing a negative balance.
  - `consume()` / `release()` — lock the `application_holds` row (`lockForUpdate()` + `status='held'` re-check) and the wallet row, so two concurrent settlements (e.g. a manual reject racing the auto-expiry job) cannot double-process one hold.
- `app/Helpers/SysCoinHelper.php` — identical hardening on `hold()` / `consume()` / `release()` against `sys_coin_accounts` / `sys_coin_holds` (integer coins; throws `InsufficientFundsException` on shortfall).
- `app/Http/Controllers/API/JobsController.php` (`applyJob`) — catches `InsufficientFundsException` → clean "Insufficient balance" response (rolls back, no charge); the pre-checks and wallet-confirmation flow are unchanged.

### Task 2 — Duplicate application protection
- DB-level `UNIQUE(job_id, student_id)` constraint `uq_application_job_student` added to `applications` (see `db_changes.txt`). Zero pre-existing duplicates confirmed before applying.
- `applyJob` now catches the duplicate-key `QueryException` (errno **1062**) and returns the existing **409 "You already applied for this job"** message — the app-level `exists()` check and its message are retained for the common case.

### Task 3 — PhonePe webhook security
- `app/Services/Payment/PhonePeGateway.php` `verifySignature()` — **fail CLOSED**: missing webhook credentials now return `false` (was `true`). Empty `Authorization` header rejected. Constant-time, case-insensitive hex compare retained.
- `app/Http/Controllers/API/PhonePeWalletController.php`
  - `webhook()` — crediting moved into a single transaction that **locks the `wallet_recharges` row and re-checks `payment_status='paid'`** before crediting; concurrent / duplicate / replayed webhooks are now no-ops (idempotent). Amount still comes from the DB row, never the payload.
  - `verify()` — same locked re-check, so a user-triggered verify racing the S2S webhook can never double-credit.

### Database Changes
- `applications`: `ADD CONSTRAINT uq_application_job_student UNIQUE (job_id, student_id)`. Documented in `db_changes.txt` with rollback.

### Behaviour preserved
- Existing successful PhonePe payments, recharge workflow, ledger entries, balances, idempotent credits, and the duplicate-application message all continue to work.

---

## 2026-06-14 — Admin: System Health endpoint (application-level)

### Goal
Give admins quick operational visibility via a lightweight, application-level health check. **No** infra metrics (CPU/RAM/Docker/Redis/Nginx/process/load) — application health only.

### Files Added / Modified
- **NEW** `app/Http/Controllers/API/AdminSystemHealthController.php` — `GET /admin/system-health` (admin-token guarded, same `admin_users` cookie pattern as other admin controllers). Runs 7 checks and computes an overall status:
  1. **Database** — `SELECT 1` with response-time (ms). Connected=green / Disconnected=red.
  2. **Queue Workers** — `queue.default` driver aware. `sync` → operational. `database` → verifies the `queue_jobs` table is reachable and not backed up; a backlog of unreserved jobs older than 120s ⇒ "Not Running" (red), else "Running" (green). Table unreachable ⇒ red.
  3. **Failed Jobs** — `failed_jobs` count. 0=green, ≥1=yellow.
  4. **Storage Usage** — `disk_total_space`/`disk_free_space` on `storage_path()`. <80%=green, 80–90%=yellow, >90%=red. Returns used/total GB + percent. (Disk capacity only — not a server/process metric.)
  5. **PhonePe** — validates `config('services.phonepe')` required keys (merchant_id, client_id, client_secret, webhook_username, webhook_password) are present. **No API call.** Configured=green / Missing=red.
  6. **Email Service** — validates `config('mail')` default mailer creds (smtp: host+username+password). **No test email.** Configured=green / Missing=red.
  7. **Sitemap** — static URL count (reused from `SitemapController::staticUrlCount()`), published-blogs count, and route reachability (confirms `sitemap.xml`, `sitemaps/static.xml`, `sitemaps/blogs.xml` are registered — no HTTP self-call). Healthy=green / Issues=yellow.
  - **Overall**: any red ⇒ Critical; else any yellow ⇒ Warning; else Healthy. Includes `checked_at` ISO timestamp.
- `app/Http/Controllers/API/SitemapController.php` — added `public static staticUrlCount()` so the health check reuses the single source of truth for the static page list.
- `routes/api.php` — registered `GET /admin/system-health`.

### DB Changes
None. Reads existing `queue_jobs`, `failed_jobs`, `blogs`; checks config + disk only.

### Rollback Plan
- Remove the route, `AdminSystemHealthController`, and the `staticUrlCount()` accessor. No data/schema impact.

---

## 2026-06-14 — Wallet: Gateway recharges excluded from manual approval queue + manual-proof validation

### Problem (root cause)
Successful PhonePe (and any future Razorpay) wallet recharges were appearing in **Admin → Pending Payment Approvals**. Gateway rows are never actually stored as `manual` (initiate sets `payment_method='phonepe'`), but:
1. `AdminWalletController@getRecharges` returned **every** `wallet_recharges` row for the selected status regardless of `payment_method`. A gateway order sits at `status='pending'` from *initiate* until *verify*/*webhook* credits it, so that transient row surfaced in the admin queue. The `counts` query was likewise unscoped.
2. (Frontend) the admin table labelled any non-`razorpay` method as "Manual", so `phonepe` rows were mislabelled.

The gateway success path itself was already correct: `verify()` / `webhook()` set `payment_status='paid'`, `status='approved'` and call `WalletHelper::credit` — no admin action needed.

### Files Modified
- `app/Http/Controllers/API/AdminWalletController.php`
  - `getRecharges()` — main query and the `counts` query now constrained to `payment_method = 'manual'`. The approval page is now strictly the manual payment-proof queue; gateway recharges (pending/approved/rejected) never appear.
  - `approveRecharge()` / `rejectRecharge()` — added a guard returning HTTP 422 if `payment_method !== 'manual'` ("Gateway payments are auto-verified"), preventing an admin from manually crediting/rejecting an auto-handled (or unpaid) gateway order.
- `app/Http/Controllers/API/WalletController.php`
  - `submitManualRecharge()` validation tightened: `reference_number` (Transaction ID) and `screenshot` (payment proof) are now **required**; `utr_number` remains **optional**; `amount` stays required. Added explicit validation messages and a 422 status on failure. Attachment rules unchanged (`mimes:jpg,jpeg,png,pdf`, `max:5120` = 5 MB).

### Behaviour after change
- PhonePe success → auto-verified, wallet auto-credited, `status='approved'`, NOT in pending approvals. ✓
- Razorpay (when wired) success → same path / same exclusion. ✓
- Manual upload → `payment_method='manual'`, `status='pending'` → appears in approval queue → admin approve/reject as before. ✓

### DB Changes
None. (`wallet_recharges.payment_method` ENUM already includes `razorpay`,`phonepe`,`manual`.)

### Rollback Plan
- Remove the `where('payment_method','manual')` constraints in `getRecharges()`/counts and the approve/reject guards; relax the two `required` rules in `submitManualRecharge()`.

---

## 2026-06-14 — Sitemap: Refactor to Sitemap-Index Architecture

### Goal
Restructure the single dynamic `sitemap.xml` into a scalable **sitemap index** so future sections (jobs, companies, resources) can be added as their own child sitemaps without bloating one file. No URL structures changed.

### New structure
- `/sitemap.xml` → now a **sitemap index** (`<sitemapindex>`) referencing the child sitemaps.
- `/sitemaps/static.xml` → static marketing / legal pages.
- `/sitemaps/blogs.xml` → one `<url>` per published blog.
- Future placeholders (not yet implemented): `jobs.xml`, `companies.xml`, `resources.xml` — added by appending to `CHILD_SITEMAPS` + a route + a method.

### Files Modified
- `app/Http/Controllers/API/SitemapController.php` — refactored from a single `index()` urlset into three actions:
  - `index()` — emits `<sitemapindex>` listing `static.xml` + `blogs.xml` (driven by `CHILD_SITEMAPS` const).
  - `static()` — emits the static `<urlset>`. Priorities: home `1.0`, blogs `0.9`, resources `0.8`, about `0.6`, contact `0.5`, policies `0.3`.
  - `blogs()` — emits the blogs `<urlset>`: `WHERE status='published'`, ordered by `updated_at` desc; `<lastmod>` = **`updated_at`** (ISO-8601/Atom), `changefreq=monthly`, `priority=0.8`. Drafts/unpublished (`status='draft'`) and deleted (hard-deleted) rows are excluded automatically.
  - Shared helpers: `frontendBase()` (uses `config('services.frontend_url')`, fallback `https://startyourstory.in`, points at the React frontend not the API domain), `xml()`, `urlNode()`, `esc()` (XML-escapes `<loc>`), `formatDate()`.
- `routes/web.php` — added `GET /sitemaps/static.xml` and `GET /sitemaps/blogs.xml` alongside the existing `GET /sitemap.xml`.

### Automatic updates (no files, no cron)
All three responses are generated per-request from the `blogs` table: publish → appears in `blogs.xml`; update → `<lastmod>` refreshes; unpublish (`status`→`draft`) → removed; delete → removed. No physical XML files are written anywhere (backend or frontend).

### Verified (local `php artisan serve`)
- `GET /sitemap.xml` → 200, `application/xml`, valid `<sitemapindex>` with both children.
- `GET /sitemaps/static.xml` → 200, 8 static URLs with the spec'd priorities.
- `GET /sitemaps/blogs.xml` → 200, one `<url>` per published blog with `updated_at` lastmod.

### Deployment note
Nginx must route both `startyourstory.in/sitemap.xml` and `startyourstory.in/sitemaps/*.xml` to the Laravel app.

### DB Changes
None.

### Rollback Plan
- Revert `SitemapController` to the single-`index()` urlset version and remove the two `/sitemaps/*.xml` routes. No data/schema impact.

---

## 2026-06-14 — Blog SEO: Dynamic Sitemap + Article dateModified

### Goal
Server-side SEO support for blog indexing: a dynamic, always-current XML sitemap generated from the DB, plus expose `updated_at` so the frontend Article schema can emit `dateModified`.

### Files Modified / Added
- **NEW** `app/Http/Controllers/API/SitemapController.php` — generates `sitemap.xml` dynamically.
  - Static pages: `/`, `/about-us`, `/resources`, `/blogs`, `/contact`, `/privacy-policy`, `/terms-and-conditions`, `/cookie-policy` (each with `changefreq` + `priority`).
  - Published blogs: `SELECT slug, published_at, updated_at FROM blogs WHERE status = 'published'`, newest first. Drafts (`status='draft'`) and deleted rows (hard-deleted — no soft deletes on this table) are excluded automatically.
  - `<lastmod>` uses `published_at` (falls back to `updated_at`) in W3C/Atom ISO-8601 format. The `/blogs` listing node's `<lastmod>` tracks the newest published blog.
  - Every `<loc>` is built from `config('services.frontend_url')` (fallback `https://startyourstory.in`) so URLs point at the React frontend, **not** the API domain. `<loc>` values are XML-escaped.
  - Returns `Content-Type: application/xml; charset=UTF-8`, `Cache-Control: public, max-age=3600`, HTTP 200.
- `routes/web.php` — registered `GET /sitemap.xml` → `SitemapController@index` (web, not `/api`, so it serves at the domain root for Nginx to expose as `https://startyourstory.in/sitemap.xml`).
- `app/Http/Controllers/API/BlogController.php` — `getPublishedBlogBySlug()` now also selects `blogs.updated_at` (consumed by the frontend Article `dateModified`).

### Automatic updates (no cron, no manual editing)
The sitemap is generated on each request straight from the `blogs` table, so publish → appears, unpublish (`status` back to `draft`) → removed, delete → removed, all automatically.

### Deployment note
Nginx must route `startyourstory.in/sitemap.xml` to the Laravel app (the static `start-your-story-ui/public/sitemap.xml` is now superseded by this dynamic route and should not be served in preference to it). In production set `FRONTEND_URL=https://startyourstory.in` and re-run `php artisan config:cache`.

### DB Changes
None. Reads existing `blogs` columns only.

### Rollback Plan
- Remove the `GET /sitemap.xml` route and `SitemapController.php`; revert the `updated_at` column add in `getPublishedBlogBySlug()`. No data/schema impact.

---

## 2026-06-14 — Blog Categories: Case-Insensitive Duplicate Prevention

### Change
Added case-insensitive duplicate name validation to `createCategory` and `updateCategory` in `AdminBlogController`.

### Files Modified
- `app/Http/Controllers/API/AdminBlogController.php`
  - `createCategory`: Before inserting, checks `LOWER(name) = LOWER(trim(request->name))` across all existing categories. Returns `{ status: false, message: "Category already exists." }` (HTTP 200) if a match is found. Treats "Articleship Guidance", "articleship guidance", and "ARTICLESHIP GUIDANCE" as duplicates.
  - `updateCategory`: Same check, but excludes the category being edited (`WHERE id != $id`) so renaming a category to the same name (or changing only case) is allowed.

### DB Changes
None.

---

## 2026-06-13 — Referral Dashboard: API enrichment (read-only)

### Goal
Power the redesigned `/referrals` dashboard with **real** data — no mock/dummy values, no new tables.

### Files Modified
- `app/Http/Controllers/API/ReferralController.php` — `index()` now also returns (all derived from existing tables):
  - `stats.students_this_month` / `stats.firms_this_month` — current-month referral counts (`users.created_at`).
  - `coins` `{ earned, this_month }` — from `sys_coin_accounts.lifetime_earned` and a sum of this-month earn-type rows in `sys_coin_transactions`.
  - `pending_rewards` `{ amount, firm_count }` — sum/count of the referrer's `referral_payouts` in `pending`+`approved`.
  - `lifetime` `{ coins, pending_amount }`.
  - Each `referrals[]` row gains `status` (student: Completed if a `REFERRAL_BONUS` ledger row exists for them, else Pending; firm: maps `referral_payouts.status` → Pending / Under Review / Completed) + `reward_type` + `reward_label`.
  - Existing fields (`referral_code`, `referral_count`, `stats.{total,firms,students}`, base `referrals` columns) are preserved.

### DB Changes
None. Reads existing `users`, `sys_coin_accounts`, `sys_coin_transactions`, `referral_payouts`.

### Rollback Plan
- Revert `index()` to the prior version (return only `referral_code`, `referral_count`, `stats{total,firms,students}`, and the base `referrals` list). No data/schema impact.

---

## 2026-06-13 — Referral Rewards + SYS Coins

### Goal
Add two separate reward systems on top of the existing referral linkage (`users.referral_code`/`referred_by`/`referral_count`) **without** changing how wallet money works:
1. **SYS Coins** — a points currency (welcome bonus for provisional students; +10 to referrer per referred student; new application-payment tier Free → Coins → Wallet). Mirrors `WalletHelper`'s hold/consume/release ledger pattern.
2. **Real-money firm-referral payouts** — when a referred firm buys premium, a pending ₹2,000 payout record is created for the referrer; admin settles it externally (mark-only, no wallet credit).

Coins and wallet money are never mixed.

### Files Created
- `app/Helpers/SysCoinHelper.php` — coin account/ledger/holds (constants `WELCOME_BONUS=100`, `STUDENT_REFERRAL_BONUS=10`, `APPLICATION_COST=50`, `HOLD_DAYS=10`); `getOrCreate/getBalance/hasEnoughCoins/grant/hold/consume/release`; idempotent `maybeGrantWelcomeBonus` (provisional only) + `maybeGrantStudentReferralBonus` (rewards the referrer).
- `app/Helpers/ReferralHelper.php` — `validateCode()` + `resolveReferrerId()` (drops unknown/self-referral codes); `onFirmPremiumActivated()` creates the pending payout (idempotent; UNIQUE on referred firm).
- `app/Http/Controllers/API/SysCoinController.php` — `GET /sys-coins` (balance), `POST /sys-coins/ledger`.
- `app/Http/Controllers/API/AdminReferralController.php` — `listPayouts/approvePayout/markPayoutPaid/listCoinTransactions/listReferralTransactions` (admin_token auth, mirrors AdminWalletController).

### Files Modified
- `app/Http/Controllers/API/ReferralController.php` — added public `validate()` for live registration feedback.
- `app/Http/Controllers/API/UserController.php` — `registerStudent`: replaced hard "Invalid referral code" rejection with `ReferralHelper::resolveReferrerId()` (self-referral dropped, registration continues); `verify` + `updateProfile`: call `SysCoinHelper::maybeGrantWelcomeBonus` + `maybeGrantStudentReferralBonus` (idempotent, order-independent).
- `app/Http/Controllers/API/FirmController.php` — `registerFirm`: same self-referral-tolerant resolution.
- `app/Http/Controllers/API/JobsController.php` — `applyJob`: payment tier Free → SYS Coins (≥50) → Wallet (₹49); returns `requires_payment_confirmation` when wallet money would be charged without `confirm_wallet`; sets `payment_source`/`coin_hold_id`. Added `SysCoinHelper::consume`/`release` beside the existing wallet calls (each no-ops if not the paying currency).
- `routes/console.php` — added a parallel `sys_coin_holds` 10-day auto-expiry loop → `SysCoinHelper::release(...,'auto_expired')`.
- `app/Http/Controllers/API/AdminController.php` (×2: manual subscription add + premium-request approval) & `app/Http/Controllers/API/PhonePeFirmController.php` (×2: verify + webhook) — call `ReferralHelper::onFirmPremiumActivated($firmProfileId)` after each `is_premium=1`.
- `routes/api.php` — `GET /referral/validate` (public); `GET /sys-coins`, `POST /sys-coins/ledger` (auth); admin `/admin/referral-payouts`(+`/{id}/approve`,`/{id}/mark-paid`), `/admin/sys-coins/transactions`, `/admin/referral-transactions`.
- `app/Http/Controllers/API/WalletController.php` — `getApplyStatus` now also returns `available_coins`, `coin_cost`, `wallet_balance`, and `can_apply` (true if free quota OR ≥50 coins OR ≥₹49 wallet). This powers the frontend apply-gate so students with SYS Coins/wallet balance aren't wrongly shown "Upgrade To Apply" once free applications run out.

### DB Changes
See `db_changes.txt` (2026-06-13 section). New tables `sys_coin_accounts`, `sys_coin_transactions`, `sys_coin_holds`, `referral_payouts`; `applications` gains `payment_source` + `coin_hold_id`. Apply that SQL before deploying. No Eloquent migration (project convention).

### Rollback Plan
- Revert the 6 modified controllers + `routes/console.php` + `routes/api.php`; delete the 2 helpers + 2 controllers.
- Run the ROLLBACK block in `db_changes.txt` (drops the 4 tables + 2 `applications` columns). No wallet-money data is touched.

### Testing Checklist
- [ ] Referral code valid / invalid / empty / self (email & mobile) — registration always proceeds; self-ref dropped.
- [ ] Provisional student: verify email + complete profile → +100 coins once; not for semi-qualified/qualified/firm.
- [ ] Referred student completes onboarding → referrer +10 coins once.
- [ ] Referred firm buys premium (all 4 activation paths) → one pending ₹2,000 payout; no duplicate.
- [ ] Apply: ≥50 coins → 50 held; interview accepted → consumed; rejected/auto-expiry → released. Wallet path unchanged.
- [ ] `confirm_wallet` gate: no row created until confirmed.
- [ ] Admin: list/approve/mark-paid payouts (status only); coin + referral ledgers.

---

## 2026-06-13 — Firm Profile: Address required validation

### Files Modified
- `app/Http/Controllers/API/FirmController.php` — `firm_profile_update()`: added a `Validator::make` at the top of the method enforcing `address` => `required|string` with message "Address is required."; on failure it `DB::rollBack()`s and returns the controller's standard `{status:false, message}` shape (matching the existing validator blocks in this file). `Validator` was already imported.

### Notes
- Validation applies only to profile create/update. The read path (`getFirmProfileDetails`) is untouched, so existing firms with an empty address still load without breaking.
- The "Other Domains" feature is frontend-only: custom domains are merged into the existing `exposure_type` JSON array by the client, so no controller, validation, or schema change was needed for it.

### DB Changes
None. No migration.

### Rollback Plan
- Remove the `address` `Validator::make` block from `firm_profile_update()`.

---

## 2026-06-12 — Feature: Admin Student Detail Endpoint

### Files Modified
- `app/Http/Controllers/API/AdminController.php` — added `getStudent(Request $request, $id)`: admin-auth via `adminFromRequest()`; joins `users` + `student_profiles`; returns full profile fields (id, name, email, mobile, profile_image as full URL, profile_completed, is_verified, created_at, is_deleted, deletion_requested_at, scheduled_deletion_at, plus all student_profiles columns); JSON-decodes `exposure_type` and `preferred_location`; 404 if student not found.
- `routes/api.php` — added `Route::get('/admin/students/{id}', [AdminController::class, 'getStudent'])` alongside the existing POST list route.

### No DB changes.

### Rollback Plan
- Remove `getStudent()` from `AdminController.php`
- Remove the `GET /admin/students/{id}` route from `routes/api.php`

---

## 2026-06-12 — Feature: Student Account Deletion (30-day soft delete)

### Reason
Allow students to delete their own account with a 30-day recovery window. Not a hard delete — records are preserved; after 30 days the account is flagged `is_deleted = true`. Firm and admin accounts are unaffected.

### Files Created
- `database/migrations/2026_06_12_000001_add_account_deletion_to_users_table.php` — adds `deletion_requested_at` and `scheduled_deletion_at` to `users` (guards `is_deleted`, which already exists).

### Files Modified
- `app/Http/Controllers/API/UserController.php` — added `requestAccountDeletion()` (student-only). Sets `deletion_requested_at = now()`, `scheduled_deletion_at = now()+30d`; withdraws active applications (`recruiter_status = 'Withdrawn by Candidate'`); cancels upcoming interviews (`student_interview_response = 'Withdrawn'`) and notifies the firm via `NotificationHelper` + a firm-visible `recruiter_actions` row; clears `api_token`/`user_sessions` (logout) and expires the auth cookie. Added `use App\Helpers\NotificationHelper;`.
- `app/Http/Controllers/API/AuthController.php` — `login()`: no longer pre-filters `is_deleted`; a permanently-deleted account returns `403 "Your account has been deleted."`; a student logging in during the grace window has `deletion_requested_at`/`scheduled_deletion_at` cleared (auto-restore) and receives a "Welcome back…" message plus `data.account_restored = true`.
- `app/Http/Controllers/API/FirmDashboardController.php` — `getCandidates()` now excludes students with a pending deletion (`whereNull('users.deletion_requested_at')`) so their profile is hidden during the grace window (reversible on login).
- `app/Http/Controllers/API/AdminController.php` — `getStudents()` accepts `deletion_status` (`active` default | `deleted` | `all`) and returns `is_deleted`, `deletion_requested_at`, `scheduled_deletion_at`.
- `routes/api.php` — added `POST /account/request-deletion` inside the `ApiAuthMiddleware` group.
- `routes/console.php` — added daily `finalize-student-account-deletions` schedule (03:00): sets `is_deleted = true` for students whose `scheduled_deletion_at <= now()` and `is_deleted = false`. No physical deletion.

### DB changes
See `db_changes.txt` (2026-06-12 entry) — two nullable DATETIME columns + index, with rollback SQL.

### Notes
- Wallet holds on withdrawn applications are intentionally left untouched (they auto-expire via the existing `expire-application-holds` job).
- Auto-restore reactivates the account/profile only; it does not reinstate already-withdrawn applications or cancelled interviews.

---

## 2026-06-12 — Fix: PhonePe API Migration v1 → v2 (OAuth2)

### Reason
PhonePe deprecated the v1 salt-key / X-VERIFY signature scheme. The current dashboard only exposes `client_id`, `client_secret`, and `merchant_id` — there is no `salt_key`. All endpoints and auth headers have changed.

### Files Modified
- `app/Services/Payment/PhonePeGateway.php` — Full rewrite. Replaced per-request SHA256 X-VERIFY with OAuth2 `client_credentials` token flow (`POST /v1/oauth/token`). Token cached via Laravel `Cache` until 60s before `expires_at`. Initiate now POSTs to `/checkout/v2/pay` with `O-Bearer` auth. Status check now GETs `/checkout/v2/order/{id}/status`. Webhook signature now verifies `SHA256(username:password)` against `Authorization` header.
- `config/services.php` — Replaced `PHONEPE_SALT_KEY` / `PHONEPE_SALT_INDEX` with `PHONEPE_CLIENT_ID`, `PHONEPE_CLIENT_SECRET`, `PHONEPE_CLIENT_VERSION`. Added `PHONEPE_WEBHOOK_USERNAME` / `PHONEPE_WEBHOOK_PASSWORD`.
- `app/Http/Controllers/API/PhonePeWalletController.php` — `verify()`: success check changed from `code === 'PAYMENT_SUCCESS'` to `state === 'COMPLETED'`; transactionId path updated to `paymentDetails[0].transactionId`. `webhook()`: removed base64 decode; now reads plain JSON body; uses `Authorization` header for sig verification; reads `payload.merchantOrderId` and `payload.state`.

### ENV Variables — Replace in `.env`
```
# Remove:
# PHONEPE_SALT_KEY=...
# PHONEPE_SALT_INDEX=...

# Add:
PHONEPE_MERCHANT_ID=<from PhonePe dashboard>
PHONEPE_CLIENT_ID=<from PhonePe dashboard>
PHONEPE_CLIENT_SECRET=<from PhonePe dashboard>
PHONEPE_CLIENT_VERSION=1
PHONEPE_BASE_URL=https://api-preprod.phonepe.com/apis/pg-sandbox
FRONTEND_URL=http://localhost:3000
PHONEPE_WEBHOOK_USERNAME=<configured in PhonePe dashboard webhook settings>
PHONEPE_WEBHOOK_PASSWORD=<configured in PhonePe dashboard webhook settings>
```

After updating `.env`, run: `php artisan config:clear`

### No DB changes. No route changes. No frontend changes.

---

## 2026-06-12 — Feature: PhonePe TEST MODE Payment Gateway Integration

### Scope
Wallet Recharge flow only. Razorpay is unchanged. Both gateways coexist.

### Files Created
- `app/Services/Payment/PhonePeGateway.php` — Implements `PaymentGateway` interface. SHA256 X-VERIFY signature for both initiate and webhook. Calls PhonePe UAT API using Laravel `Http` facade (no PHP SDK needed).
- `app/Http/Controllers/API/PhonePeWalletController.php` — Handles `initiate`, `verify`, and `webhook` endpoints. Idempotency guard on `payment_status = 'paid'`. Signature verified server-side before any DB write.

### Files Modified
- `app/Services/Payment/PaymentGatewayFactory.php` — Uncommented `'phonepe' => new PhonePeGateway()` case.
- `config/services.php` — Added `phonepe` config block reading from `PHONEPE_MERCHANT_ID`, `PHONEPE_SALT_KEY`, `PHONEPE_SALT_INDEX`, `PHONEPE_BASE_URL`, `FRONTEND_URL`.
- `routes/api.php` — Added 3 routes: `POST /wallet/recharge/phonepe/initiate` (auth), `POST /wallet/recharge/phonepe/verify` (auth), `POST /wallet/recharge/phonepe/webhook` (public, sig-verified).

### DB Changes (see db_changes.txt)
- `ALTER TABLE wallet_recharges MODIFY payment_method ENUM('razorpay','phonepe','manual')`
- `ALTER TABLE wallet_recharges ADD COLUMN gateway_response JSON NULL`

### ENV Variables Required
```
PHONEPE_MERCHANT_ID=PGTESTPAYUAT
PHONEPE_SALT_KEY=099eb0cd-02cf-4e2a-8aca-3e6c6aff0399
PHONEPE_SALT_INDEX=1
PHONEPE_BASE_URL=https://api-preprod.phonepe.com/apis/pg-sandbox
FRONTEND_URL=http://localhost:3000
```

### Payment Flow
1. Frontend `POST /wallet/recharge/phonepe/initiate` → backend creates `wallet_recharges` record, calls PhonePe UAT API, returns `redirect_url`
2. Frontend redirects user to PhonePe checkout
3. PhonePe redirects user to `FRONTEND_URL/wallet/recharge?phonepe_txn={merchantTxnId}`
4. Frontend auto-calls `POST /wallet/recharge/phonepe/verify` → backend queries PhonePe status API → credits wallet
5. PhonePe also POSTs to webhook → backend verifies X-VERIFY signature → idempotently credits wallet

### Security
- Signatures verified server-side with `hash_equals()` before any DB write
- Idempotency guard: `payment_status = 'paid'` check prevents double-credit
- No credentials hardcoded; all via env vars

### Rollback
```sql
ALTER TABLE `wallet_recharges` DROP COLUMN `gateway_response`;
ALTER TABLE `wallet_recharges`
  MODIFY COLUMN `payment_method` ENUM('razorpay','manual') NOT NULL DEFAULT 'razorpay';
```
Backend: delete `PhonePeGateway.php`, `PhonePeWalletController.php`; re-comment factory case; remove routes; remove `phonepe` config block.

---

## 2026-06-11 — Fix: Student Profile — backend business-logic validation

### Files Modified
- `app/Http/Controllers/API/UserController.php` — `updateProfile()` method

### Added validation block (runs after main Validator, before file processing)

All four rules call `DB::rollBack()` before returning to stay consistent with the surrounding transaction.

1. **Professional status required** — if `looking_for = 'articleship'` and `ca_status` is empty → 422-style error "Please select your professional status."

2. **Core domain required** — if flow is semi-qualified, qualified, or articleship with inter-both status AND `core_department` is empty → "Please select your core domain."

3. **Exposure preference — domain-wise requires at least one domain** — if the flow needs exposure AND `exposure_type` is sent as neither "overall" nor a valid comma-separated list → "Please select at least one preferred domain." (Catches the edge case where frontend sends `exposure_type = ""` when domain-wise mode is active but nothing is checked.)

4. **Resume required when no existing resume** — if flow is articleship/semi-qualified/qualified AND no `resume_path` file uploaded AND `student_profiles.resume_path` is empty → "Please upload your resume."

### Intent
These rules are the server-side enforcement of the frontend wizard's section-level validation, ensuring the same constraints hold for direct API calls.

---

## 2026-06-11 — Fix: Experience Department — backend storage normalization

### Files Modified
- `app/Http/Controllers/API/UserController.php`

### Root cause
`experience_department` was declared `nullable` (single scalar). When the frontend sent `experience_department[]` array items, PHP assembled them into an array but the validator didn't enforce the shape. The storage code called `json_encode()` on that array, producing clean JSON — but the old frontend prefill used `.split(",")` on that string, fragmenting it. Re-submit then sent both a CSV string AND individual fragment items; PHP received a mixed array; backend double-encoded it. Each round trip added one nesting level.

### Fixes

**1. Validator rule corrected**:
```php
'experience_department'   => 'nullable|array',
'experience_department.*' => 'nullable|string',
```
Laravel now correctly maps `experience_department[]` → clean PHP array and rejects non-string members.

**2. Storage normalization hardened**: Strips empty strings/nulls from the array before encoding; uses `array_values` to ensure stored JSON is always a flat indexed array with no gaps.

---

## 2026-06-11 — Feature: topic_id on blog create/update with DB transaction

### Files Modified
- `app/Http/Controllers/API/AdminBlogController.php`
  - `getBlog`: appends `topic_id` to response by querying `blog_topics WHERE blog_id = $id`.
  - `createBlog`: added `topic_id` (nullable integer, exists:blog_topics) to validator; wrapped blog insert + tag insert + topic update in `DB::transaction()`; if `topic_id` provided → sets `blog_topics.status = 'published'` and `blog_topics.blog_id = $newBlogId`. Full try-catch rolls back on any failure.
  - `updateBlog`: added `topic_id` to validator; wrapped blog update + tag sync + topic sync in `DB::transaction()`; three cases handled atomically: (1) clear topic → unlink, revert status to `generated`; (2) change topic → unlink old, link new as `published`; (3) same topic → no-op.

### Transaction scope (createBlog)
```
DB::transaction {
  INSERT blogs
  INSERT blog_tag_map (if tags)
  UPDATE blog_topics SET status='published', blog_id=$id WHERE id=$topicId
}
```

### Transaction scope (updateBlog)
```
DB::transaction {
  UPDATE blogs
  DELETE + INSERT blog_tag_map (if tag_ids sent)
  UPDATE blog_topics (unlink old / link new / clear)
}
```

---

## 2026-06-11 — Blog Module Phase 3: Public Blog Detail API

### Files Modified
- `app/Http/Controllers/API/BlogController.php` — added `getPublishedBlogBySlug()`
- `routes/api.php` — added `GET /blogs/public/{slug}` (registered AFTER `/blogs/public/categories` so "categories" is never captured as a slug)

### DB Changes
No database changes required.

### API Endpoints Added
```
GET /blogs/public/{slug}
```

### Changes
- `getPublishedBlogBySlug()`: published-only (`status='published'` in WHERE — drafts 404 publicly); returns full content + meta_title/meta_description for SEO, category name/slug, featured_image_url
- Includes `tags` array (joined via blog_tag_map, ordered by name)
- Includes `prev` (next-older published) and `next` (next-newer published) `{title, slug}` objects by `published_at` for the detail page navigation cards — null when at either end
- 404 JSON `{status:false}` when slug missing or unpublished

### Rollback Plan
- Remove `getPublishedBlogBySlug()` from `BlogController.php`
- Remove the `/blogs/public/{slug}` route + comment from `routes/api.php`

---

## 2026-06-11 — Blog Module Phase 2: Public Blog Listing API

### Files Created
- `app/Http/Controllers/API/BlogController.php` — public (no-auth) blog endpoints

### Files Modified
- `routes/api.php` — 2 public routes added to the no-auth section

### DB Changes
No database changes required.

### API Endpoints Added
```
GET /blogs/public             ?search, ?category (slug), ?page, ?per_page (cap 30, default 10)
GET /blogs/public/categories
```

### Changes
- `getPublishedBlogs()`: hard-coded `WHERE blogs.status='published'` — drafts can never appear publicly regardless of params; selects only public-safe fields (no content/meta in list); search LIKE on title + excerpt only; category filter by `blog_categories.slug` (SEO-friendly URLs); ordered `published_at` DESC; paginated
- `getPublicBlogCategories()`: only categories with ≥1 published blog, with `published_count`
- Separate controller from `AdminBlogController` so the Phase 3 public detail page extends it

### Rollback Plan
- Delete `app/Http/Controllers/API/BlogController.php`
- Remove the two `/blogs/public*` routes and the `BlogController` import from `routes/api.php`

---

## 2026-06-11 — Blog Topics Management Module (Phase 1 Enhancement)

### Files Modified
- `app/Http/Controllers/API/AdminBlogController.php` — added Blog Topics section (5 methods)
- `routes/api.php` — added 5 topic routes inside existing `admin/blog` prefix group
- `db_changes.txt` — appended `blog_topics` table

### DB Changes
New table `blog_topics`:
- Core: id, title, slug (unique), category_id FK→blog_categories SET NULL, target_keywords TEXT, search_intent VARCHAR(50), notes TEXT
- Pipeline: priority ENUM('low','medium','high') default medium; status ENUM('pending','generating','generated','published','rejected') default pending; blog_id FK→blogs SET NULL (nullable); generation_source ENUM('manual','gpt','claude','other') default manual; ai_model; generated_at; published_at
- Audit: created_by (admin_users.id, plain BIGINT — no FK by design), timestamps
- Indexes: status, category_id, priority, blog_id, created_at

### API Endpoints Added
```
GET    /admin/blog/topics          ?search, ?status, ?category_id, ?priority, ?page, ?per_page
POST   /admin/blog/topics
GET    /admin/blog/topics/{id}
POST   /admin/blog/topics/{id}
DELETE /admin/blog/topics/{id}
```

### Changes
- `getTopics()` leftJoins blog_categories (category_name) and blogs (blog_title/blog_slug/blog_status); 4 filters + search across title/slug/target_keywords; paginated, per_page capped at 50
- `createTopic()` reuses existing `generateSlug()` helper; sets `created_by` from admin cookie auth; `generation_source` forced to 'manual'
- `updateTopic()` does NOT touch pipeline fields (blog_id, generation_source, ai_model, generated_at, published_at) — those are reserved for future AI automation
- `deleteTopic()` hard delete; linked blogs unaffected (FK SET NULL not needed since we delete the topic, not the blog)
- Future AI workflow supported by schema: automation picks status='pending' topics → sets status='generating' → creates blog draft → sets status='generated', blog_id=<new blog>, generation_source/ai_model/generated_at

### Rollback Plan
```sql
DROP TABLE IF EXISTS `blog_topics`;
```
Remove the 5 `/topics` routes from the `admin/blog` group in `routes/api.php`.
Remove the "Blog Topics" section (getTopics, getTopic, createTopic, updateTopic, deleteTopic) from `AdminBlogController.php`.

---

## 2026-06-11 — Blog Module Phase 1: Admin CRUD

### Files Created
- `app/Http/Controllers/API/AdminBlogController.php`

### Files Modified
- `routes/api.php` — added `Route::prefix('admin/blog')` group
- `db_changes.txt` — appended Blog Phase 1 schema

### DB Changes
New tables:
- `blog_categories` (id, name, slug unique, description, timestamps)
- `blog_tags` (id, name, slug unique, timestamps)
- `blogs` (id, title, slug unique, excerpt, content, featured_image, meta_title, meta_description, status ENUM('draft','published'), category_id FK→blog_categories, published_at, timestamps)
- `blog_tag_map` (blog_id FK cascade, tag_id FK cascade, unique blog+tag pair)

### API Endpoints Added
```
GET    /admin/blog/categories
POST   /admin/blog/categories
POST   /admin/blog/categories/{id}
DELETE /admin/blog/categories/{id}

GET    /admin/blog/tags
POST   /admin/blog/tags
POST   /admin/blog/tags/{id}
DELETE /admin/blog/tags/{id}

GET    /admin/blog/blogs            ?search, ?status, ?category_id, ?page, ?per_page
POST   /admin/blog/blogs
GET    /admin/blog/blogs/{id}
POST   /admin/blog/blogs/{id}
DELETE /admin/blog/blogs/{id}
POST   /admin/blog/blogs/{id}/publish
POST   /admin/blog/blogs/{id}/unpublish
```

### Changes
- `AdminBlogController` uses `getAdminUser()` cookie auth matching existing admin pattern
- `generateSlug()` private helper: derives unique slug from text via `Str::slug()` with numeric suffix loop
- Categories: `deleteCategory()` nulls `blogs.category_id` before deletion (FK SET NULL already handles at DB level, but explicit null-out ensures clean state)
- Blogs: create/update handle featured image upload to `blog-images/featured` storage; old image deleted on update
- `publishBlog()` stamps `published_at` at first publish; `unpublishBlog()` reverts status to draft (does NOT clear `published_at`)
- `updateBlog()` syncs tag_map only when `tag_ids` key is present in request (partial update safe)
- No public endpoints — admin only in Phase 1

### Rollback Plan
```sql
DROP TABLE IF EXISTS `blog_tag_map`;
DROP TABLE IF EXISTS `blogs`;
DROP TABLE IF EXISTS `blog_tags`;
DROP TABLE IF EXISTS `blog_categories`;
```
Remove `Route::prefix('admin/blog')` block from `routes/api.php`.
Delete `app/Http/Controllers/API/AdminBlogController.php`.

---

## 2026-06-10 — Auth: me() Omits Firm Fields for Non-Firm Users

### Files Modified
- `app/Http/Controllers/API/AuthController.php`

### DB Changes
None.

### Changes
- `me()` response previously returned `is_branch`, `parent_firm_id`, `parent_frn`, `firm_city` as `null` for non-firm users
- Now these four fields are omitted entirely from the JSON response for non-firm users using PHP spread + conditional array: `...($user->role === 'firm' ? [...] : [])`
- Firm users are unaffected — they still receive all four fields with their values

### Rollback Plan
- Revert to explicit `null` values: `'is_branch' => $user->role === 'firm' ? $isBranch : null`, etc.

---

## 2026-06-10 — CreatorMarketplace: Fix forbidNonCreator() Querying Wrong Table

### Files Modified
- `app/Http/Controllers/API/CreatorMarketplaceController.php`

### DB Changes
None.

### Changes
- **Root cause**: `ApiAuthMiddleware` sets `auth_user` from `DB::table('users')` only — no join to `student_profiles`. So `$user->looking_for` was always `null`, causing 403 for every creator student.
- **Fix**: `forbidNonCreator()` now queries `student_profiles` directly:
  ```php
  $profile = DB::table('student_profiles')
      ->where('user_id', $user->id)
      ->value('looking_for');
  if ($profile !== 'creator') { return 403; }
  ```
- All 14 creator-side API methods are now correctly accessible to students with `looking_for = 'creator'`

### Rollback Plan
- Revert `forbidNonCreator()` to `$user->looking_for !== 'creator'` check (broken — will 403 all creators again)

---

## 2026-06-10 — FirmDashboard: Add Creator Fields to getCandidates SELECT

### Files Modified
- `app/Http/Controllers/API/FirmDashboardController.php`

### DB Changes
None.

### Changes
- Added `student_profiles.availability_status` and `student_profiles.experience_years` to the `getCandidates` SELECT query
- These fields are needed to display Availability and Experience on creator tab student cards in the firm dashboard
- `getCandidateById` was unaffected (uses `->first()` without explicit column list, so all columns were already returned)

### Rollback Plan
- Remove `student_profiles.availability_status` and `student_profiles.experience_years` from the SELECT array

---

## 2026-06-10 — Revert B2 (forbidNonCreator) + Restore Client-Driven City Filter

### Files Modified
- `app/Http/Controllers/API/CreatorMarketplaceController.php`
- `app/Http/Controllers/API/FirmDashboardController.php`

### DB Changes
None.

### Changes

#### `CreatorMarketplaceController::forbidNonCreator()` (B2 reverted)
- Restored creator-only guard: checks `$user->looking_for !== 'creator'` and returns 403
- Previously (2026-06-09) it only checked `$user->role !== 'student'`, allowing all students
- All creator-side APIs are once again restricted to `looking_for = 'creator'` students:
  `submitBid`, `withdrawBid`, `getMyBids`, `getSelectedBidDetails`, `creatorRespondToBid`,
  `getMyEngagements`, `saveBankDetails`, `getPayoutStatus`, `getBidDetail`, `getMyEarnings`,
  `getBankDetails`, `submitDeliverable`, `requestRevision`, `approveDeliverable`

#### `FirmDashboardController::getCandidates()` city block
- Removed server-enforced city filter (`whereJsonContains` from `firm_profiles.city`)
- Restored client-driven filter: accepts `cities[]` from request; filters only when non-empty
- Empty `cities` → all candidates returned regardless of location

### Rollback Plan
- `forbidNonCreator()`: restore role-only check (`$user->role !== 'student'`)
- City block: restore `$firmCity` server-enforcement, remove `$request->input('cities', [])` block

---

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

---

## 2026-06-09 — Creator Profile Enhancement

### Files Modified
- `app/Http/Controllers/API/UserController.php` — validation, save, creator completion logic
- `app/Http/Controllers/API/CreatorMarketplaceController.php` — getProjectBids joins student_profiles
- `db_changes.txt` — Phase 6 migration appended

### DB Changes
```sql
ALTER TABLE `startyourstory`.`student_profiles`
  ADD COLUMN `qualification`       VARCHAR(50)  NULL AFTER `why_should_hire_you`,
  ADD COLUMN `availability_status` VARCHAR(50)  NULL AFTER `qualification`,
  ADD COLUMN `instagram_url`       VARCHAR(500) NULL AFTER `portfolio_url`,
  ADD COLUMN `website_url`         VARCHAR(500) NULL AFTER `instagram_url`;
```

### API Changes

**UserController::updateProfile()**
- Added validation rules: `qualification` (nullable|string|max:100), `availability_status` (nullable|string|max:100), `instagram_url` (nullable|string), `website_url` (nullable|string)
- Added to `$profileData` array: `qualification`, `availability_status`, `instagram_url`, `website_url`
- Updated creator profile completion check: requires `city` + `qualification` + `availability_status` + `why_should_hire_you` + `experience_years` + at least 1 `preferred_category` (was only `city`)

**CreatorMarketplaceController::getProjectBids()**
- Added `leftJoin('student_profiles', ...)` to bid query
- Returns per bid: `creator_qualification`, `creator_experience_years`, `creator_availability`, `creator_why_hire`, `creator_linkedin`, `creator_portfolio`
- Existing bid fields and response shape unchanged

### Rollback Plan
- Revert validation additions and `$profileData` new keys in `UserController::updateProfile()`
- Revert creator completion check to `!empty($request->city)` only
- Remove `leftJoin('student_profiles')` and new select columns from `getProjectBids()` query
- Run rollback SQL (see db_changes.txt Phase 6 rollback section)

---

## 2026-06-09 — Student Marketplace Access

### Files Modified
- `app/Http/Controllers/API/CreatorMarketplaceController.php` — `forbidNonCreator()` relaxed

### DB Changes
None.

### Changes
- **`forbidNonCreator()`**: Removed the `looking_for` check. Now returns 403 only when `user.role !== 'student'`. All student sub-roles (`articleship`, `semi-qualified`, `qualified`, `doing-articleship`, `creator`) can call all creator-side APIs: `submitBid`, `withdrawBid`, `getMyBids`, `getSelectedBidDetails`, `creatorRespondToBid`, `getMyEngagements`, `saveBankDetails`, `getPayoutStatus`, `getBidDetail`, `getMyEarnings`, `getBankDetails`, `submitDeliverable`, `requestRevision`, `approveDeliverable`.
- Firm-only APIs remain guarded by `FirmVerifiedMiddleware`; admin-only APIs remain guarded by their own middleware. No other guards changed.

### Rollback Plan
- Restore `$lf` lookup and `if ($lf !== 'creator')` block inside `forbidNonCreator()`, restore original docblock comment.

---

## 2026-06-09 — Creator Marketplace Open + Free Content Credits

### Files Modified
- `app/Http/Controllers/API/CreatorMarketplaceController.php` — `createProject()`
- `routes/api.php` — new routes
- `db_changes.txt` — Phase 5 migration appended

### New Files
- `app/Http/Controllers/API/FreeContentController.php`

### DB Changes
```sql
CREATE TABLE firm_content_credits (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    firm_id       BIGINT UNSIGNED NOT NULL,
    total_credits TINYINT UNSIGNED NOT NULL DEFAULT 3,
    used_credits  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_fcc_firm (firm_id)
);

CREATE TABLE free_content_requests (
    id             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    firm_id        BIGINT UNSIGNED NOT NULL,
    brief          TEXT NOT NULL,
    delivery_date  DATE NULL,
    notes          TEXT NULL,
    attachments    JSON NULL,
    status         ENUM('pending','confirmed','in_progress','delivered','rejected') NOT NULL DEFAULT 'pending',
    admin_notes    TEXT NULL,
    created_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

CREATE TABLE free_content_deliverables (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id   BIGINT UNSIGNED NOT NULL,
    file_path    VARCHAR(1000) NOT NULL,
    file_name    VARCHAR(500) NOT NULL,
    uploaded_by  BIGINT UNSIGNED NULL,
    created_at   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);
```

### Feature: Creator Marketplace Open to All Firms
- Removed `SubscriptionHelper::isPremiumFirm` check from `CreatorMarketplaceController::createProject()`
- All verified firms can now post projects to the marketplace (premium no longer required)

### Feature: Free Content Credits (FreeContentController)
- `GET /free-content/credits` — returns `total_credits`, `used_credits`, `remaining_credits` for premium firm; lazily initialises credits row (3 credits) on first access
- `POST /free-content/requests` — submit a free content request (brief, delivery_date, notes, attachments); validates premium + credits remaining; does NOT deduct credit on submission
- `GET /free-content/requests` — list own requests with deliverables for premium firm
- `GET /admin/free-content-requests` — paginated list for admin with firm_name, firm_city, deliverables
- `POST /admin/free-content-requests/{id}/confirm` — confirms request, deducts 1 credit from firm; only `pending` → `confirmed`
- `POST /admin/free-content-requests/{id}/status` — admin updates status to `in_progress` or `delivered`
- `POST /admin/free-content-requests/{id}/deliver` — admin uploads deliverable file (stored at `free-content-deliverables/`)
- `POST /admin/free-content-requests/{id}/reject` — rejects request; if `confirmed`, refunds the credit

### Rollback Plan
- Restore `SubscriptionHelper::isPremiumFirm` check in `CreatorMarketplaceController::createProject()`
- Remove `FreeContentController.php`
- Remove free content routes from `api.php`
- Drop tables: `free_content_deliverables`, `free_content_requests`, `firm_content_credits`

---

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

---

## 2026-06-09 — Branch FRN Read-only, Partner Count from Parent, Auto-discount Pricing

### Files Modified
- `app/Http/Controllers/API/FirmController.php` — `getFirmProfileDetails()`
- `app/Http/Controllers/API/PaymentController.php` — `createOrder()`

### DB Changes
None. Uses existing `firm_profiles.partners_count` and `firm_profiles.is_branch`.

### API Changes

#### Modified: `POST /getFirmProfileDetails`
- Added `parent_partners_count` to response
- When `is_branch = true`: fetches `partners_count` from the parent firm row and returns it as `parent_partners_count` (used to populate read-only Partners field on branch profile page)

#### Modified: `POST /payments/create-order` (Razorpay)
- When `$firmProfile->is_branch = 1`: applies 50% discount automatically before creating the Razorpay order
- The `firm_subscriptions` record stores the discounted amount (no manual intervention required)

### Rollback Plan
- Revert `getFirmProfileDetails()`: remove `$parentPartnersCount` variable and `parent_partners_count` key from response
- Revert `createOrder()`: remove the `if (!empty($firmProfile->is_branch))` discount block

---

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

---

## 2026-06-09 — Branch Registration & City-Based Candidate Visibility

### Files Modified
- `db_changes.txt` — Phase 4 migration appended
- `routes/api.php` — Added public route `/firm/lookup-by-frn`
- `app/Http/Controllers/API/AuthController.php` — `me()` method
- `app/Http/Controllers/API/FirmController.php` — `registerFirm()`, `getFirmProfileDetails()`, new `lookupByFRN()`
- `app/Http/Controllers/API/FirmDashboardController.php` — `getCandidates()`

### DB Changes
```sql
ALTER TABLE firm_profiles
    ADD COLUMN is_branch      TINYINT(1)      NOT NULL DEFAULT 0,
    ADD COLUMN parent_firm_id BIGINT UNSIGNED NULL,
    ADD COLUMN parent_frn     VARCHAR(50)     NULL,
    ADD INDEX  idx_fp_is_branch      (is_branch, parent_frn),
    ADD INDEX  idx_fp_parent_firm_id (parent_firm_id);
```

### API Changes

#### New: `GET /firm/lookup-by-frn?frn=<FRN>` (public, no auth)
- Returns parent firm name + city if found (`is_branch = 0`)
- Returns `{ status: false, message: "Parent firm not found" }` if not found

#### Modified: `POST /registerFirm`
- New optional fields: `is_branch` (boolean), `parent_frn` (string)
- When `is_branch=true`: validates parent FRN, auto-generates firm name as `"ParentName - City"`, stores `is_branch=1`, `parent_firm_id`, `parent_frn`

#### Modified: `GET /me`
- Firm users now receive: `is_branch`, `parent_firm_id`, `parent_frn`, `firm_city`

#### Modified: `POST /getFirmProfileDetails`
- Response now includes: `is_branch`, `parent_firm_id`, `parent_frn`, `city`, `associated_offices`
- `associated_offices`: populated only when `is_branch=1`; contains all sibling branches + parent firm row with same `parent_frn`

#### Modified: `POST /candidates` (FirmDashboardController::getCandidates)
- City filter is now **server-enforced** from `firm_profiles.city`
- Client-sent `cities` parameter is **ignored**
- Candidates must have firm's city in `student_profiles.preferred_location`

### Rollback Plan
```sql
ALTER TABLE firm_profiles DROP INDEX idx_fp_parent_firm_id;
ALTER TABLE firm_profiles DROP INDEX idx_fp_is_branch;
ALTER TABLE firm_profiles DROP COLUMN parent_frn;
ALTER TABLE firm_profiles DROP COLUMN parent_firm_id;
ALTER TABLE firm_profiles DROP COLUMN is_branch;
```
- Revert `FirmDashboardController::getCandidates()` city block to client-driven filter
- Remove `lookupByFRN()` method and `/firm/lookup-by-frn` route
- Revert `me()` select to exclude `is_branch`, `parent_firm_id`, `parent_frn`, `city`
- Revert `getFirmProfileDetails()` response to exclude branch fields

## Phase 8 — Student Creator Opt-In Flow

### Feature: Student Creator Opt-In (UserController, AuthController, CreatorMarketplaceController)

#### Modified: `app/Http/Controllers/API/UserController.php` — `updateProfile()`
- Added `'is_creator' => 'nullable|boolean'` to validation rules
- `$profileData` array: always sets `show_in_directory = true` (toggle removed from UI); saves `is_creator` from request or falls back to existing profile value
- After existing `$isProfileComplete` logic: if student opted-in as creator (`is_creator = true` and `looking_for !== 'creator'`), also checks that qualification, availability_status, why_should_hire_you, experience_years, and preferred_categories are all filled before marking profile complete

#### Modified: `app/Http/Controllers/API/AuthController.php` — `me()`
- `student_profiles` SELECT now includes `is_creator` column
- Response array includes `'is_creator' => (bool)($studentProfile->is_creator ?? false)`

#### Modified: `app/Http/Controllers/API/CreatorMarketplaceController.php` — `forbidNonCreator()`
- Now queries `student_profiles` directly for `looking_for` AND `is_creator`
- Allows access when `looking_for = 'creator'` OR `is_creator = 1`
- All 14 creator-side endpoint methods are covered automatically

### DB Change
```sql
ALTER TABLE `startyourstory`.`student_profiles`
  ADD COLUMN `is_creator` TINYINT(1) NOT NULL DEFAULT 0 AFTER `looking_for`;
UPDATE `startyourstory`.`student_profiles` SET `show_in_directory` = 1;
```

### Rollback Plan
- Revert `updateProfile()` validation to remove `is_creator` rule
- Revert `$profileData` to restore `show_in_directory` from request; remove `is_creator` field
- Remove the `$isCreatorOptin` block from `$isProfileComplete` logic
- Revert `me()` select to exclude `is_creator`; remove `is_creator` from response
- Revert `forbidNonCreator()` to original single-line check against `auth_user->looking_for`
```sql
ALTER TABLE `startyourstory`.`student_profiles` DROP COLUMN `is_creator`;
```

## Phase 9 — Student+Creator Hybrid Enhancements (2026-06-10)

No backend changes required for this phase.

### Coverage confirmation
- `forbidNonCreator()` in `CreatorMarketplaceController.php` already allows `is_creator = true` students through all bid endpoints (implemented in Phase 8)
- All bid routes (`GET /creator-marketplace/my-bids`, `POST /creator-marketplace/bids/{id}`, etc.) are already accessible to student+creators
- No DB schema changes needed

## Firm Candidate Filter — Include is_creator Opt-ins (2026-06-10)

### Modified: `app/Http/Controllers/API/FirmDashboardController.php` — `getCandidates()`
- "Registered For" filter: when `registered_for` includes `'creator'`, results now also include students with `is_creator = 1` even if their `looking_for` is not `'creator'` (grouped as `looking_for IN (...) OR is_creator = 1`)
- Aligns with the creator rule used by `forbidNonCreator()` in `CreatorMarketplaceController.php` (`looking_for = 'creator'` OR `is_creator = 1`)
- Behavior unchanged when `'creator'` is not among the selected `registered_for` values

### Rollback Plan
- Revert the "Registered For" block to the plain `whereIn('student_profiles.looking_for', $registeredFor)`

## Navigation & Permission Pass — Student / Student+Creator / Creator (2026-06-10)

No backend changes required.

### Authorization verification (existing coverage confirmed)
- All 11 creator-side endpoints in `CreatorMarketplaceController.php` (`submitBid`, `withdrawBid`, `getMyBids`, `getSelectedBidDetails`, `creatorRespondToBid`, `getMyEngagements`, `getBankDetails`, `saveBankDetails`, `getPayoutStatus`, `getBidDetail`, `getMyEarnings`) call `forbidNonCreator()` → returns 403 for non-students and for students with `looking_for != 'creator'` AND `is_creator = 0`; student+creators (`is_creator = 1`) are allowed
- Firm project/payment endpoints remain behind `ApiAuthMiddleware` + `FirmVerifiedMiddleware` — unchanged
- `GET /creator-marketplace/projects` and `/projects/{id}` remain intentionally public (browse without login, pre-existing design); page access is gated by frontend route guards
- No DB schema changes — nothing appended to db_changes.txt

## Admin Section Fixes — Directory Listings + Student Filters (2026-06-10)

### Feature
Implemented the missing admin directory list endpoints (root cause of the Firms 405 and Students 404 — the frontend POSTs `/admin/firms` and `/admin/students` but only `GET /admin/firms` (verification) existed and `/admin/students` was never built). Added backend-level student filters for email verification and profile completion.

#### Modified: `routes/api.php`
- Added `POST /admin/firms` → `AdminController@getFirms` (coexists with `GET /admin/firms` verification endpoint)
- Added `POST /admin/students` → `AdminController@getStudents`
- POST-for-list matches existing admin conventions (`/admin/subscriptions`, `/admin/wallet/recharges`)

#### Modified: `app/Http/Controllers/API/AdminController.php`
- New `getFirms()`: admin-token auth via `adminFromRequest()`; joins `firm_profiles` + `users` (role=firm, not deleted); `search` (firm_name/email/mobile), `city` filters; returns `plan` derived from `fp.is_premium`; shape `{firms, total}`
- New `getStudents()`: admin-token auth; joins `users` + `student_profiles` (role=student, not deleted); `search` (name/email/mobile), `city` filters
  - `email_verified` filter: `verified` → `email_verified_at IS NOT NULL`; `not_verified` → `IS NULL`; anything else → no filter
  - `profile_completion` filter: `completed` → `users.profile_completed = 1`; `incomplete` → `= 0 OR NULL` (uses the platform's existing completion flag)
  - All filters applied in SQL before `paginate(page_size)` (default 25, capped 100); response `{students, current_page, last_page, total}`

### API Changes
- `POST /api/admin/firms` body: `{search?, city?}` — 401 without valid `admin_token` cookie
- `POST /api/admin/students` body: `{search?, city?, email_verified?, profile_completion?, page?, page_size?}` — 401 without valid `admin_token` cookie

### DB Changes
None — no schema changes, nothing added to db_changes.txt.

### Known gap (pre-existing, not addressed)
`POST /admin/{firms|students}/{id}/toggle-active` called by the admin UI still has no backend, and `users` has no `is_active` column — needs a product decision (what deactivation enforces) before implementing.

### Rollback Plan
- Remove the two routes from `routes/api.php`
- Remove `getFirms()` and `getStudents()` from `AdminController.php`

## Student Profile — Resume Upload Made Optional (2026-06-15)

### Feature
Resume upload is now **optional** for students. They can complete their profile, reach `profile_completed = 1`, and apply for jobs/articleship without uploading a resume. Upload remains fully supported — students may upload, replace, view, or remove a resume at any time, and firms continue to see/download resumes for candidates who uploaded one.

#### Modified: `app/Http/Controllers/API/UserController.php` — `updateProfile()`
- **Removed the mandatory-resume business rule** that returned `"Please upload your resume."` (status `false`) for Semi-Qualified, Qualified, and Articleship→Inter-Both flows when no resume existed/was uploaded. Replaced the block with a comment documenting that resume is optional.
- **Removed resume from the profile-completion criteria**: dropped the `$resumeExists` variable and the two `&& $resumeExists` conditions in the Articleship (Case A) and Semi-Qualified/Qualified (Case C/D) completion calculations. Preferred-location, SRN, core domain, exposure, attempts, etc. remain required exactly as before.
- **Kept unchanged**: the `resume_path` validation rule (`nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`) — so when a file IS uploaded its type and size are still validated — and the resume storage logic (`storeAs('resumes', ...)`).

### Validation Rules
- `resume_path`: unchanged — `nullable|file|mimes:pdf,jpg,jpeg,png|max:5120`. No required rule was ever on the field itself; the requirement lived only in the business-logic block that is now removed.

### Behaviour
- Resume **not** uploaded → profile saves; completion flag is computed from the remaining (non-resume) criteria.
- Resume uploaded → type/size validated as before; file stored; firms can view/download it (no change to `FirmDashboardController`/`AdminController` read paths).
- No change to `registerStudent` (never required a resume) or to the job-apply flow (`JobsController` only reads `resume_path` for display).

### DB Changes
None — no schema changes, nothing added to db_changes.txt.

### Rollback Plan
- Restore the mandatory-resume block in `updateProfile()` (the `$resumeRequired` / `$hasExistingResume` check returning `"Please upload your resume."`).
- Restore the `$resumeExists` variable and re-add `&& $resumeExists` to the Case A and Case C/D completion conditions.

## Firm Verification Notification — Moved From Registration to Profile Completion (2026-06-15)

### Problem
The admin "new firm verification request" notification was created at **registration** (`registerFirm`). Because `firm_profiles.verification_status` defaults to `pending` for every signup, admins were notified about firms that often never completed their profile or pursued verification — flooding the notification feed (and FCM) with non-actionable items.

### Change
Notify admins only when a firm is **genuinely ready for review** — i.e. when it first **completes its profile**. There is no separate "submit verification request" endpoint in the system; a completed firm profile is the de-facto verification-submission event (the firm is already `pending` by DB default and appears in the admin pending list, but is only review-ready once its profile is complete).

#### Modified: `app/Http/Controllers/API/FirmController.php`
- **`registerFirm()`** — **removed** the `AdminNotificationService::firmVerification(...)` call (and the now-unused `$newFirmProfileId` lookup) that fired right after the registration `DB::commit()`. Registration, first login, and account creation now produce **no** admin notification. Email verification to the firm is unchanged.
- **`firm_profile_update()`** — **added** the `AdminNotificationService::firmVerification($firmName, $firmId)` call after `DB::commit()`, guarded so it fires exactly once when the firm becomes review-ready:
  - `$isProfileCompleted` is true (same flag already used to set `users.profile_completed`),
  - `!$wasAlreadyCompleted` — only on the incomplete→complete transition (read from the pre-update `auth_user->profile_completed`), so repeated profile edits never re-notify,
  - `verification_status === 'pending'` — never re-notify a firm already approved/rejected.

#### Modified: `app/Services/Notifications/AdminNotificationService.php`
- `firmVerification()` message body updated from "{firm} has registered and is awaiting verification." to "{firm} has completed its profile and is ready for verification review." Title (`'New firm verification request'`), type (`firm_verification`), action URL (`/admin/firms`) and metadata are unchanged.

### Not Changed (verified intact)
- `AdminNotificationService::create()` still stores the `AdminNotification` (notification center) **and** fans out via `FcmService::sendToAllAdmins()` — so Scenario 4 gets both the center entry and the FCM push, unchanged.
- `getPendingFirms()` / `approveFirm()` / `rejectFirm()` (admin review workflow) and the firm-side pending/rejected pages — untouched.
- `AdminNotificationController` (list / unread-count / mark-read) — untouched.

### Known limitation (deliberate, out of scope)
A firm that is **rejected** and later re-completes/fixes its profile will **not** generate a fresh notification (status is `rejected`, not `pending`). Re-review-after-rejection notifications were not requested; revisit if needed.

### DB Changes
None — no schema changes, nothing added to `db_changes.txt`.

### Rollback Plan
- Re-add the `firmVerification(...)` call + `$newFirmProfileId` lookup after the `DB::commit()` in `registerFirm()`.
- Remove the guarded `firmVerification(...)` block added after `DB::commit()` in `firm_profile_update()`.
- Revert the `firmVerification()` message body in `AdminNotificationService.php`.

## Registration — City mandatory (student) + Branch disables referral (firm) (2026-06-15)

### Task 1 — City mandatory for student registration
Previously `registerStudent()` never collected or stored a city (city was only captured later in `updateProfile`). Firm registration already required city; this brings students in line.

#### Modified: `app/Http/Controllers/API/UserController.php` — `registerStudent()`
- Added `'city' => 'required|string|max:255'` to the validator with a custom message **`'Please select your city.'`** (`city.required`). Registration without a city now returns `{status:false, message:'Please select your city.'}`.
- The `student_profiles` insert now stores `city` (and `address` = city, mirroring `updateProfile()`), so the student's profile is pre-filled and the existing city-dependent profile-completion logic starts consistent. No completion-logic code changed (it already gates on city).

### Task 2 — Branch firm registration must not participate in referrals
#### Modified: `app/Http/Controllers/API/FirmController.php` — `registerFirm()`
- Referral resolution is now skipped for branch registrations: `$referrerId = $isBranch ? null : ReferralHelper::resolveReferrerId(...)`. With `$referrerId` null for branches, **no `referred_by` linkage and no `referral_count` increment** occur, so `ReferralHelper::onFirmPremiumActivated()` later finds no referrer and **never creates a referral payout**. This is the backend enforcement (independent of the frontend disabling the field).
- Non-branch flow is unchanged — valid codes still link `referred_by` + increment `referral_count`; invalid/self codes are still dropped silently while registration proceeds.

### Tests executed (against dev DB)
- Student no city → **blocked** with "Please select your city." ✓
- Student with city → success; `student_profiles.city` stored ✓
- Firm (non-branch) + valid code → `referred_by` set, `referral_count` +1 ✓
- Firm (non-branch) + invalid code → registration succeeds, `referred_by` null ✓
- Firm (branch) + valid code → success, `referred_by` **null** (referral ignored, no count) ✓ — plus a non-branch control proving normal referral still links.

### DB Changes
None — no schema changes (`student_profiles.city`/`address` columns already exist). Nothing added to `db_changes.txt`.

### Rollback Plan
- `registerStudent()`: remove the `city` validation rule + custom message and the `city`/`address` keys from the `student_profiles` insert.
- `registerFirm()`: restore `$referrerId = ReferralHelper::resolveReferrerId(...)` unconditionally (drop the `$isBranch ? null :` guard).

---

## 2026-06-16 — Feature: Admin-managed manual payment destination (bank/UPI/QR)

Made the Premium Subscription page's **manual payment destination** (account holder, bank, account number, IFSC, UPI ID, QR code) admin-manageable instead of hardcoded in the frontend. Reuses the existing `system_settings` framework + `ImageHelper` (no new table, no new upload system). **PhonePe credentials/gateway/verification, plans, pricing, branch discount and subscription activation are entirely unchanged** — this is destination data only.

### New
- `app/Http/Controllers/API/PaymentSettingsController.php`
  - `instructions()` — **public** `GET /payments/instructions`. Returns the 6 destination fields (`qr_image` as an absolute `/storage` URL, or `''`). Reads via `SystemSettingService::get(...)` with `''` fallbacks; on any error returns empty strings (status `true`) so the payment page never crashes — it shows its own "details unavailable" fallback.
  - `uploadQr()` — **admin** `POST /admin/payment-settings/qr` (multipart `qr_code`). Validates `image|mimes:jpg,jpeg,png,webp|max:5120`, optimises to WebP via `ImageHelper::optimizeToWebp(..., 'payment-settings', 'public')`, stores the path in `payment_qr_code` (`SystemSettingService::set` → audited + cache-busted), then deletes the previous image.
  - `deleteQr()` — **admin** `DELETE /admin/payment-settings/qr`. Clears `payment_qr_code` and deletes the file.
  - Admin auth follows the existing `admin_token` → `admin_users` (active) pattern.

### Modified
- `routes/api.php` — registered the public `GET /payments/instructions` (public block) and the two admin QR routes (next to `/admin/system-settings`). The 5 text fields reuse the existing `POST /admin/system-settings/{key}` update route.
- `database/seeders/SystemSettingsSeeder.php` — added the 6 `payment` rows, seeded with the previously-hardcoded values so behaviour is preserved. `payment_qr_code` is `is_editable = false` (managed only by the upload endpoint; the generic text editor rejects it with 422).

### DB Changes
`db_changes.txt` (2026-06-16) — idempotent `INSERT … SELECT … WHERE NOT EXISTS` adding the 6 `system_settings` rows under category `payment` (no schema change; existing `system_settings` table reused). Run before deploy (or run `php artisan db:seed --class=SystemSettingsSeeder`). Rollback = `DELETE` those `setting_key`s from `system_settings` + `system_setting_audits`.

### Testing
- Seeder applied; `GET /payments/instructions` returns the 6 migrated values with `qr_image=''` (no QR uploaded yet). `php -l` clean; `route:list` shows all 3 routes.
- Verification: PhonePe initiate/verify/webhook untouched; plans/pricing untouched; branch discount math (frontend `floor(price/2)`) untouched; subscription activation untouched.

### Rollback Plan
- Remove the 3 routes + `PaymentSettingsController.php`; drop the 6 `payment` rows from `SystemSettingsSeeder`; run the `db_changes.txt` rollback `DELETE`. Frontend keeps working off the original mock instructions.

---

## 2026-06-16 — Feature: Admin Activity Logging (audit trail)

Added a meaningful admin audit trail that records ONLY important administrative WRITE actions (approvals, rejections, status / permission / money changes, content publish, settings changes, admin-user management). Read-only browsing — dashboard/list/search/filter/pagination/navigation — is deliberately NOT logged (no noise). Logs are append-only and retained indefinitely: there is no write/update/delete API for them.

### New
- `app/Services/AdminActivityLogger.php` — central, NON-THROWING recorder (`log($admin, $actionType, $entityType, $entityId, $description, $request)`). A logging failure is logged but never breaks the host action. Action-type constants for every event (firm_*, subscription_*, wallet_recharge_*, creator_payment_*, creator_payout_*, referral_payout_*, blog_*, report_*/warning_issued, *_settings_updated, admin_*). Captures admin id+name, IP and user agent. Modelled on the existing `AdminNotificationService` philosophy.
- `app/Http/Controllers/API/AdminActivityLogController.php` — READ-ONLY. `index()` (GET `/admin/activity-logs`) with filters admin_id, action_type, entity_type, date_from, date_to, search; paginated (50/page, newest first). `filters()` (GET `/admin/activity-logs/filters`) returns distinct admins/actions/entities for the UI dropdowns. No store/update/delete by design.
- Table `admin_activity_logs` — migration `2026_06_16_000001_create_admin_activity_logs_table.php` + idempotent SQL in `db_changes.txt`. Columns: admin_id, admin_name, action_type, entity_type, entity_id (string-safe for hashids), description, ip_address, user_agent, created_at. Indexed on admin_id, action_type, entity_type, created_at (the filter dimensions). No FK on admin_id (admins live in admin_users — project convention).

### Modified (instrumentation — additive log call on the SUCCESS path only)
- `routes/api.php` — registered the two read-only routes.
- `AdminController.php` — approveFirm, rejectFirm, approvePremiumRequest (subscription approved), rejectPremiumRequest, addSubscriptions (premium/subscription change), approveCreatorPayment, rejectCreatorPayment, updateReportStatus (moderation → report_reviewed / report_dismissed / warning_issued by status).
- `AdminWalletController.php` — approveRecharge (wallet credit), rejectRecharge.
- `AdminPayoutsController.php` — markPaid, markFailed, flushApproved, updateCommissionRate.
- `AdminReferralController.php` — approvePayout, markPayoutPaid.
- `AdminBlogController.php` — createBlog, updateBlog, publishBlog, unpublishBlog, deleteBlog (category/tag/topic intentionally NOT logged).
- `AdminSettingsController.php` — updateSetting (platform_settings_updated).
- `AdminSystemSettingController.php` — update (payment_settings_updated when key starts `payment_`, else platform_settings_updated).
- `PaymentSettingsController.php` — uploadQr / deleteQr (payment_settings_updated).
- `AdminUserController.php` — store (admin_created), update (admin_updated), destroy (admin_deleted), toggleActive (admin_enabled / admin_disabled).
- 29 log calls total. Each placed AFTER the mutation succeeds (after DB::commit where present) and only on the success branch — never on auth/validation/not-found/already-in-state early returns. No existing logic, responses, or validation changed.

Note: there are no admin Student approve/suspend/delete endpoints in the codebase (students are read-only in admin apart from moderation), so the spec's student events are covered via the moderation report workflow. Profile restricted/restored constants exist for forward use but no endpoint applies restrictions today.

### DB Changes
`db_changes.txt` (2026-06-16) — `CREATE TABLE IF NOT EXISTS admin_activity_logs` (+ indexes). Applied to the working DB. Rollback = `DROP TABLE IF EXISTS admin_activity_logs;`.

### Testing
- Table created; `AdminActivityLogger::log()` inserts the correct row shape (verified via tinker, then cleaned up). `php -l` clean on all 11 touched/new PHP files. `route:list` shows both read-only routes.
- Logged actions confirmed via code review of each instrumented success path; read endpoint paginates + filters by admin/action/entity/date/search.

### Rollback Plan
- Remove the two routes + `AdminActivityLogController.php` + `AdminActivityLogger.php`; delete the `AdminActivityLogger::log(...)` calls + `use App\Services\AdminActivityLogger;` imports from the 9 instrumented controllers; run the `db_changes.txt` rollback `DROP TABLE`.

---

## 2026-06-17 — Admin: View Firm endpoint, Delete Student, firm approval gating + Redis cache

Backend support for the admin-panel enhancements, plus switching the cache store to Redis. **No schema changes** (the student soft-delete columns already exist). PhonePe/plans/pricing/wallet/subscription logic is entirely untouched.

### New: `app/Http/Controllers/API/AdminController@getFirm`
- `GET /admin/firms/{id}` (admin auth via `adminFromRequest`). Joins `users` + `firm_profiles` and returns the full firm profile for the admin "View" modal — firm_name, frn, hr_name (contact person), firm_type, city, address, about, establishment_year, employees/partners/articles counts, exposure_type, services/industries, work_modes, training/stipend, all link fields, is_premium→`plan`, is_branch/parent_frn, verification_status, rejection_reason, logo→`logo_url`, plus `email_verified_at`→`is_verified` and `profile_completed`. Mirrors `getStudent`'s shape. 404 when not a firm.

### New: `app/Http/Controllers/API/AdminController@deleteStudent`
- `DELETE /admin/students/{id}` (admin auth). **Soft delete by design** — a student touches ~29 tables (student_profiles, applications, wallet/SYS-coin ledgers, referrals, creator engagements, messaging, …), several of them financial or firm-facing audit records, so a hard delete would orphan or destroy history. Instead, inside one transaction it sets `users.is_deleted = 1`, stamps `deletion_requested_at`/`scheduled_deletion_at = now`, clears `api_token`, and deletes the user's `user_sessions` rows (force logout). No related rows are deleted → no orphans. The account immediately disappears from the admin listing (which already defaults to `is_deleted = false`) and can no longer authenticate (auth resolution already filters `is_deleted = false`). Guards: 404 if not a student, 422 if already deleted. Logs `AdminActivityLogger::STUDENT_DELETED`.
- Reuses the existing account-deletion infrastructure (`is_deleted` + scheduled-deletion columns from migration `2026_06_12_000001`); a future finalizer can hard-purge rows past `scheduled_deletion_at`.

### Modified: `app/Http/Controllers/API/AdminController@getPendingFirms`
- The verification listing now also selects `u.email_verified_at`, `u.profile_completed` and a derived `is_verified` flag, so the admin Firms verification tabs can render the Email-Verified / Profile-Completed badge columns.

### Modified: `app/Http/Controllers/API/AdminController@approveFirm`
- **Approval gate**: a firm cannot be approved until its profile is complete. After loading the firm it now loads the firm's `users` row and returns `422 "Firm profile must be completed before approval."` when `profile_completed` is falsy. (`profile_completed` is maintained on the users row by `FirmController`.) All existing approve behaviour — email, activity log, transaction — is unchanged.

### Modified: `app/Services/AdminActivityLogger.php`
- Added the `STUDENT_DELETED = 'student_deleted'` action-type constant (no schema change — the table stores free-form `action_type`).

### Modified: `routes/api.php`
- Registered `GET /admin/firms/{id}` (after the existing `GET /admin/firms`) and `DELETE /admin/students/{id}` (next to the other student routes).

### Redis cache (cache store only — sessions deliberately left on file)
- `.env`: `CACHE_STORE=file` → `CACHE_STORE=redis`; `REDIS_CLIENT=phpredis` → `REDIS_CLIENT=predis` (the phpredis C-extension is not installed on this host, so the pure-PHP client is required). `SESSION_DRIVER=file` is **unchanged** — sessions, auth flow and login persistence are untouched, so logged-in users are unaffected.
- Installed `predis/predis ^3.5` via Composer (added to `composer.json`/`composer.lock`).
- This fixes the recurring `storage/framework/cache/data/… Failed to open stream` file-cache errors. The `RateLimiter` uses the default cache store, so the 10/min limiters now run on Redis automatically.

### DB Changes
- **None.** No new tables or columns; the soft-delete columns used by `deleteStudent` already exist. `db_changes.txt` unchanged.

### Testing
- `php -l` clean on `AdminController.php`, `AdminActivityLogger.php`, `routes/api.php`.
- `route:list` shows `GET api/admin/firms/{id}`, `DELETE api/admin/students/{id}` (and the existing firm/student routes intact).
- Redis: `php artisan tinker` → `config('cache.default') = redis`, `Cache::put/get` round-trips, store class `Illuminate\Cache\RedisStore`, `Redis::ping() = PONG`, `RateLimiter::hit/attempts` works on the redis store. `optimize:clear` then `config:cache` run clean; cached config still resolves `cache.default=redis`, `database.redis.client=predis`.
- Frontend `tsc --noEmit` reports no errors in the changed files; eslint shows only the project-wide `any` style + pre-existing line-ending issues (no new unused/undefined/unresolved errors).

### Rollback Plan
- Remove `getFirm`/`deleteStudent` from `AdminController.php`; revert the `getPendingFirms` select additions and the `approveFirm` profile-completion guard; remove the `STUDENT_DELETED` constant; drop the two routes from `routes/api.php`.
- Redis: set `CACHE_STORE=file` and `REDIS_CLIENT=phpredis` in `.env`, run `php artisan config:clear` (or `config:cache`); optionally `composer remove predis/predis`.
- No DB rollback required (no schema changes). To "un-delete" a soft-deleted student: `UPDATE users SET is_deleted=0, deletion_requested_at=NULL, scheduled_deletion_at=NULL WHERE id=?`.

> Note: the entries in this file are no longer in strict chronological order (a few 2026-06-16 entries sit below earlier ones). New entries should continue to be appended at the very bottom.

---

## 2026-06-17 — Admin: Delete Firm (soft delete with mandatory reason)

Mirrors `deleteStudent` for firms, and adds a **mandatory deletion reason** stored on a new `users.deletion_reason` column.

### DB
- Added `users.deletion_reason VARCHAR(500) NULL` (after `scheduled_deletion_at`). Migration `2026_06_17_000001_add_deletion_reason_to_users_table.php` (guarded by `Schema::hasColumn`) + idempotent `ALTER` appended to `db_changes.txt`. Applied to the working DB directly (the project's schema is hand-applied via `db_changes.txt`; `php artisan migrate` is not used because it would try to recreate the existing base tables).

### New: `app/Http/Controllers/API/AdminController@deleteFirm`
- `DELETE /admin/firms/{id}` (admin auth). **Soft delete by design** (same rationale as `deleteStudent` — a firm references firm_profiles, jobs, applications, subscriptions, conversations, branch links, several of them firm-facing/financial audit records). Requires a `reason` (`required|string|max:500`, 422 otherwise). Inside one transaction: sets `users.is_deleted = 1`, stamps `deletion_requested_at`/`scheduled_deletion_at = now`, saves `deletion_reason`, clears `api_token`, and deletes the firm's `user_sessions` (force logout). No related rows are deleted → no orphans. Guards: 404 if not a firm, 422 if already deleted. Logs `AdminActivityLogger::FIRM_DELETED` with the reason in the description.

### Modified
- `app/Services/AdminActivityLogger.php` — added `FIRM_DELETED = 'firm_deleted'`.
- `app/Http/Controllers/API/AdminController@getFirm` — now also returns `deletion_requested_at` and `deletion_reason` so the admin View modal can show them.
- `routes/api.php` — registered `DELETE /admin/firms/{id}` (between `GET /admin/firms/{id}` and the approve route).

### Testing
- `php -l` clean on `AdminController.php`, `AdminActivityLogger.php`, `routes/api.php`. `route:list` shows `DELETE api/admin/firms/{id}`.
- Column verified present via `Schema::hasColumn('users','deletion_reason')`.
- Frontend `tsc --noEmit` + eslint clean on the changed files (only the project-wide `any` style remains).

### Rollback Plan
- Remove `deleteFirm` from `AdminController.php`; revert the `getFirm` select additions; remove the `FIRM_DELETED` constant; drop the `DELETE /admin/firms/{id}` route.
- DB: `ALTER TABLE users DROP COLUMN deletion_reason;` (or `php artisan migrate:rollback` for that migration). To un-delete a firm: `UPDATE users SET is_deleted=0, deletion_requested_at=NULL, scheduled_deletion_at=NULL, deletion_reason=NULL WHERE id=?`.

> Scope note: `deleteFirm` deliberately does NOT deactivate the firm's existing job postings (kept consistent with `deleteStudent`, which doesn't auto-withdraw applications). The firm can no longer log in, but any active jobs remain until separately handled — flag if auto-deactivation is wanted.

---

## 2026-06-17 — Admin: Delete Student now requires a reason (parity with Delete Firm)

Retrofitted the mandatory deletion reason onto `deleteStudent`, reusing the `users.deletion_reason` column added earlier today (no new schema).

### Modified: `app/Http/Controllers/API/AdminController`
- `deleteStudent` — now validates `reason` (`required|string|max:500`, 422 otherwise) and stores it in `users.deletion_reason`; the reason is appended to the `STUDENT_DELETED` activity-log description. All other behaviour (soft delete, session invalidation, transaction) is unchanged.
- `getStudent` — now also returns `deletion_reason` so the admin View modal can display it.

### DB Changes
- None — reuses the `users.deletion_reason` column from the earlier "Delete Firm" change.

### Testing
- `php -l` clean on `AdminController.php`. Frontend `tsc`/eslint clean on the changed files.

### Rollback Plan
- Remove the `reason` validation + `deletion_reason` write from `deleteStudent`, and the `deletion_reason` select from `getStudent`. (Column rollback is covered by the Delete-Firm entry.)

---

## 2026-06-17 — Admin notifications: fire on firm premium purchase requests (FCM gap fix)

Firm premium purchase submissions created a `premium_requests` row but never created an admin notification, so admins got neither an in-app bell entry nor an FCM push (the dispatch only ever runs from `AdminNotificationService`). This wires that flow into the existing notification system.

### Modified: `app/Services/Notifications/AdminNotificationService`
- Added `TYPE_PREMIUM_REQUEST = 'premium_request'` (no schema change — `type` is a free-form column).
- Added typed helper `premiumRequest($firmName, $plan, $amount, $requestId, $firmId = null)` → title "Premium purchase request", action_url `/admin/premium-requests`, metadata `{premium_request_id, firm_id, firm_name, plan, amount}`. Same non-throwing path as the other helpers (stores notification + fans out via `FcmService::sendToAllAdmins`).

### Modified: `app/Http/Controllers/API/AdminController`
- `submitPremiumRequest` — after `DB::commit()`, calls `AdminNotificationService::premiumRequest(...)` with the firm name/plan/amount/new id. Non-throwing; never affects the submission response.

### Not changed (flagged)
- Student premium requests (`WalletController@submitPremiumRequest`, table `student_premium_requests`) still have no trigger, because there is **no admin review screen/endpoint** for that table yet — a notification would link to a screen that can't display it. Deferred pending an admin destination.
- Delivery still requires a registered admin device token (`admin_fcm_tokens`); the in-app bell works regardless.

### DB Changes
- None.

### Testing
- `php -l` clean on `AdminNotificationService.php` and `AdminController.php`.

### Rollback Plan
- Remove the `premiumRequest(...)` call from `submitPremiumRequest`, and the `TYPE_PREMIUM_REQUEST` const + `premiumRequest()` helper from `AdminNotificationService`.

---

## 2026-06-17 — Student premium requests: admin review screen + endpoints + notification trigger

Built the missing admin path for `student_premium_requests` (previously write-only), then wired the premium-request notification trigger to it. Closes the gap flagged in the prior entry. No schema change — `student_premium_requests` and `student_subscriptions` already exist.

### Modified: `app/Http/Controllers/API/AdminController`
- `getStudentPremiumRequests` — admin-auth (admin_token); lists `student_premium_requests` joined to `users` (name/email), Hashids-encoded ids, absolute screenshot URLs, newest first.
- `approveStudentPremiumRequest($id)` — decodes id, guards already-approved, computes expiry by plan (monthly→+1mo, quarterly→+3mo, yearly→+1yr), **upserts `student_subscriptions`** to `active` (one row per user — mirrors firm activation so `AuthController` reports the student as premium), marks the request approved (`admin_remarks`/`reviewed_by`/`reviewed_at`), logs `SUBSCRIPTION_APPROVED`.
- `rejectStudentPremiumRequest($id)` — marks rejected + remarks/reviewer, logs `SUBSCRIPTION_REJECTED`. No subscription change.

### Modified: `routes/api.php`
- `POST /admin/student-premium-requests` (+ `/{id}/approve`, `/{id}/reject`).

### Modified: `app/Services/Notifications/AdminNotificationService`
- Added `studentPremiumRequest(...)` helper (reuses `TYPE_PREMIUM_REQUEST`) → action_url `/admin/student-premium-requests`.

### Modified: `app/Http/Controllers/API/WalletController`
- `submitPremiumRequest` (student) — now fires `AdminNotificationService::studentPremiumRequest(...)` after insert (non-throwing), so admins get a bell entry + FCM push.

### Frontend
- New route `src/routes/admin.student-premium-requests.tsx` (mirrors the firm Premium Requests screen; Student/Plan/UTR columns).
- `src/services/api.ts` — `StudentPremiumRequest` type + `getStudentPremiumRequests` / `approveStudentPremiumRequest` / `rejectStudentPremiumRequest`.
- `src/components/admin-shell.tsx` — "Student Premium" nav link (Finance group, GraduationCap icon).

### DB Changes
- None.

### Testing
- `php -l` clean on all changed PHP files. `vite build` exits 0; the new route is registered in `routeTree.gen.ts`. (Pre-existing repo-wide `tsc` errors on `/firm/payments`/`/messages/` are unrelated and unchanged.)

### Rollback Plan
- Remove the 3 controller methods + 3 routes, the `studentPremiumRequest` helper + its call in `WalletController`, the new route file, the 3 api.ts functions + type, and the admin-shell nav link.

---

## 2026-06-17 — Security C1 + C2: centralized admin auth & hardened document download

Fixes two verified audit findings only (C1, C2). No payments/wallet/premium/creator/profile/registration logic touched.

### C1 — Centralized admin authentication
- **New:** `app/Http/Middleware/AdminAuthMiddleware.php` — validates `admin_token` → `admin_users` (must be `is_active`). Registered on the `api` group in `bootstrap/app.php`; it **enforces only on `admin/*` paths** (after stripping the `api/` prefix), exempts `admin/login`/`admin/me`/`admin/logout` and CORS `OPTIONS`, and is a no-op for all other routes. Guarantees every current + future `/admin/*` route is protected centrally, regardless of per-controller checks.
- Existing per-controller `admin_token` checks left intact as defense-in-depth.
- Closes unauthenticated access to `AdminMessagingController`, `ErrorLogController` (incl. destructive `DELETE /admin/error-logs`), `FreeContentController` admin methods, and `TrainingPartnerController@index`.
- Returns 401 (missing/invalid token), 403 (inactive admin).

### C2 — Document download (FirmDashboardController)
- `downloadFile` no longer accepts a client-supplied `path`. It now takes `student_id` + `type` (resume|marksheet), resolves the path from `student_profiles` (existing column values, existing `storage/app/public` location — **no file move/rename/migration**), blocks `..`/absolute/null-byte paths defensively, and writes a concise security audit log (`[ResumeDownload]` — user/role/student/type/result/reason) for success and every failure/blocked attempt.
- Business rule preserved: firms may download **without an application** (no application check added). `recruiter_actions` download log preserved.
- `candidateDetail` no longer returns `resume_path`/`marksheet_path`; instead returns `has_resume`, `has_marksheet`, `resume_ext`, `marksheet_ext`.
- Admin `downloadStudentFile` was already DB-resolved/safe — left unchanged.

### DB Changes
- None.

### Testing
- `php -l` clean on all changed files; `php artisan route:list` boots. Live: unauth `/admin/messaging/stats` & `/admin/error-logs` → 401; bad token → 401; `/admin/login` → 422 (reachable); public `/platform-settings` → 200. Inactive-admin → 403 (verified by code path).

### Rollback Plan
- Remove the `appendToGroup('api', AdminAuthMiddleware::class)` line in `bootstrap/app.php` and delete the middleware to revert C1. Revert `downloadFile`/`candidateDetail` in `FirmDashboardController` to restore prior C2 behavior.

---

## 2026-06-17 — Contact form fixed end-to-end + admin Feedback screen

The public contact form was broken three ways: the `contact_submissions` table didn't exist (insert 500'd), the contact notification linked to a non-existent `/admin/contact` route, and there was no admin screen to read submissions. Fixed all three.

### DB Changes
- Created table `contact_submissions` (schema was already authored in `db_changes.txt` lines 1247-1259 but had never been applied to the DB). **Production must run the same `CREATE TABLE`** — it is missing there too.

### Modified: `app/Http/Controllers/API/AdminController`
- Added `getContactSubmissions(Request)` — admin-auth'd, paginated list of `contact_submissions` with optional `search` (name/email/subject/message). Returns `{ submissions, total, page, has_more }`.

### Modified: `routes/api.php`
- `GET /admin/contact-submissions` (auto-protected by `AdminAuthMiddleware`).

### Modified: `app/Services/Notifications/AdminNotificationService`
- `contactSubmission(...)` action_url changed from the dead `/admin/contact` to `/admin/feedback` (the new admin screen). Docblock example updated to match.

### Testing
- `php -l` clean; `route:list` shows the new route. Live: contact submit → `status:true`, row stored in `contact_submissions`, `admin_notifications` row created with `action_url:/admin/feedback`; unauth `GET /admin/contact-submissions` → 401.

### Rollback Plan
- Remove `getContactSubmissions` + its route; revert the action_url to `/admin/contact`. (Leave the table — it's required by `submitContact` regardless.)

---

## 2026-06-18 — New Student Type: Already Doing Articleship

Introduces `looking_for = "already_doing_articleship"`. These students are enrolled in an articleship, not seeking jobs. They are excluded from firm candidate searches but appear in Creator Search if `is_creator = 1`.

### DB Changes
None — `looking_for` is a VARCHAR column; no migration required.

### Modified: `app/Http/Controllers/API/FirmDashboardController.php` (`getCandidates`)
- Before the Search block, checks whether the active query is targeting the Creator tab (`registered_for` includes `"creator"`).
- If NOT the creator tab: adds `WHERE student_profiles.looking_for != 'already_doing_articleship'` to exclude the new type from all non-creator candidate views (general list, all filter tabs, saved-only).
- If the creator tab IS active: no additional exclusion — the existing `whereIn('looking_for', ['creator']) OR is_creator = 1` filter already handles visibility correctly; `already_doing_articleship + is_creator = 1` students appear via the `is_creator` branch, while those without creator opt-in are still excluded.

### Modified: `app/Http/Controllers/API/UserController.php` (`updateProfile`) — completion logic
- **Bug fix:** the profile-completion branches had no case for `already_doing_articleship`, so `$isProfileComplete` stayed `false` and these students could never finish onboarding (permanently stuck on `/profile`). Extended the `doing-articleship` branch to also match `already_doing_articleship` — completion now requires Basic Info + SRN + current articleship firm (mirrors the ADA wizard: Basic Info + Experience, no Professional Status). The existing creator-opt-in extension block (adds creator-field requirements when `is_creator = 1`) already applies on top correctly.
- Apply-limit awareness modal now also suppressed for `already_doing_articleship` (alongside `creator`) — neither type applies for jobs.

### Testing
- `php -l` clean. `GET /candidates` (no filter): `already_doing_articleship` students absent from results. `GET /candidates?registered_for[]=creator`: `already_doing_articleship + is_creator=1` students present; `already_doing_articleship + is_creator=0` absent (not matched by either clause). Job seeker and pure creator results unaffected.
- `updateProfile` for `already_doing_articleship` with name/email/mobile/city/srn/current_firm_name → `profile_completed: 1`, `show_apply_limit_modal: false`. Missing current_firm_name → `profile_completed: 0`. With `is_creator=1` also requires creator fields. Other looking_for flows unaffected.

### Rollback Plan
- In `FirmDashboardController::getCandidates()`: remove the `$isCreatorTabActive` block and the `if (!$isCreatorTabActive) { $query->where(...) }` clause.
- In `UserController::updateProfile()`: revert the completion branch back to `=== 'doing-articleship'`, and remove the `&& $request->looking_for !== 'already_doing_articleship'` clause from the apply-limit modal guard.

---

## 2026-06-18 — Editable student name + welcome-bonus exclusion for Already Doing Articleship

Two targeted changes: (1) the profile Name field is now actually persisted; (2) `already_doing_articleship` students are excluded from the onboarding welcome bonus.

### DB Changes
None.

### Modified: `app/Http/Controllers/API/UserController.php` (`updateProfile`)
- **Name now persisted.** Previously the profile form's Name field was editable and validated on the client but never written server-side — `updateProfile` did not validate or store `name`, so edits were silently discarded. Added `'name' => 'required|string|min:3|max:100'` to the validator and now write `'name' => trim($request->name)` into the `users` table update (the same statement that sets `profile_completed`). Only caller of `/updateProfile` is the student profile form, which always submits `name`; profile-image upload uses the separate `/updateProfileImage` endpoint, so making `name` required does not affect it.

### Modified: `app/Helpers/SysCoinHelper.php` (`maybeGrantWelcomeBonus`)
- **Welcome bonus excluded for `already_doing_articleship`.** The method already fetched `registration_type`; it now selects `looking_for` in the same query and returns early (no grant) when `looking_for === 'already_doing_articleship'`, before the provisional-eligibility check. This is the single enforcement point for both callers (`updateProfile` on completion, and the email-verification path). No other reward path is touched: SYS Coin amounts, wallet logic, ledger entries, referral bonus (`maybeGrantStudentReferralBonus`), and notifications are all unchanged. Note: ADA students keep `registration_type = 'provisional'`, so without this guard they WOULD have received the bonus — this is the targeted exception.

### Testing
- `php -l` clean on both files.
- Name: `updateProfile` with `name = "New Name"` → `users.name` updated; `/me` reflects it. `name` length < 3 → `422` validation error. Other flows (creator, job seeker) save name unchanged.
- Welcome bonus: Job Seeker + Creator (provisional) → `WELCOME_BONUS` transaction created as before. `already_doing_articleship` (even provisional, profile complete, email verified) → no `WELCOME_BONUS` row, no coin credit. Referral bonus to a referrer who referred an ADA student is unaffected (separate method).

### Rollback Plan
- `UserController::updateProfile()`: remove the `'name' => 'required|...'` validator rule and the `'name' => trim($request->name)` line from the `users` update.
- `SysCoinHelper::maybeGrantWelcomeBonus()`: revert the `select('registration_type', 'looking_for')` back to `->value('registration_type')` and remove the `already_doing_articleship` early-return.

---

## 2026-06-18 — Error logs: raw error in `error_summary` + log errors only

Admins can now see the actual exception text (e.g. "Base table or view not found … Table 'x' doesn't exist") straight from the dashboard without opening `laravel.log`.

### DB Changes — ⚠️ MUST BE APPLIED MANUALLY
- `error_summary` widened **VARCHAR(100) → VARCHAR(1000)**. Migration `2026_06_18_000001_widen_error_summary_on_error_logs.php` (idempotent, raw `ALTER … MODIFY`); also appended to `db_changes.txt` and updated in `sys.sql`.
- The migration was **not run by this change** (the harness blocked `php artisan migrate` since prod/live status of this DB is unconfirmed). **Run it before deploying the code below**, via `php artisan migrate` or the `db_changes.txt` SQL: `ALTER TABLE error_logs MODIFY error_summary VARCHAR(1000) NULL;`

### Column semantics (changed)
- `error_summary` → **RAW** exception message: SQL is **kept** (that's the point — admins need to see the failing query/table), passwords/tokens/secrets are still **redacted**, single-lined, ≤1000 chars.
- `message` → unchanged: short, fully sanitized one-liner (SQL tail stripped, secrets redacted), ≤1000 chars.

### Modified: `app/Services/ErrorLogRecorder.php`
- Split the old `sanitize()` into `redactSecrets()` (secret-mask + whitespace-collapse, SQL untouched) and `sanitize()` (strip SQL tail, then `redactSecrets()`).
- New `rawMessage(Throwable)` → secret-redacted raw message (falls back to class name).
- `writeRow()` now takes `($safe, $raw, $status, $request)`: writes `message = $safe` (≤1000) and `error_summary = $raw` (≤1000, falls back to `$safe`).
- `recordLog()` computes a raw variant (`redactSecrets($message)`) alongside the sanitized one.
- Stack traces are still NEVER stored in the DB.

### Modified: `app/Http/Controllers/API/ErrorLogController.php` (`store`)
- Frontend rows now also set `error_summary = mb_substr($message, 0, 1000)` (their submitted message *is* the raw error) so the dashboard's Raw Error view is uniform across api/frontend rows. `index()` already searched `error_summary`.

### Config: `.env` — log errors only
- `LOG_LEVEL` changed `debug → error` and `php artisan config:clear` run. The `single`/`daily` channels use `env('LOG_LEVEL','debug')`, so only `error`/`critical`/`alert`/`emergency` now reach `laravel.log`. **`Log::info`/`debug`/`notice`/`warning` calls remain in code but produce no file output** — no call sites were deleted (reversible by flipping the env back).
- ⚠️ **Tradeoff:** this also suppresses `Log::warning` lines, including **PhonePe webhook signature-verification failures and payment no-credit warnings** (`PhonePeWalletController`, `PhonePeFirmController`, `PhonePeEngagementController`, `MessagingController`). If those warnings must stay visible, use `LOG_LEVEL=warning` instead.

### Testing
- `php -l` clean on `ErrorLogRecorder.php` and `ErrorLogController.php`. `config:clear` succeeded.
- Pending live verification after the column ALTER is applied: trigger a DB error (query a missing table) → `error_summary` holds the full `SQLSTATE… Table … doesn't exist` text (≤1000), `message` holds the sanitized one-liner; a `Log::info` no longer appears in `laravel.log`; a thrown 500 still does.

### Rollback Plan
- `.env`: set `LOG_LEVEL=debug` (or `warning`), then `php artisan config:clear`.
- `ErrorLogController@store`: remove the `error_summary` line.
- `ErrorLogRecorder`: restore the single `sanitize()` (inline the secret regex), revert `writeRow()` to `($safe, $status, $request)` with `error_summary = mb_substr($safe,0,100)`, drop `rawMessage()`/`redactSecrets()`.
- DB: `ALTER TABLE error_logs MODIFY error_summary VARCHAR(100) NULL;` (truncates rows > 100 chars) or `php artisan migrate:rollback`.


---

## 2026-06-19 — Blog: reusable social-media caption

Blogs can now store one reusable social caption (for WhatsApp / LinkedIn / Twitter-X). The admin UI copies a paste-ready post (caption + dynamic blog URL + default hashtags). The blog URL is generated from the slug at copy time and is **never stored**.

### DB Changes — ⚠️ MUST BE APPLIED MANUALLY
- New nullable column `blogs.social_caption TEXT NULL` (after `meta_description`). Migration `2026_06_19_000001_add_social_caption_to_blogs_table.php` (idempotent, `Schema::hasColumn` guarded). Also appended to `db_changes.txt`.
- The migration was **not run by this change** (prod/live status of this DB is unconfirmed). **Run it before deploying**, via `php artisan migrate` or the SQL: `ALTER TABLE blogs ADD COLUMN social_caption TEXT NULL AFTER meta_description;`

### Modified: `app/Http/Controllers/API/AdminBlogController.php`
- `getBlogs` (listing): added `blogs.social_caption` to the select so the listing "Copy Social Post" button has the caption without a per-row fetch.
- `getBlog` (detail): unchanged — already selects `blogs.*`, so `social_caption` is returned automatically.
- `createBlog` / `updateBlog`: added validation `social_caption => nullable|string` (no length cap, line breaks preserved) and persist `social_caption` — `$request->filled('social_caption') ? $request->social_caption : null`, so an emptied caption is stored as `NULL`. Value is stored raw (not trimmed) to preserve line breaks. Existing create/edit/tag/topic/image flows are unchanged.

### Not changed
- Public `BlogController` endpoints — the copy feature is admin-only, so `social_caption` is not exposed on the public API.

### Testing
- `php -l` clean on the controller and the migration.
- Pending live verification after the column ALTER is applied: create/edit a blog with a caption → value round-trips in `getBlog`; clearing it stores `NULL`; existing captionless blogs keep working.

### Rollback Plan
- Controller: remove the `social_caption` validation lines, the listing select column, and the two insert/update assignments.
- DB: `ALTER TABLE blogs DROP COLUMN social_caption;` or `php artisan migrate:rollback`.


---

## 2026-06-19 — Blog: expose social_caption on public blog API

Follow-up to the social-caption feature — the "Copy Social Post" action was moved to the **public** blog page's share row (next to WhatsApp/LinkedIn/X/Copy-link), so the caption must be returned publicly.

### Modified: `app/Http/Controllers/API/BlogController.php`
- `getPublishedBlogBySlug`: added `blogs.social_caption` to the select. Only published blogs are exposed (unchanged), so this leaks nothing draft-side. The blog URL is still generated client-side from the slug — not stored.
- `getPublishedBlogs` (listing) left unchanged — the caption is only needed on the single-post page.

### Rollback Plan
- Remove `blogs.social_caption` from the `getPublishedBlogBySlug` select.


---

## 2026-06-19 — Resume Builder — Phase 5 (drafts: save + get)

Backend for saving/loading a user's resume draft. New table + two query-builder endpoints; existing architecture untouched.

### DB Changes — ⚠️ MUST BE APPLIED MANUALLY
- New table `resumes` (one draft per user): `id`, `user_id` (UNIQUE `uq_resumes_user`), `template_key VARCHAR(50)`, `resume_data JSON`, timestamps. Migration `2026_06_19_000002_create_resumes_table.php` (guarded with `Schema::hasTable`). Also appended to `db_changes.txt` and `sys.sql`.
- Not run by this change (DB live status unconfirmed). **Run before deploying**: `php artisan migrate`, or the `db_changes.txt` SQL.

### New: `app/Http/Controllers/API/ResumeController.php`
- `getResume` (GET): returns the auth user's draft `{ template_key, resume_data (decoded), updated_at }` or `data: null` if none. Query-builder only; reads `auth_user` from request attributes (set by `ApiAuthMiddleware`).
- `saveResume` (POST): validates `template_key` (in the 4 allowed keys) + `resume_data` (`array`); **upserts** keyed by `user_id` (update if present, else insert with `created_at`); stores `resume_data` as `json_encode(...)`.

### Modified: `routes/api.php`
- Added `use App\Http\Controllers\API\ResumeController;` and, inside the existing `ApiAuthMiddleware` group, `GET /resume` + `POST /resume`.

### Testing
- `php -l` clean on the controller, migration, and `routes/api.php`.
- Pending live verification after the table is created: POST `/resume` with `{template_key, resume_data}` persists one row per user; GET `/resume` round-trips the decoded JSON; a second POST updates (not duplicates) the row.

### Rollback Plan
- Remove the two routes + the import from `routes/api.php`; delete `ResumeController.php`; `DROP TABLE resumes;` or `php artisan migrate:rollback`.


---

## 2026-06-19 — Resume Builder — Phase 6 (delete resume endpoint)

Adds resume deletion for the new "My Resume" dashboard. No DB/schema change (uses the existing `resumes` table from Phase 5).

### Modified: `app/Http/Controllers/API/ResumeController.php`
- New `deleteResume` (DELETE): removes the auth user's draft row (`DB::table('resumes')->where('user_id', …)->delete()`), returns `{status:true}`. Idempotent — succeeds even if no draft exists.

### Modified: `routes/api.php`
- Added `DELETE /resume` → `ResumeController@deleteResume` inside the existing `ApiAuthMiddleware` group (alongside the Phase-5 GET/POST).

### Testing
- `php -l` clean on the controller and `routes/api.php`. No migration required.

### Rollback Plan
- Remove the `DELETE /resume` route and the `deleteResume` method.


---

## 2026-06-19 — Resume Builder — Launch QA: backend PDF generation (mPDF)

Replaces the client-side `window.print()` download with **server-side PDF generation** (PART 3 mandate). No schema change (uses the existing `resumes` data shape).

### Dependency
- `composer require mpdf/mpdf` → **mpdf/mpdf 8.3.1** (pure-PHP, no Chromium/Node). Chosen over dompdf for far better table/colour/Unicode support. ⚠️ Run `composer install` on deploy so `vendor/` has mPDF.

### New: `resources/views/resume/pdf.blade.php`
- Server-side Blade replica of all 4 templates (Classic / Modern / Executive / Creative), authored to match the reference designs within mPDF's constraints (same section order, typography hierarchy, colours, spacing). mPDF has no flex/grid, so:
  - Single-column templates (Classic, Modern) use normal flow → paginate natively.
  - **Executive (sidebar)** and **Creative (two-column)** use **float layouts (not tables)** so the main/left column paginates across pages — fixes a clipping/cutoff bug where a tall two-column *table* row was truncated to one page.
- Edge cases: `word-wrap/overflow-wrap: break-word` everywhere; empty/optional sections omitted; respects `showPhoto`/`showCertifications`/`showAchievements`/`sectionOrder`. Executive photo → initials box (no binary asset needed).

### Modified: `app/Http/Controllers/API/ResumeController.php`
- New `downloadPdf` (POST): validates `template_key` + `resume_data`, normalizes via `normalizeResume()` (typed arrays, sane defaults, sanitized `sectionOrder`), renders the Blade, and streams an **A4** PDF (`Content-Disposition: attachment`). mPDF config: `format A4`, margins 0 (templates own their insets so full-bleed bands reach the edge), `default_font dejavusans` (₹/Unicode), `useSubstitutions`, `tempDir = storage/app/mpdf`.

### Modified: `routes/api.php`
- `POST /resume/pdf` → `ResumeController@downloadPdf` (inside the existing `ApiAuthMiddleware` group).

### Testing
- `php -l` clean. End-to-end render harness across **4 templates × 5 cases** (full / very-long / minimal / missing-optional / reordered): **all 20 render**, long content paginates to 2 pages on every template (verified Executive & Creative no longer clip), minimal/missing render cleanly. Output is vector/selectable text (sharp, recruiter-ready).

### Rollback Plan
- Remove `POST /resume/pdf` + `downloadPdf`/`normalizeResume`; delete `resources/views/resume/pdf.blade.php`; `composer remove mpdf/mpdf`. (Frontend would need its print path restored.)


