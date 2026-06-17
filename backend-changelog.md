# Backend Changelog

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

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
