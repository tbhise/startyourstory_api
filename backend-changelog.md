# Backend Changelog

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

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
