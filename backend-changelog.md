# Backend Changelog

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

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
