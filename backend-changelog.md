# Backend Changelog

> Append-only. Never delete previous entries. Date format: YYYY-MM-DD.

---

## 2026-07-04 — Push spam reduction: message aggregation, gates restored (tuned), interview collapse tags

Ends the 2026-07-03 TEST MODE (push on every message) and replaces it with the
final anti-spam design. Chrome/Android flags high-frequency per-site pushes as
spam; this reduces volume while losing zero information.

- **`MessagingController::pushToPeer` rewritten**:
  - **Aggregation**: every message increments a pending cache counter
    (`msg_push_agg_{conv}_{recipient}`, TTL 15 min). Suppressed messages are not
    lost — the next allowed push says "{n} new messages from {name}" (n>1) with
    the latest message as preview. Verified: msg1 → "New message from Rahul";
    msgs 2-3 suppressed; after cooldown expiry msg4 → "3 new messages from Rahul".
  - **Active suppression restored and raised 60s → 180s**
    (PUSH_ACTIVE_SUPPRESS_SECONDS). Rationale: shells poll every ~30s so an open
    tab keeps `last_activity_at` fresh either way; the raise only adds a grace
    period after the user leaves the site, avoiding "pushed about the chat I was
    literally just reading". Checked BEFORE the cooldown now — a skip no longer
    consumes the cooldown window (fixes the ordering flaw from the 2026-07-03 audit).
  - **2-min cooldown restored** (PUSH_COOLDOWN_SECONDS, atomic Cache::add).
  - Collapse tag `conv_{id}` unchanged. Verified: active recipient → 0 jobs
    queued, aggregate=2, cooldown NOT consumed.
- **Interview collapse tags** (webpush `tag` → newer replaces older in the tray):
  `interview_{inviteId}` on all 5 InterviewInviteController pushes
  (invite/respond/schedule/confirm/cancel) + SendInterviewResponseReminderJob;
  `interview_app_{applicationId}` on the 3 JobsController application-flow
  interview pushes (request/respond/reschedule-accept) + both interview reminder
  jobs (1h reminder replaces the 24h one). Two namespaces because invite ids and
  application ids can collide. applyJob push left untagged on purpose (distinct
  applicants must not replace each other). Digest logic untouched.
- **`UserPushService`**: webpush notification gains `badge` (Android status-bar
  glyph; currently the 192px app icon — a dedicated monochrome ~96px badge asset
  would render crisper, follow-up).
- **Verified**: php -l clean on all 7 files; tinker functional test of
  aggregation/cooldown/active-skip (transactional, rolled back).
- **Rollback**: restore pushToPeer from the TEST MODE entry below; drop the six
  `[], 'interview_…'` arg pairs; remove the badge line.

---

## 2026-07-03 — Push on EVERY notification-bell action (central hook)

The notification bell (`FirmDashboardController@getNotifications`, `POST /notifications`)
reads ONLY the `notifications` table, and every row there is written through
`NotificationHelper::create()`. So push is now hooked into that single helper: every
bell entry for a student/firm fires a `SendUserPushJob` — exactly once.

- **`app/Helpers/NotificationHelper.php`**: `create()` gains `bool $sendPush = true`
  and `?string $actionUrl = null`. When `$sendPush` (default), it dispatches
  `SendUserPushJob($userId, $title, $message, $actionUrl)` after the insert. Queued +
  non-throwing; no-op when the user has no device token / FCM unconfigured; safe inside
  DB transactions (database queue = job row commits with the txn).
- **Newly pushed (previously bell/email only)**: wallet credit/debit
  (AdminWalletController), support-ticket replies (Admin + SupportTicketController),
  referral payouts (AdminReferralController), admin-originated user notifications
  (AdminController), new message-request (MessagingController@startConversation, both
  directions), account-deletion notice to firm (UserController).
- **Dedup — 6 sites pass `$sendPush = false`** because they ALREADY dispatch a richer,
  deep-linked explicit push to the same user (kept, better copy):
  InterviewInviteController respond/confirm/cancel, JobsController@applyJob,
  SendInterviewResponseReminderJob, SendFirmApplicantReminderJob. Interview
  invite/schedule and application-status pushes ride the `recruiter_actions` feed
  (not `create()`), so they never doubled.
- **Verified** (tinker, transactional, rolled back): default `create()` → 1
  `SendUserPushJob` queued on `queue_jobs` + bell row written; `create(...,false)` →
  0 jobs queued + bell row still written. Syntax clean on all 5 touched files.
- **Note**: still requires a running queue worker to deliver; a focused recipient
  tab shows an in-app toast instead of an OS pop-up (FCM foreground behaviour).
- **Rollback**: revert the `NotificationHelper::create` signature/body and the six
  `false` args.

---

## 2026-07-03 — [TEST MODE — REVERT BEFORE PROD] message push on every message

Temporary change for end-to-end push testing. **`MessagingController::pushToPeer`**:
both anti-spam gates are commented out so EVERY chat message dispatches a push —
(1) the 2-min per-conversation cooldown and (2) the 60s-recently-active skip. The
`conv_{id}` collapse tag is retained, so browser notifications still coalesce.

- ⚠️ **Not for production.** Restore by un-commenting the two gate blocks (they are
  left in place, commented, with a `TEST MODE` banner) — this reverts to at most one
  push per conversation per 2 minutes and suppression while the recipient is active.
- No other logic touched; note that a FOCUSED recipient tab still shows a toast
  instead of an OS pop-up (FCM foreground behaviour), and delivery still requires a
  running queue worker.

---

## 2026-07-03 — SMTP stale-connection fix: ping_threshold + restart_threshold

Root cause of the production "421 4.4.2 smtp.hostinger.com Error: timeout exceeded"
errors (thrown at MAIL FROM inside DispatchMailJob): long-running queue workers keep
the SMTP connection alive between sends, and Hostinger's shared SMTP drops idle
connections — the next send then fails on the dead socket (and was retried by the
job's tries=3/backoff, so mail still delivered, but each incident logged an error).

- **`config/mail.php`** (smtp mailer, additive): `ping_threshold` = 30 (env
  `MAIL_PING_THRESHOLD`) — Symfony NOOP-pings a connection idle >30s and
  transparently reconnects if dead; `restart_threshold` = 50 (env
  `MAIL_RESTART_THRESHOLD`) — recycles the connection every 50 sends as a guard
  against per-connection message caps. Both are consumed by
  `EsmtpTransportFactory` from the DSN options Laravel already passes through.
- **Verified** (tinker + reflection on the built smtp transport):
  pingThreshold=30, restartThreshold=50 present on the EsmtpTransport instance.
- **Deploy note**: if config is cached in prod, run `php artisan config:cache`
  after deploying. Optional server hardening (not in repo): supervisor
  `--max-time=3600` on queue workers to recycle processes hourly.
- **Rollback**: remove the two config keys.

---

## 2026-07-03 — error_logs: skip-list for routine-noise messages

Four messages were flooding the admin error_logs table without representing real
failures. They are now excluded at BOTH ingestion points via a shared skip list
(`ErrorLogRecorder::MESSAGE_SKIP` + `shouldSkipMessage()`), matched exact
(case-insensitive, trimmed) so real errors that merely contain these words
(e.g. "Unauthorized webhook signature …") are still recorded:

- "Invalid token" / "Unauthorized" — expired-cookie 401s (frontend reports every
  API error; these are routine session expiries)
- "Send Verification Link API called" — an audit `Log::alert()` in
  UserController@sendVerificationLink, captured because `alert` is in
  ErrorLogRecorder's error-level list; it is not a failure
- "This action is disabled during admin impersonation (read-only mode)." —
  intentional 403 from BlockImpersonationWrites

Changes:
- **`app/Services/ErrorLogRecorder.php`**: `MESSAGE_SKIP` const + public
  `shouldSkipMessage()`; applied in `record()` (exceptions) and `recordLog()`
  (error-level log lines).
- **`app/Http/Controllers/API/ErrorLogController.php`** `@store`: same check for
  frontend-reported rows; still ACKs 201 so the fire-and-forget client (and its
  localStorage fallback) is unaffected. Backend-side filtering deliberately covers
  stale cached frontend bundles too.
- Everything else (sanitization, redaction, stack storage, admin endpoints)
  unchanged. laravel.log is NOT affected — full detail still lands there.
- **Verified** (tinker): all four messages skipped incl. case/whitespace variants;
  "Unauthorized webhook signature from gateway", "Network Error", and an SQLSTATE
  message still pass through and would be recorded.
- **Rollback**: remove the `MESSAGE_SKIP` block + the two call sites + the
  controller guard.

---

## 2026-07-03 — Attachment rule finalized: 5 files total, any mix, up to 5 PDFs

Documentation correction — NO validation logic changed. The final approved rule is:
max 5 attachments per message TOTAL, any combination of images and PDFs (5 images,
5 PDFs, or 3 images + 2 PDFs are all valid; 6 files always rejected). The code already
implemented this (`MessageAttachmentService::MAX_FILES = 5`, `MAX_PDFS = 5`; frontend
`ATT_MAX_FILES/ATT_MAX_PDFS = 5`) — the "at most 1 PDF" wording in the original
'Chat message attachments' entry below is superseded, and the stale service docblock
was updated to match.

- **Verified** (tinker, real GD PNGs + real PDFs through `validateSet`): 5 images →
  accepted; 5 PDFs → accepted; 3 images + 2 PDFs → accepted; 6 files (4+2) → rejected
  ("at most 5 files"); exe-disguised-as-png → rejected. Per-file size caps unchanged
  (images ≤5MB, PDF ≤10MB).

---

## 2026-07-03 — FINAL rule correction: student-initiated conversations also consume the free limit

Supersedes the "free limit counts ONLY firm-initiated conversations" decision in the
entry below. Final confirmed business rule: the free-firm conversation limit applies to
ALL new conversations in BOTH directions — firm-initiated AND student-initiated each
increment `messaging_limits.lifetime_conversations_started`. Existing conversations
remain unaffected (reply gate unchanged).

- **`MessagingController@startConversation`** (student branch):
  `MessagingHelper::incrementConversationsStarted($firmId)` restored after the message
  insert (unconditional, matching the firm-initiated branch — premium firms accrue the
  counter too; the gate just never enforces it for them).
- **`MessagingHelper`**: `canStartNewConversation` docblock + increment-counters comment
  updated to the final rule (no logic change there — the gate already counted both
  directions once the increment exists).
- **Verified** (tinker on dev DB, transactional dry-run, rolled back): limit=2, fresh
  free firm → student→firm OK (counter 1) → firm→student OK (counter 2) →
  firm→student blocked `free_limit_reached` → student→firm blocked `not_accepting`
  ("Firm Not Accepting Direct Messages"), counter stays 2 → reply in existing
  conversation still allowed at limit, counter unchanged → premium firm with counter=99
  passes both gates.
- **Rollback**: remove the one `incrementConversationsStarted` line in the student
  branch (comments cosmetic).

---

## 2026-07-03 — Messaging business rules finalized: gates rewritten

Implements the finalized rules: NEW conversations gated identically in both directions
(premium unlimited; free = `allow_free_firm_messaging` + under `free_firm_conversation_limit`);
EXISTING conversations are never blocked by policy — only by a closed conversation or an
inactive participant. Four decisions applied:

- **Request-unlock system removed completely.** Deleted `canFirmUnlockRequest`,
  `incrementRequestsUnlocked`, `FREE_LIFETIME_REQUESTS_UNLOCKED`, the
  `request_limit_reached` 403 in `getMessages`, and the unlock counting in `sendMessage`'s
  auto-accept. Free firms can always view + reply to student-initiated requests
  (also fixes the old inconsistency where a firm could reply via API to a request it
  couldn't view). `messaging_limits.lifetime_requests_unlocked` column remains but is
  no longer read/written.
- **`accept_direct_messages` toggle retired.** Removed from the student gate
  (`canStudentMessageFirm`), `acceptsDirectMessages()` + `getOrCreateSettings()` deleted,
  `POST /messaging/settings` route + `updateSettings` removed, field dropped from the
  `GET /messaging/settings` payload. `messaging_settings` table stays in the DB, unused.
  (Known cosmetic leftover: AdminMessagingController@getStats still counts that table.)
- **New `MessagingHelper::canSendMessageInConversation($conv)`** — existing-conversation
  gate used by `sendMessage`: blocks only `blocked`/`ignored` conversations or a
  soft-deleted participant (checks `users.is_deleted` for both sides). Also added
  `is_deleted` guards in `startConversation`: firm→deleted-student now 404s; student→
  deleted-firm-user returns the generic not-accepting message.
- **Free limit now counts ONLY firm-initiated conversations.** Removed
  `incrementConversationsStarted` from the student-initiated branch — inbound student
  leads never consume a free firm's quota (premium sells outbound outreach).
- **Renames/copy**: `firmCanHaveNewConversation`/`canFirmStartConversation` consolidated
  into `canStartNewConversation($firmId)` (all callers updated); student-facing message
  is now "Firm Not Accepting Direct Messages".
- **Frontend**: companies page Message button now gated on `can_start_conversation`
  (was `is_premium` alone — free firms under limit were wrongly shown as not accepting);
  `MessagingSettings` type + `updateMessagingSettings` removed.
- **Verified** (tinker on dev DB): premium → allowed; free under-limit + student → allowed;
  free over-limit → firm sees upgrade copy, student sees "Firm Not Accepting Direct
  Messages"; existing conv with active participants → allowed; blocked conv →
  `conversation_closed`; soft-deleted participant → `participant_inactive`. Routes:
  `POST /messaging/settings` gone, all others intact.
- **Rollback**: git-revert both repos' edits; no schema changes (columns retained).

---

## 2026-07-03 — Chat message attachments (images + PDF), additive layer

Students and firms can attach files to chat messages. Text-only messaging is untouched —
a plain JSON send takes the exact pre-existing code path. Approved V1 decisions applied:
originals preserved (NO WebP conversion — users send marksheets/offer letters), max 5
files/message in any mix with at most 1 PDF, and the websocket payload stays lightweight
(no attachment metadata broadcast).

- **`message_attachments`** (new table, migration `2026_07_03_000004`, mirrored in
  db_changes.txt): message_id, conversation_id (denormalised for 1-query download auth),
  type enum(image,pdf), file_path (server-only), original_name, mime_type, size_bytes,
  width/height (images — stable chat layout).
- **`app/Services/Messaging/MessageAttachmentService.php`** (new)
  - `validateSet()`: ≤5 files, images jpg/png/webp ≤5MB (content-sniffed MIME +
    `getimagesize()` sanity check), ≤1 PDF ≤10MB (`%PDF-` magic bytes). Rejects
    disguised executables (verified in test).
  - `store()`: ORIGINAL bytes untouched → private `local` disk at
    `message-attachments/{conv}/{40-char random}.{server-derived ext}`; inserts rows;
    on any failure deletes already-written files then rethrows (caller's txn rolls back).
  - `forMessages()`: client-safe payloads (never file_path) grouped by message_id.
  - `summaries()`: bell-preview + push-body strings ("📷 3 Photos" / "📄 Sent a document").
- **`app/Http/Controllers/API/MessageAttachmentController.php`** (new) +
  route `GET /messaging/attachments/{id}` (ApiAuthMiddleware): re-verifies conversation
  ownership per request, streams from the private disk with stored Content-Type,
  `X-Content-Type-Options: nosniff`, inline (or `?download=1`), `Cache-Control: private`.
- **`MessagingController@sendMessage`** (modified, additive): optional `attachments[]`
  multipart; text required only when no attachments (`''` stored otherwise — column is
  TEXT NOT NULL, no ALTER needed); attachment storage inside the existing transaction.
  `MessagingHelper` / events / notifyPeer are UNCHANGED — attachment-aware preview and
  push-body strings are passed through their existing string parameters. Realtime:
  attachment messages broadcast the generic marker `📎 Attachment` (V1 decision); the
  client refetches the thread on seeing it.
- **`MessagingController@getMessages`** (modified, additive): decorates rows with
  `attachments: []` via one whereIn query — old messages get an empty array.
- **Verified**: syntax clean; migration run; route registered; service e2e test (real GD
  PNG + PDF): valid set accepted, exe-disguised-as-png rejected, 2-PDF set rejected,
  originals preserved (.png kept), private-disk storage under the conversation folder,
  no file_path in client payloads, cleanup verified.
- **Deployment note**: nginx `client_max_body_size` must be ≥ 35m and PHP
  `upload_max_filesize`/`post_max_size` ≥ 10M/35M on the API server.
- **Rollback**: revert the two MessagingController blocks + route, remove service +
  controller + migration, DROP TABLE message_attachments, delete
  storage/app/private/message-attachments/.

---

## 2026-07-03 — Messaging notifications rearchitected: 3-hourly unread email + per-message push

Decision: for chat messages, PUSH is the realtime channel (2-min cooldown per
conversation+recipient, skip-if-active, collapse tag — see entry below) and EMAIL becomes
a slow backstop — one summary email per user at most every 3 hours while messages stay
unread. The old per-message `NewMessageReplyMail` send is REMOVED from
`MessagingController::notifyPeer` (one email per chat message no longer happens; the
mailable class itself is kept).

- **`app/Jobs/SendUnreadMessagesEmailJob.php`** (new) — hourly (`send-unread-messages-email`).
  Finds users with unread messages via the denormalised
  `conversations.candidate_unread_count` / `firm_unread_count` (status active/pending),
  skips anyone emailed < 3h ago, queues `UnreadMessagesReminderMail`, upserts throttle
  state. Reading messages zeroes the unread counters, which stops reminders automatically.
- **`app/Mail/UnreadMessagesReminderMail.php`** + view
  **`emails/messaging/unread-reminder.blade.php`** (new) — "You have {n} unread messages
  waiting in {m} conversations", CTA → /messages. Same template style + sender identity
  (EmailPurpose::MESSAGE_REPLY) as the per-message mail it replaces.
- **`user_message_email_state`** (new table, migration `2026_07_03_000003`, mirrored in
  db_changes.txt) — `user_id PK, last_sent_at`.
- **`MessagingController`** — per-message `Mail::to(...)->queue(NewMessageReplyMail)`
  removed from both branches of `notifyPeer` (comment points to the new job).
  `pushToPeer` anti-spam gates RESTORED after temporary test bypass (2-min cooldown +
  active-in-60s skip), with debug logging of every skip/dispatch decision.
- **Verified**: targeted migration run; `schedule:list` shows the job; transactional
  dry-run on dev DB (3 unread conversations → 2 reminder emails queued on first run →
  0 on immediate re-run (3h throttle) → rolled back cleanly).
- **Rollback**: restore the two Mail::queue blocks in notifyPeer, remove the job +
  schedule entry + mailable + view, drop `user_message_email_state`.

---

## 2026-07-03 — Push v2: dynamic copy, message push, smart unread digest

Follow-up to the initial push layer (entry below). Three additions, all additive;
bell/email/reminder logic still untouched.

- **Dynamic push copy everywhere** (no generic wording). Names moved into the TITLE:
  students see "*{firm_name}* invited you for an interview" / "…scheduled your interview"
  (with date · mode in the body) / "…accepted your new interview date"; firms see
  "*{student_name}* applied for {job_title}" / "…{accepted|declined} your interview invite" /
  "…confirmed the interview" / "…requested a new interview time" / "…cancelled the interview".
  Reminder jobs retitled the same way ("Interview tomorrow with {firm_name}", "{n} applicants
  are awaiting your review", "{firm_name} is waiting for your response").
- **Missing firm push added**: `JobsController@respondInterview` now also pushes on
  `Reschedule Requested` (title "…requested a new interview time", body "Proposed: {date}") —
  previously only Accepted/Rejected fired. Decision applied: NO push to a firm for its own
  reschedule-accept action (actor never gets pushed).
- **New-message push** (`MessagingController::notifyPeer` → new `pushToPeer()` helper):
  "New message from {name}" + 80-char preview → `/messages`. Anti-spam, in order:
  (1) atomic 2-min cooldown per conversation+recipient (`Cache::add`), (2) skip when the
  recipient was active in the last 60s (Reverb + focused-tab toast already cover them),
  (3) webpush collapse tag `conv_{id}` so bursts replace one notification instead of stacking.
  `UserPushService::sendToUser` + `SendUserPushJob` gained an optional trailing `collapseTag`
  param (backward-compatible).
- **Smart unread digest** — new `app/Jobs/SendUnreadDigestPushJob.php`, scheduled hourly
  (`send-unread-digest-push`). Sends "You have {n} unread notifications" (+ latest title
  teaser) only when ALL hold: has push tokens; unread > 0 (bell + role-scoped
  recruiter_actions); inactive > 6h; last digest > 8h ago (max 2/day by construction);
  unread count GREW since last digest; 09:00–21:00 IST (enforced in-job too). Throttle state
  in new `user_push_digest_state` table (migration `2026_07_03_000002`, mirrored in
  db_changes.txt). Collapse tag `digest_{user}`. Push-only — no bell insert, no email.
- **Verified**: syntax on all 12 touched files; targeted migration run; `schedule:list`
  shows the new job; digest dry-run on dev DB (baseline no-op → 1 push queued for an
  eligible user with correct dynamic payload → immediate re-run throttled → cleanup).
- **Rollback**: revert the dispatch-site copy edits, remove `pushToPeer` + its two call
  lines, remove the digest job + schedule entry, drop `user_push_digest_state`.

---

## 2026-07-03 — Push notifications for Students & Firms (additive layer)

Browser push (FCM) extended from admin-only to students and firms. Strictly additive:
every existing flow (in-app bell, `recruiter_actions`, email, reminder jobs, admin push)
is unchanged — trigger points only gain a queued push dispatch next to their existing
notification calls. Admin push infra (`FcmService`, `admin_fcm_tokens`,
`AdminNotificationService`, `/admin/fcm/token`) is untouched.

- **`database/migrations/2026_07_03_000001_create_user_fcm_tokens_table.php`** (new)
  - `user_fcm_tokens` — one row per device, `token` unique, keyed to `users.id`.
    Deliberately separate from `admin_fcm_tokens`. SQL mirrored in `db_changes.txt`.
- **`app/Services/Notifications/UserPushService.php`** (new)
  - FCM HTTP v1 sender for user devices. Same service account + OAuth token-cache slot
    as `FcmService`, but reads only `user_fcm_tokens` (query builder). Non-throwing,
    no-op when unconfigured, prunes dead tokens on 404/403/UNREGISTERED.
    OAuth mint duplicated (not shared) so the admin class needed zero modification.
- **`app/Jobs/SendUserPushJob.php`** (new)
  - Queued (database driver) wrapper around `UserPushService::sendToUser`. `tries = 1`,
    internally try/caught — a push failure can never fail a business flow or the queue.
    Dispatching inside DB transactions is safe: the job row commits atomically with them.
- **`app/Http/Controllers/API/UserPushController.php`** (new) + **`routes/api.php`** (modified)
  - `POST /fcm/token` (upsert by unique token — reassigns device to current user) and
    `DELETE /fcm/token` (scoped to token + user), inside `ApiAuthMiddleware` (cookie auth).
- **High-priority push bindings** (existing logic untouched; dispatch added alongside):
  - `InterviewInviteController` — invite() → student; respond() → firm (accepted/declined);
    schedule() → student; confirm() → firm (confirmed/reschedule); cancel() → firm.
  - `JobsController` — applyJob() → firm ("New application received", deep-link
    `/firm-jobs/{id}/applications`); interview request → student; respondInterview()
    → firm (Accepted/Rejected only); reschedule-accept → student.
  - `SendInterviewReminder24HoursJob` / `SendInterviewReminder1HourJob` — student push
    after successful email send (rides the existing `reminder_*_sent_at` duplicate
    protection; `applications.student_id` added to the select — additive).
  - `SendInterviewResponseReminderJob` / `SendFirmApplicantReminderJob` — push alongside
    the existing bell + email calls inside the same per-item try/catch.
  - Deep links: students → `/recruiter-actions` or `/my-applications`; firms →
    `/firm-applications`, `/firm-dashboard`, or `/firm-jobs/{id}/applications`.
- **Not bound yet** (deferred to a later phase): messages, ticket updates, wallet/payment,
  shortlist/select/reject status changes, marketplace events.
- **Rollback**: remove the `SendUserPushJob::dispatch(...)` blocks (each is a self-contained
  addition), the two `/fcm/token` routes, and the three new classes; drop `user_fcm_tokens`.
  No existing table or contract changed, so rollback has zero data impact.

---

## 2026-07-01 — Dedicated lightweight Career Status update API

The Career Status modal previously reused `POST /updateProfile`, which runs full-profile
validation. Changing status (e.g. to Qualified CA) could fail on an unrelated field with
errors like *"Please select your core domain."* New dedicated endpoint updates only
`student_profiles.looking_for` and recomputes `profile_completed` — no full-profile validation.

- **`app/Helpers/ProfileCompletionHelper.php`** (new)
  - `ProfileCompletionHelper::isComplete(array $f): bool` — extracted, single source of truth
    for the student `profile_completed` calculation. Same branch logic previously inlined in
    `UserController@updateProfile` (articleship / already_doing_articleship / semi-qualified /
    qualified / creator + creator opt-in extension). Caller normalises its own data source
    (request vs. stored DB row) into a flat array; the helper owns only the decision.
- **`app/Http/Controllers/API/UserController.php`** (modified)
  - `updateProfile()` — completion block refactored to call `ProfileCompletionHelper::isComplete()`.
    **No behavioural change** — identical inputs/result; only de-duplicated. External API contract
    (route, request, response, rewards) unchanged.
  - `updateCareerStatus()` (new) — handler for the new endpoint. Validates
    `looking_for ∈ {articleship, already_doing_articleship, semi-qualified, qualified}`, updates
    **only** `looking_for` on `student_profiles`, recomputes `profile_completed` from the stored
    profile (JSON columns normalised) via the shared helper, and mirrors updateProfile's idempotent
    SYS-coin grant on completion. No full-profile validation is performed.
- **`routes/api.php`** (modified)
  - Added `PATCH /student/career-status → UserController@updateCareerStatus` in the authenticated
    (no firm-verification gate) group. As a PATCH it is auto-blocked during admin impersonation by
    the existing `BlockImpersonationWrites` verb rule (parity with `updateProfile`).

---

## 2026-06-29 — exposure_type TEXT widen + error_summary stops storing SQL

Follow-up to the column/ErrorLogRecorder audit (this date). Two independent fixes.

- **`database/migrations/2026_06_29_000002_widen_exposure_type_to_text_on_firm_profiles.php`** (new)
  - `firm_profiles.exposure_type` `VARCHAR(500)` → **`TEXT`** (raw `ALTER ... MODIFY`,
    `Schema::hasColumn` guarded). Removes the truncation / "Data too long" (SQLSTATE[22001])
    risk on the JSON-array-as-text value. **Intentionally NOT migrated to native JSON yet**
    (would require refactoring the LIKE/whereIn filters — deferred). Run:
    `php artisan migrate --path=database/migrations/2026_06_29_000002_widen_exposure_type_to_text_on_firm_profiles.php`.
  - `down()` narrows back to `VARCHAR(500)` (truncates >500-char rows).
- **`app/Services/ErrorLogRecorder.php`** — `error_summary` no longer stores the SQL query.
  - Root cause was `error_summary` being sourced from `rawMessage()`/`redactSecrets()`, which
    kept the `(Connection: …, SQL: …)` tail. Now both `message` and `error_summary` store the
    **sanitized** message (`safeMessage()` → `sanitize()`: SQL/Connection tail stripped, secrets
    redacted), e.g. `SQLSTATE[22001]: Data too long for column 'exposure_type'`.
  - Cap aligned to the live `VARCHAR(1000)` column: new `SUMMARY_MAX = 1000`; removed the
    stale `RAW_MAX = 10000` (which silently truncated, or under STRICT mode threw → dropped the
    whole error row). **Column datatype left as VARCHAR(1000) per decision** (no migration).
  - `writeRow()` simplified to a single `$summary` param (writes it to both columns); `recordLog()`
    updated to match; unused `rawMessage()` removed. **`stack` storage kept unchanged** (full
    secret-redacted trace, TEXT, capped 15000) — still useful for debugging.
- **`app/Http/Controllers/API/FirmController.php`** — added an inline `// TECH DEBT` marker at the
  `whereIn('firm_profiles.exposure_type', …)` filter in `getCompanies()` (does not match a
  JSON-array text column). No behavior change — documented only.
- **`backend-audit.txt`** — new "TECHNICAL DEBT / KNOWN ISSUES" section (TD-1) describing the
  whereIn/exposure_type bug and the correct `whereJsonContains` + native-JSON fix for later.
- Verified: `php -l` clean on ErrorLogRecorder, FirmController, and the new migration.

---

## 2026-06-29 — "Last Login" tracking (denormalized last_login_at) for admin Students/Firms

Audit (this date) found student/firm logins were reliably recorded in the append-only
`login_history` table, but the admin listings had no cheap per-row "Last Login" value,
and **admins had no login tracking at all**. Decision: denormalize a `last_login_at`
column rather than join `MAX(login_history)` in list queries. `login_history` is unchanged
and remains the source-of-truth audit trail.

- **`database/migrations/2026_06_29_000001_add_last_login_at_to_users_and_admin_users.php`** (new)
  - `users.last_login_at` DATETIME NULL (after `email_verified_at`), index `idx_users_last_login_at`.
  - `admin_users.last_login_at` DATETIME NULL (after `is_active`), index `idx_admin_users_last_login_at`.
  - Idempotent (`Schema::hasColumn` guards); `down()` drops indexes + columns. Run:
    `php artisan migrate --path=database/migrations/2026_06_29_000001_add_last_login_at_to_users_and_admin_users.php`.
- **`app/Http/Controllers/API/AuthController.php`** — `login()` now stamps
  `users.last_login_at = now()` in the same block that inserts `login_history` (success only;
  NOT touched on profile edits). `updated_at` intentionally left alone.
- **`app/Http/Controllers/API/AdminController.php`**
  - `login()` now sets `admin_users.last_login_at = now()` explicitly alongside `api_token`
    (NOT reusing `updated_at`, which drifts on any admin row change).
  - `getStudents()` / `getFirms()` — select `u.last_login_at`; added an `activity` filter and
    a `sort` option via two new private helpers:
    - `applyActivityFilter($q, $col, $activity)` — buckets relative to `now()`:
      `active` ≤3d · `warm` 4–15d · `inactive` 16–45d · `dormant` >45d · `never` (NULL).
      Unknown/empty = no-op.
    - `applyLastLoginSort($q, $col, $sort, $defaultCol)` — `recent_login` / `oldest_login`
      (NULLs pushed last in both directions); otherwise default `$defaultCol DESC`
      (students `u.created_at`, firms `fp.created_at`).
  - `getPendingFirms()` — added `u.last_login_at` to the select so the firm verification tabs
    can display the column too.
- Verified: `php -l` clean on AuthController, AdminController, and the migration.

---

## 2026-06-28 — Admin subscriptions/premium-requests pagination + server-side filtering

Performance fixes for the `/admin/subscriptions` page (audit follow-up). Auth and
existing response keys preserved.

- **`app/Http/Controllers/API/AdminController.php`**
  - `getAdminSubscriptions()` — now reads `page` / `per_page` (default 20, clamped 1–100)
    from the request body and returns a paginated slice via `forPage()`. Total is computed
    with `(clone $query)->count()` **after** the search WHERE so pagination math respects the
    filter. Response keeps `subscriptions` and `total`, and adds `page` / `per_page` /
    `last_page`. NOTE: `total` previously held the `firm_profiles` count (never read by the
    UI); it now holds the real matching-subscription total. Search via `$request->search`
    unchanged — it now actually receives a value (see frontend fix).
  - `getPremiumRequests()` — now filters `WHERE pr.status = 'pending'` **server-side**
    (previously returned every status and let the client discard non-pending) and paginates
    (`page` / `per_page`, default 20). Response keeps `requests`, adds `total` / `page` /
    `per_page` / `last_page`. Manual admin-token auth block left intact.
- Verified: `php -l` clean; live runtime check — subscriptions `total=1/last_page=1`;
  premium pending `total=0` (the one existing request is `approved`, correctly excluded by
  the new filter vs. all-status count of 1).

---

## 2026-06-27 — Campaign stats endpoint (Phase 4)

- **`app/Http/Controllers/API/CampaignController.php`** — added `stats()`:
  `GET /admin/campaigns/stats` returns `{ total, pending, running, completed, failed }`
  (status counts over `campaigns`) for the Campaign page header cards.
- **`routes/api.php`** — registered `GET /admin/campaigns/stats` (admin-guarded; verified
  via `route:list` → 5 campaign routes). `php -l` clean. No other backend changes —
  the existing `GET /admin/campaigns` already returns `sent_count`/`failed_count`/
  `eligible_count`/`status`, so the UI's running-campaign polling needs no new fields.

---

## 2026-06-27 — Admin dashboard stats: student registrations + admin activity feed (Phase 3)

Additive backend support for the dashboard's reworked Recent Activity (no existing
fields removed → no breaking change for any consumer).

- **`app/Http/Controllers/API/AdminAnalyticsController.php`** — `dashboard()` now also
  returns under `recent`:
  - `students` — latest 5 student registrations (`users` role=student, not deleted):
    id, name, email, created_at.
  - `activity` — latest 6 rows from `admin_activity_logs` (id, action_type, description,
    admin_name, created_at). This is the curated admin audit feed, so it naturally
    surfaces **"Payment approved"** (subscription/wallet/creator approvals) and the new
    **`campaign_executed`** entries written in Phase 2.
  Existing `recent.{firms,premium,applications,recharges}` and all KPIs are unchanged
  (the frontend simply stops rendering some of them). `php -l` clean.

---

## 2026-06-27 — Admin Campaign module: backend refactor + APIs (Phase 2)

Refactors the re-engagement campaign out of the console command into a reusable
service, adds a queued campaign engine, secure admin APIs, and a `campaigns` run
record. Existing mail stack (ReEngagementMail, emails.reengagement view, EmailLog,
click tracking, EmailSenderResolver, DispatchMailJob) is untouched. **Run
`php artisan migrate` to apply the two new migrations.**

- **`database/migrations/2026_06_27_100001_create_campaigns_table.php`** (new) — `campaigns`
  run table: `campaign_type`, `campaign_name`, `target_type`, `verification_status`,
  `profile_completion_status`, `filters` (JSON, NOT NULL), counters
  (`eligible/sent/failed/opened/clicked_count`), `status`
  (pending|running|completed|failed), `initiated_from` (admin|cli|scheduler),
  nullable `executed_by_admin_id`, `started_at`/`completed_at`, indexes for the 24h
  duplicate guard + history.
- **`database/migrations/2026_06_27_100002_add_campaign_id_to_email_logs.php`** (new) —
  nullable `email_logs.campaign_id` (+ index) so per-campaign clicks roll up. Additive;
  existing transactional logs unaffected.
- **`app/Models/Campaign.php`** (new) — model + status/initiated_from constants; `filters`
  cast to array.
- **`app/Models/EmailLog.php`** — added `campaign_id` to `$fillable`.
- **`app/Services/Campaign/ReEngagementCampaignService.php`** (new) — single source of
  truth: `normalizeFilters`, `buildEligibilityQuery` (**users-only base + whereExists
  subqueries — no join**, so `lazyById` can't skip/duplicate; `student_profiles.user_id`
  has no unique key), `dryRun`, `recentDuplicate` (24h), `createCampaign`, `run`
  (chunked `lazyById(500,'users.id')`, **no sleep()**, per-recipient try/catch updates
  sent/failed + status), `sendTest`, and the segment `subjectFor` lifted from the command.
- **`app/Jobs/ProcessCampaignJob.php`** (new) — queued runner (`tries=1`, `timeout=3600`);
  idempotent (only runs a `pending` campaign); `failed()` marks the campaign failed.
- **`app/Http/Controllers/API/CampaignController.php`** (new) — `dryRun`, `test`, `send`,
  `index`. `send` enforces: 24h duplicate block (override `force=true`), server-side
  eligibility recount, >500 large-campaign `confirm=true` gate, then creates the campaign +
  dispatches the job + writes an `AdminActivityLogger::CAMPAIGN_EXECUTED` audit row.
- **`app/Services/AdminActivityLogger.php`** — new `CAMPAIGN_EXECUTED` action constant.
- **`routes/api.php`** — `POST /admin/campaigns/{dry-run,test,send}` + `GET /admin/campaigns`
  (auto-guarded by AdminAuthMiddleware; verified via `route:list`).
- **`app/Console/Commands/SendReEngagementEmails.php`** — now a **thin wrapper** over the
  service. Options: `--type`, `--verified`, `--profile`, `--dry-run`, **`--sync`** (default
  is queue). Creates a `campaigns` row per target type (`initiated_from=cli`) + audit log.
  Dropped `--queue/--limit/--sleep/--test` (queue is now default; per-recipient sleep removed).
- **`routes/web.php`** — **deleted** the public `GET /admin/send-reengagement` trigger
  (hardcoded `?key=` secret) entirely; removed its now-unused `Request` import. The
  `GET /e/click/{emailLog}` tracker now bumps `campaigns.clicked_count` on the first click
  of a campaign-linked log.
- **`db_changes.txt`** — DDL for the above.

Security: campaign execution is now admin-auth-only (no public/CLI-secret trigger).
Scalability: queue-based, chunked, no `sleep()`, no `$query->get()` of the whole base.
All files pass `php -l`; routes + command verified via artisan; eligibility SQL verified
via `toSql()` for all three target types. Open-tracking (`opened_count`) is reserved
(no open-pixel infra yet). **Not run:** `php artisan migrate` (left for you to apply).

---

## 2026-06-27 — Re-engagement campaign audit (NO code changes)

Pre-work audit for a future admin Campaign module. **No code, routes, or auth were
modified.** Findings recorded here for traceability:

- **Trigger:** single Artisan command `mail:reengagement`
  (`app/Console/Commands/SendReEngagementEmails.php`), reachable from the browser via
  `GET /admin/send-reengagement` (`routes/web.php`). No `CampaignController`, no campaign
  DB entity.
- **Auth gap:** that web route is gated **only** by a hardcoded `?key=sys-7f3a9` on a GET
  request — `AdminAuthMiddleware` is applied to the `api` group only (`bootstrap/app.php`),
  so the trigger has **no admin authentication**. (The `/api/admin/email-logs` viewer IS
  admin-guarded.)
- **Filters:** one query over `users ⟕ student_profiles`; base filter is only
  `is_deleted=0` + valid email. `email_verified_at` / `profile_completed` / type
  (student|firm|creator) are **opt-in** via `--verified/--profile/--type` (default targets
  the whole base — contradicts the class docblock). Firm verification/subscription state is
  **not** considered (no `firm_profiles` join).
- **Delivery:** synchronous `Mail::to()->send()` loop (or whole-command-on-queue via
  `&background=1`); **no chunking** (`$query->get()` loads all rows), hardcoded `sleep(1)`
  per recipient (~60/min). Synchronous run risks proxy timeout at scale (~83 min/5k users).
- **Logging:** per-recipient `email_logs` rows only (purpose `reengagement`); **no
  campaign-run record, no admin identity, no batch timestamp/aggregate**.
- **Recommendation:** Option C — extract logic into a `CampaignService` shared by the
  command + a new authenticated `POST /api/admin/campaigns/*`; add a `campaigns` table
  (admin_id, filters, counts, status, timestamps); move to chunked/batched queue jobs;
  retire the keyed GET trigger. Implementation deferred.

Scoped **only** to the student profile-image endpoint. Resume, certificate, and all
other uploads are untouched.

- **`app/Http/Controllers/API/UserController.php`** — `updateProfileImage()` validation
  tightened from `'profile_image' => 'required|image'` to
  `'required|image|mimes:jpg,jpeg,png,webp|max:4096'` (4096 KB = 4 MB), with custom
  messages: `max` → **"Profile image size must be less than 4 MB."**, `mimes`/`image` →
  "Profile image must be a JPG, PNG, or WEBP file.", `required` → "Please select a profile
  image to upload." The method's existing `try/catch (\Exception)` returns
  `$e->getMessage()`, and `ValidationException::getMessage()` surfaces the first (custom)
  rule message, so the frontend toast shows the exact text. Upload/move/DB-update flow and
  auth (`auth_user`) unchanged.

---

## 2026-06-27 — Re-engagement campaign: browser trigger route (temporary)

Lets the existing `mail:reengagement` command be fired from a URL (small base,
≤100 users, short-term internal use) instead of only the CLI. The command's
`handle()` is **untouched** — the route just maps query params onto its options.

- **`routes/web.php`** — added `GET /admin/send-reengagement` (mirrors the
  existing `/admin/cls` `Artisan::call` pattern). Added `Illuminate\Http\Request`
  import.
  - **Gate:** requires `?key=<secret>` (`env('REENGAGEMENT_MAIL_KEY')`, falling
    back to a hardcoded constant since `env()` is null under `config:cache`); 403
    otherwise.
  - **Safe by default:** without `?confirm=SEND` it forces `--dry-run` (sends
    nothing) — so an accidental/prefetched hit can't blast the base.
  - **Param → option map:** `type|verified|profile|limit` → `--type|...`,
    `test=1` → `--test` (redirects to TEST_EMAIL). The command still validates values.
  - **Synchronous by default:** `Artisan::call('mail:reengagement', $opts)`,
    returns `Artisan::output()` (the per-segment Sent/Failed summary). Lifts PHP's
    time limit (`set_time_limit(0)` + `ignore_user_abort(true)`) since the command
    pauses ~1s/email (~100s for 100 users).
  - **`background=1`** (only with `confirm=SEND`): `Artisan::queue(...)` runs the
    whole command on the queue worker and returns instantly — escape hatch if the
    FPM/nginx request timeout cuts off the synchronous run. Results land in `email_logs`.
- No change to `SendReEngagementEmails`, `ReEngagementMail`, the Blade view, or
  the `/e/click` tracking route. ⚠️ Temporary tool — remove or harden (admin auth)
  before long-term use; the secret rides in the query string (appears in access logs).

---

## 2026-06-26 — admin/firms: server-side pagination (approved response-shape change)

Converts `getFirms` (POST /admin/firms) from an unbounded `.get()` to
`paginate()`. **Approved breaking change to the response shape** (additive: adds
page/per_page/has_more; `firms` is now the current page). Filters, search, sorting,
and all firm fields are unchanged. Only the "All Firms" admin tab uses this endpoint;
the verification tabs use a separate route (GET /admin/firms → `getPendingFirms`).

- **`app/Http/Controllers/API/AdminController.php`** — `getFirms()`:
  - `->get()` → `->paginate($pageSize)`; `$pageSize = min(max((int) per_page, 1), 100)`,
    default 25 (same clamp pattern as `getStudents`). Page resolved from `?page=`.
  - Response now: `data: { firms, total, page, per_page, has_more }` (was `{ firms, total }`).
  - The `select` column list, all `where`/search filters, and `orderByDesc('fp.created_at')`
    are untouched.

**Validation (temp `perf:firms2` command, then deleted):**
- 24 firms; `per_page=5` → 5 pages, **no duplicate ids across pages**, concatenated
  order **== created_at DESC truth**, `has_more` correct on every page (true×4, false on last).
- `per_page=100` returns the full set, `has_more=false`.
- Filter totals match direct COUNTs: city=PUNE 17, verified 20, completed 15.

[PERFORMANCE — median of 5 runs, page 1]
```
Stage   Route         Response   Queries  DB time   Payload   Note
BEFORE  admin/firms   0.7 ms     2        0.54 ms   6.9 KB    unbounded .get() (returns ALL)
AFTER   admin/firms   1.0 ms     3        0.79 ms   6.9 KB    paginate(25); +1 COUNT query
```
At 24 firms both return the same rows (24 < 25 → one page), so payload is identical
today; the win is structural — payload/rows are now bounded to per_page regardless of
firm count, instead of growing unboundedly. The extra query is the paginator's COUNT.

Rollback: restore `->get()` and the `{ firms, total }` response in `getFirms()`, and
revert the frontend (see frontend changelog). `idx_fp_user_id` can stay.

---

## 2026-06-26 — Performance: admin/firms (getFirms) — firm_profiles.user_id index

Performance-only change. **No change to response structure, JSON payload, filters,
sorting, or business logic** — validated byte-identical across 5 payloads (empty /
search / city / verified / completed). The route's main scaling risk (unbounded
`.get()` returning ALL firms) is a **response-shape change (pagination) and was NOT
implemented** — escalated for sign-off instead (see below).

**Index added** (`db_changes.txt`, 26/06/2026, forward + rollback):
- `firm_profiles (user_id)` `idx_fp_user_id` — `firm_profiles` had no index on
  `user_id`. Backs the admin/firms join (`users.id = firm_profiles.user_id`) and,
  more importantly, the app-wide `DB::table('firm_profiles')->where('user_id',$id)->first()`
  lookup used by nearly every firm-authenticated endpoint. `user_id` verified unique
  (30/30 distinct, 0 null); kept as plain INDEX (not UNIQUE) to avoid future-insert risk.

**Audit / EXPLAIN:** `fp type=ALL` + `Using filesort` on `ORDER BY fp.created_at`;
`users` joined by PK (eq_ref, 1:1 — no row expansion). At 30 rows the optimizer still
scans `fp` (cheaper than switching join order), so `idx_fp_user_id` appears in
`possible_keys` but isn't chosen yet — it activates at scale and already serves the
per-request firm-by-user_id seeks elsewhere. The `filesort` is inherent to returning
the full ordered set and is not addressed by indexing.

[PERFORMANCE — median of 5 runs, empty payload]
```
Stage   Route         Response   Queries  DB time   Slowest   Payload
BEFORE  admin/firms   0.7 ms     2        0.54 ms   0.35 ms   6.9 KB
AFTER   admin/firms   0.8 ms     2        0.65 ms   0.44 ms   6.9 KB
```
Caveat: delta is noise at current volume. Index benefit is structural / app-wide.

**ESCALATED — NOT implemented (pagination):** `getFirms` returns every firm in one
unbounded `.get()`. The only real fix for large firm counts is pagination, which
changes the response from `{firms:[...all...], total}` to a paged shape and requires
admin-UI changes. Per task rules this is reported, not applied. Recommended approach
when approved: mirror the existing `getContactSubmissions` pattern in the same
controller — `paginate($pageSize)` returning `{firms, total, page, has_more}` — and
update the admin firms table to send `page`/`page_size` and consume `has_more`.

Rollback: `ALTER TABLE firm_profiles DROP INDEX idx_fp_user_id;` (db_changes.txt).

---

## 2026-06-26 — Performance: getCandidates (POST /candidates) indexes + is_saved rewrite

Performance-only change to the firm candidate-search feed. **No change to response
structure, JSON payload, filters, sorting, pagination, or business logic** —
validated byte-identical across 5 payloads (empty / search / saved_only / sort_name
/ page 2), including `is_saved` values and the `saved_only` result.

**Indexes added** (`db_changes.txt`, 26/06/2026, forward + rollback):
- `users (role, is_deleted, profile_completed)` `idx_users_role_deleted_completed`
- `student_profiles (user_id)` `idx_sp_user_id`, `(created_at)` `idx_sp_created_at`
- `recruiter_actions (firm_id, action_type, student_id)` `idx_ra_firm_action_student`
- `applications (applied_at)` `idx_applications_applied_at`

**Code change** — `app/Http/Controllers/API/FirmDashboardController.php` `getCandidates()`:
- **Old:** `is_saved` was a per-row correlated subquery in the SELECT
  `CASE WHEN EXISTS (SELECT 1 FROM recruiter_actions WHERE ... student_id = users.id AND firm_id = {id} AND action_type='candidate_saved') THEN 1 ELSE 0 END`
  — EXPLAIN showed `DEPENDENT SUBQUERY` (re-run per result row).
- **New:** a pre-aggregated derived table joined 1:1:
  `LEFT JOIN (SELECT student_id FROM recruiter_actions WHERE firm_id = {id} AND action_type = 'candidate_saved' GROUP BY student_id) AS saved_actions ON saved_actions.student_id = users.id`
  selected as `IF(saved_actions.student_id IS NOT NULL, 1, 0) as is_saved` — kept in
  the **same SELECT position** so output bytes are unchanged. `GROUP BY student_id`
  makes the join 1:1, so it cannot multiply rows or affect pagination/ordering.
- The `saved_only` `whereExists` filter and all other filters/sorts were left untouched.

**EXPLAIN before → after:**
- `users`: `type=ALL` (full scan, key=NULL) → `type=ref` `idx_users_role_deleted_completed`.
- `student_profiles`: `type=ALL` (hash join) → `type=ref` `idx_sp_user_id`.
- `recruiter_actions`: `DEPENDENT SUBQUERY` (per row) → `DERIVED` materialized once,
  covering `idx_ra_firm_action_student` (`Using index`).
- Residual (known, low-impact): `Using filesort` on `ORDER BY student_profiles.created_at`
  remains because `users` drives the join; removing it would require making
  `student_profiles` the leading table (out of scope / behaviour risk). It now sorts
  the filtered set, not the full table.

[PERFORMANCE — median of 5 runs, empty payload, firm_id=30]
```
Stage   Endpoint             Response   Queries  DB time   Slowest
BEFORE  POST /candidates     1.4 ms     3        1.14 ms   0.56 ms
AFTER   POST /candidates     1.5 ms     3        1.21 ms   0.52 ms
```
Caveat: at current volume (users=88, students=64) the wall-clock delta is noise; the
gains are structural — two full scans and a per-row dependent subquery eliminated, so
the route now scales with index seeks instead of O(students) scans + O(rows) subquery
executions. Query count unchanged (firm lookup + paginate count + paginate select).

Rollback: (1) restore the correlated-subquery SELECT and remove the `saved_actions`
LEFT JOIN in `getCandidates()`; (2) run the DROP INDEX statements in `db_changes.txt`.

---

## 2026-06-26 — AUDIT ONLY: Hot-route fetching bottleneck audit (no code changed)

Read-only audit of major data-fetching routes, ranked by **structural scaling
risk** (EXPLAIN + query-count), not current dev-DB speed. No code or schema was
changed. Structured per-route markers also written to `storage/logs/laravel.log`.
Current volume is tiny (users=88, jobs=12, firm_profiles=30, conversations=4,
messages=49) so live timings are sub-5ms noise — the findings below are about how
each route degrades as data grows.

### Top routes by scaling risk

| Rank | Route | Method | Severity | Gain | Risk to fix |
|---|---|---|---|---|---|
| 1 | POST /candidates | FirmDashboardController@getCandidates | **Critical** | Very High | Medium |
| 2 | POST /admin/firms | AdminController@getFirms | High | High | Medium |
| 3 | GET /admin/dashboard-stats | AdminAnalyticsController@dashboard | High | High | Low–Med |
| 4 | POST /admin/students | AdminController@getStudents | High | Medium | Low |
| 5 | GET /messaging/conversations | MessagingController@getConversations | Medium | Medium | Low |
| 6 | GET /admin/revenue-analytics | AdminAnalyticsController@revenue | Medium | Medium | Low |
| 7 | GET /getJobs | FirmController@getJobs | Medium | Medium | Low |
| 8 | GET /messaging/messages | MessagingController@getMessages | Low–Med | Low | Low |
| 9 | GET /blogs/public/{slug} | BlogController@getPublishedBlogBySlug | Low | Low | Low |
| 10 | POST /getCompanyDetails/{id} | FirmController@getCompanyDetails | Low | Low | Low |

### #1 getCandidates — the dominant structural bottleneck
EXPLAIN (firm scoped): `users type=ALL` (full scan), `student_profiles type=ALL`
(full scan, hash join), `recruiter_actions = DEPENDENT SUBQUERY` (the `is_saved`
`CASE WHEN EXISTS` re-evaluates per result row), `Using temporary; Using filesort`.
Root causes:
- No index serves `WHERE role='student' AND is_deleted=0 AND profile_completed=1`.
- Join `users.id = student_profiles.user_id` is **unindexed** (`student_profiles`
  has no `user_id` index) → hash join / scan.
- `ORDER BY student_profiles.created_at` has no index → filesort + temp table.
- `is_saved` correlated `EXISTS` + `saved_only` `whereExists` on `recruiter_actions`
  (no composite `(student_id, firm_id, action_type)` index).
- City/category filters use `whereJsonContains` (non-indexable); search uses
  leading-wildcard `LIKE '%..%'` (non-indexable).

### Other notable findings
- **admin/firms**: `.get()` with **no pagination** — returns *all* firms every call.
- **admin/dashboard-stats**: **14 sequential queries** (10 COUNT/SUM + 4 recent
  feeds). Not row-N+1, but several COUNT(*) scans over `users`/`applications`
  /`firm_subscriptions`/`wallet_recharges` that grow with the tables;
  `applications.applied_at` and `firm_subscriptions.created_at` are unindexed.
- **getConversations**: correctly bulk-fetches peers/requests (no N+1); minor:
  `select c.*` over-fetch + offset pagination + per-row `whereExists`.
- **getMessages / getCompanyDetails / candidateDetail / blogs**: bounded
  single-entity reads, low risk; a few `SELECT *` over-fetches (candidateDetail,
  admin blog detail).

### Recommended fixes (NOT applied — audit only)
**Quick wins (Low risk — indexes only, no logic/response change):**
- `users (role, is_deleted, profile_completed)` — getCandidates / admin students / dashboard counts.
- `student_profiles (user_id)` (join) and `student_profiles (created_at)` (sort).
- `recruiter_actions (student_id, firm_id, action_type)` — is_saved / saved_only.
- `applications (applied_at)`, `firm_subscriptions (status, created_at)`,
  `blogs (status, published_at)` — dashboard/analytics ranges.

**Medium-risk improvements (need validation / minor shape coordination):**
- Convert getCandidates `is_saved` correlated `EXISTS` → `LEFT JOIN recruiter_actions`
  (or batch-fetch saved IDs like getConversations does) to kill the per-row subquery.
- Paginate admin/firms (response-shape coordination with admin UI).
- Consolidate dashboard-stats COUNTs into fewer grouped queries.

**High-risk refactors (defer until volume justifies):**
- Replace `whereJsonContains` city/category filters with normalized columns or
  generated columns + indexes (schema + write-path change).
- Full-text index for search instead of leading-wildcard LIKE.

---

## 2026-06-26 — Performance: getCompanies current_openings via pre-aggregated derived table

Performance-only change to **`current_openings` calculation in `getCompanies` only**.
No change to filters, sorting, GROUP BY, GROUP_CONCAT, pagination, or response
shape. Validated byte-identical across 5 payloads (empty / city / search / premium
sort / page 2).

- **`app/Http/Controllers/API/FirmController.php`** — `getCompanies()`:
  - **Old:** per-row correlated subquery in the SELECT
    `(select count(*) from jobs where jobs.firm_id = firm_profiles.id and jobs.is_active = true) as current_openings`
    — re-executed once per result row.
  - **New:** a pre-aggregated derived table joined 1:1 on `firm_id`:
    `LEFT JOIN (SELECT firm_id, COUNT(*) AS current_openings FROM jobs WHERE is_active = 1 GROUP BY firm_id) AS job_counts ON job_counts.firm_id = firm_profiles.id`
    selected as `COALESCE(MAX(job_counts.current_openings), 0) AS current_openings`.
  - The derived table is GROUP BY firm_id (one row per firm) so the join is strictly
    1:1 — it does **not** multiply rows or duplicate the existing
    `GROUP_CONCAT(firm_departments.department_name)`.
  - `MAX()` is required (not the bare column) for MySQL `only_full_group_by`
    compatibility; since the join is 1:1 the value is constant within each group, so
    `MAX()` returns exactly the count. `COALESCE` preserves the prior 0-for-no-jobs.
  - `EXPLAIN` confirms the derived table is materialized once (`select_type=DERIVED`)
    rather than evaluated per row — the gain that scales as jobs/firms grow.

[PERFORMANCE — median of 5 runs, empty payload]
```
Stage   Endpoint            Response   Queries  DB time   Slowest
BEFORE  POST /getCompanies  2.6 ms     2        1.47 ms   0.75 ms
AFTER   POST /getCompanies  2.5 ms     2        1.41 ms   0.75 ms
```
Caveat: at current volume (firm_profiles=30 approved=18, jobs=12) the delta is
within run-to-run noise — the benefit is structural (one aggregation vs N
subquery executions) and materializes as data grows. No DB schema change; the
`idx_jobs_active_status_created` index (added 2026-06-26) now serves the derived
table's `WHERE is_active=1 GROUP BY firm_id`.
Rollback: restore the correlated subquery SELECT and remove the job_counts LEFT JOIN.

---

## 2026-06-26 — Performance: hot-endpoint indexes (getCompanies / getJobs)

Performance-only change. **No business logic, query structure, filter, sort,
pagination, field, or response-shape change.** Query count is identical
before/after (getCompanies=2, getJobs=3).

- **`db_changes.txt`** — added 5 indexes (forward + rollback documented):
  - `firm_profiles (verification_status, is_premium, created_at)` `idx_fp_verif_premium_created`
    — matches `WHERE verification_status='approved'` + `ORDER BY is_premium DESC, created_at DESC`.
  - `firm_profiles (firm_name)` `idx_fp_firm_name`.
  - `jobs (firm_id, is_active)` `idx_jobs_firm_active` — covers the `current_openings`
    correlated subquery and firm-scoped job lookups.
  - `jobs (is_active, status, created_at)` `idx_jobs_active_status_created` — matches
    the public job-feed `WHERE` + `ORDER BY`.
  - `firm_departments (firm_id)` `idx_fd_firm` — covers the `leftJoin`.
- **`current_openings` correlated subquery: intentionally NOT rewritten.** A grouped
  JOIN would fan out the existing `GROUP_CONCAT(firm_departments.department_name)` and
  corrupt the response. The subquery + `idx_jobs_firm_active` is the safe choice.
- **No controller code changed.** A temporary `app/Console/Commands/PerfBench.php`
  was used to capture the benchmarks below, then deleted.

[PERFORMANCE — median of 5 runs, identical empty payloads]
```
Stage   Endpoint            Response   Queries  DB time   Slowest
BEFORE  POST /getCompanies  2.9 ms     2        1.80 ms   0.92 ms
AFTER   POST /getCompanies  2.7 ms     2        1.60 ms   0.81 ms
BEFORE  GET  /getJobs       1.4 ms     3        0.76 ms   0.31 ms
AFTER   GET  /getJobs       1.5 ms     3        0.84 ms   0.35 ms
```
Caveat: at current data volume (firm_profiles=30, jobs=12) the delta is within
run-to-run noise — these indexes are **preventative future-proofing**, not a fix
for present slowness. `EXPLAIN` confirms getCompanies already selects
`idx_fp_verif_premium_created` + `idx_fd_firm`; getJobs still full-scans 12 rows
(below the optimizer's index threshold) but now lists the new indexes in
`possible_keys`, so it switches automatically as the table grows.
Rollback: `DROP INDEX` statements in `db_changes.txt` under the 26/06/2026 block.

---

## 2026-06-25 — Messaging: expose firm is_premium in firm messaging status

- **`app/Http/Controllers/API/MessagingController.php`** — `getFirmMessagingStatus`
  (`GET /messaging/firm/{firmId}/status`) now also returns `is_premium`, derived
  from `SubscriptionHelper::isPremiumFirm($firmId)` (the active-subscription source
  of truth, not the denormalized `firm_profiles.is_premium`). Powers the
  candidate-side "Message" button, which is now gated strictly on the firm being
  premium (see frontend changelog). `accept_direct_messages` /
  `can_start_conversation` / `existing_conversation_id` unchanged.

---

## 2026-06-25 — Messaging: accept direct messages ON by default (toggle removed)

The per-firm "Direct Message Preference" toggle was removed from the firm profile
page (see frontend changelog). Firms now accept candidate direct messages by
default, so the default flips from opt-in (false) to opt-out (true).

- **`app/Helpers/MessagingHelper.php`**:
  - `getOrCreateSettings()` — auto-created `messaging_settings` rows now insert
    `accept_direct_messages = true` (was `false`).
  - `acceptsDirectMessages()` — fallback when no row exists is now `true` (was
    `false`), so a firm without a settings row also accepts messages.
  - No change to `canStudentMessageFirm()` / `firmCanHaveNewConversation()` — the
    firm-policy + admin free-limit gates still apply on top of this toggle.
- **`db_changes.txt`** (2026-06-25) — `ALTER TABLE messaging_settings MODIFY
  accept_direct_messages TINYINT(1) NOT NULL DEFAULT 1` + `UPDATE messaging_settings
  SET accept_direct_messages = 1` to flip all existing firms to accepting. Rollback
  SQL included.
- `updateMessagingSettings` / `getMessagingSettings` endpoints remain (still used by
  admin messaging surfaces); only the firm-profile toggle UI and the default changed.

---

## 2026-06-25 — Messaging Phase 3: realtime via Laravel Reverb

REQUIRES: `composer require laravel/reverb` then run the Reverb server
(`php artisan reverb:start`). Set the REVERB_* env values (a real key/secret/app_id).

- **`bootstrap/app.php`** — added `channels: routes/channels.php` to `withRouting` and
  `->withBroadcasting(routes/channels.php, ['middleware' => [ApiAuthMiddleware::class]])` so the
  `/broadcasting/auth` endpoint runs under the app's cookie auth.
- **`app/Http/Middleware/ApiAuthMiddleware.php`** — additionally calls
  `$request->setUserResolver(fn () => $user)` so `$request->user()` works for broadcasting channel
  authorization (existing `auth_user` attribute consumers unchanged).
- **`routes/channels.php`** (new) — `user.{userId}` (self) and `conversation.{conversationId}`
  (participant-only: candidate, or the firm's owning user). Reuses the firm↔student model.
- **`config/broadcasting.php`** + **`config/reverb.php`** (new) — reverb connection + server config.
- **`app/Events/`** (new, all `ShouldBroadcastNow` — no queue worker needed):
  - `MessageSent` → `conversation.{id}` (live thread append).
  - `MessageRead` → `conversation.{id}` (peer flips sent bubbles to "Seen").
  - `ConversationUpdated` → `user.{id}` per participant (list reorder/preview + global badge
    via `total_unread`).
- **`app/Http/Controllers/API/MessagingController.php`** — dispatches after commit:
  `sendMessage`/`startConversation` → `broadcastMessage()` (MessageSent + ConversationUpdated to
  both participants); `getMessages`/`markRead` → `broadcastRead()` (MessageRead + ConversationUpdated
  to the reader). All dispatches are wrapped in try/catch — if Reverb is down, messaging still works.
- **`.env`** — `BROADCAST_CONNECTION=reverb` + REVERB_* placeholders.
- Builds directly on Phase 2 denormalized counters (events carry preview + per-side unread, no scans).

---

## 2026-06-25 — Messaging Phase 2: denormalized counters for scalability (no Reverb)

- **`db_changes.txt`** — `conversations` gains `last_message_id`, `last_message_preview`,
  `last_message_sender_type`, `firm_unread_count`, `candidate_unread_count` + backfill of all
  five from `messages`. No new index needed (existing `idx_conv_firm`/`idx_conv_candidate`
  cover `firm_id|candidate_id + status`).
- **`app/Helpers/MessagingHelper.php`**:
  - `applyMessageSent()` — on every persisted message, refreshes the conversation's
    last-message snapshot and bumps the **recipient's** unread counter (single UPDATE).
  - `applyConversationRead()` — zeroes the **reader's** unread counter.
  - `getUnreadCount()` rewritten to `SUM(side_unread_count)` over the user's active/pending
    conversations — **no messages-table scan** (was a COUNT join on messages).
- **`app/Http/Controllers/API/MessagingController.php`**:
  - `formatConversation()` now reads denormalized columns only (dropped the per-row
    last-message query + per-row unread COUNT). Accepts injected peer/request for bulk use.
  - `getConversations()` **bulk-resolves** peers (firms or candidates+profiles) and request
    statuses with `whereIn` maps — eliminates the N+1 across the page. `unread` tab filters on
    the counter (`*_unread_count > 0`) instead of a `whereExists` messages scan.
  - `sendMessage()` + both `startConversation()` first-message inserts now `insertGetId` +
    `applyMessageSent()`. `getMessages()`/`markRead()` call `applyConversationRead()` after
    marking peer messages read.
- API response shapes unchanged (`last_message.{message,sender_type,created_at}`, `unread_count`)
  except `last_message.message` is now a ≤255-char preview — frontend already shows a preview, so
  no frontend change. Realtime/Reverb still deferred to Phase 3.

---

## 2026-06-25 — Messaging Phase 1: admin-controlled firm messaging policy

- **`app/Helpers/MessagingHelper.php`** — replaced the hardcoded free-3-lifetime /
  premium-100-monthly model with an admin-driven policy:
  - `allowFreeFirmMessaging()` + `freeFirmConversationLimit()` read `platform_settings`
    (`allow_free_firm_messaging` default true, `free_firm_conversation_limit` default 2).
  - `firmCanHaveNewConversation()` — premium = unlimited; non-premium = allowed only if free
    messaging is on AND lifetime conversations < limit.
  - `canFirmStartConversation()` now delegates to it (firm-initiated; premium UNLIMITED — the
    old monthly cap is gone). `canStudentMessageFirm()` = firm policy **AND** `accept_direct_messages`
    toggle, collapsing all failures to "Not Accepting Direct Messages" (no premium leak).
  - Premium gate applies to **new conversations only**; `sendMessage`/existing convos untouched.
- **`app/Http/Controllers/API/MessagingController.php`** — candidate-initiated `startConversation`
  now uses `canStudentMessageFirm()` (was bare `acceptsDirectMessages`) and increments the firm's
  lifetime conversation counter (student-initiated convos now count toward the firm's free limit).
  `getFirmMessagingStatus` now returns `can_start_conversation` (effective gate).
- **`app/Http/Controllers/API/AdminSettingsController.php`** — registered
  `allow_free_firm_messaging` + `free_firm_conversation_limit` (defaults + allowed-keys validation).
- **`db_changes.txt`** — optional seed of the two `platform_settings` rows.
- Existing conversations remain fully usable regardless of premium/limit (continue is never gated).

---

## 2026-06-25 — Premium source-of-truth + race-safe invites + dead-state transitions

- **`app/Helpers/SubscriptionHelper.php`** — `isPremiumFirm()` no longer trusts the
  denormalized `firm_profiles.is_premium` fast-path (never reset on expiry → expired
  firms kept bypassing every free-action gate). Premium is now derived **only** from an
  ACTIVE, non-expired `firm_subscriptions` row (plan IN premium/-monthly/-quarterly/-yearly,
  status active, `expires_at` NULL or future). Verified all four real plan values are covered
  (admin writes `premium`; PhonePe writes the three hyphenated tiers). Fixes expired-premium bypass (P1).
- **`app/Http/Controllers/API/AuthController.php`** — `/me` (getCurrentUser) firm `is_premium`
  now derives from `SubscriptionHelper::isPremiumFirm()` instead of the stale
  `firm_profiles.is_premium` flag, so an expired-premium firm's UI (upgrade banners etc.)
  is consistent with the now-fixed gates. Student premium logic unchanged.
- **`app/Http/Controllers/API/InterviewInviteController.php`** —
  - **Race-safe invites (P2):** insert now sets `active_flag = 1` and is wrapped in a
    `QueryException` catch; a lost race hits the new unique index and returns `409 invite_exists`
    instead of creating a duplicate. `decline` clears `active_flag` to NULL.
  - **Dead-state transitions (P2):** new `complete()` (firm; scheduled/confirmed → completed)
    and `cancel()` (firm OR candidate; any active state → cancelled). Both clear `active_flag`,
    update the linked `recruiter_actions.action_status`, and free the pair for a future invite.
    Candidate-cancel notifies the firm bell.
- **`routes/api.php`** — `POST /interview-invites/{id}/complete` (firm group),
  `POST /interview-invites/{id}/cancel` (shared auth group; firm or candidate).
- **`db_changes.txt`** — `interview_invites.active_flag TINYINT NULL` + backfill +
  `UNIQUE INDEX uq_active_invite (firm_id, student_id, active_flag)` (partial-unique via NULLs).

---

## 2026-06-24 — Sitemap: add /resume-builder to static pages

- **`app/Http/Controllers/API/SitemapController.php`** — added `/resume-builder`
  to `STATIC_PAGES` (changefreq monthly, priority 0.7), so it appears in the
  backend-served `/sitemaps/static.xml`. `staticUrlCount()` (admin health check)
  picks up the new count automatically.

---

## 2026-06-24 — Modern Minimal resume PDF: fix Education section layout

- **`database/migrations/2026_06_24_000010_modern_minimal_education_layout_fix.php`**
  (new) — **the actual PDF fix.** The downloaded PDF for modern_minimal renders
  from the ACTIVE `resume_templates` DB row (ResumeController::renderTemplateHtml),
  not from the blade — so the blade edit alone never reached the PDF. The DB row's
  education block still used the original single right cell rendering
  `trim($e['year'].' '.$e['score'])`, i.e. Duration + Score **concatenated into
  one cell** ("May - 2019 97" — the exact merge reported). The migration now sets
  the whole `.modern` body to a known-good template string (deterministic — no
  cross-section regex) with: a stable full-width two-row Education table
  (`width:100%; border-collapse`; right cells `text-align:right;
  white-space:nowrap; padding-left:14px`) so Duration and Score sit on separate
  rows/cells; `.ed-score` highlighted (#475569 → #0f172a); and Skills as a
  one-per-line bullet list (matching the editor preview + Classic/Premium blades).
  Verified: `@if`/`@endif` (10/10) and `@foreach`/`@endforeach` (7/7) balanced and
  the template renders. Idempotent; Modern Minimal only.
  NOTE: an earlier draft of this migration used a greedy `.*?</table>` regex that
  over-matched from the Skills table through Education and stripped intervening
  Blade directives, causing a transient `unexpected "endforeach"` compile error;
  the deterministic full-set replacement above is the fix and supersedes it.
- **`resources/views/resume/pdf.blade.php`** (Modern Minimal case only) — same fix
  applied to the fallback blade (used when no active DB row exists): `.modern
  .ed-table` (`width:100%; border-collapse`) + `.ed-left` / `.ed-right` cell
  classes (right cell: `text-align:right; white-space:nowrap; padding-left:14px`);
  `.ed-score` highlighted. Classic / Premium Minimal blades untouched.

---

## 2026-06-24 — Batch: firm int validation, resume skills, premium-first companies, subs null fix, impersonation read-only

- **`app/Http/Controllers/API/FirmController.php`**
  - `firm_profile_update()` — added integer validation for `employees`,
    `partners`, `articles` (`nullable|integer|min:0`) with friendly messages, so
    decimals/ranges/text are rejected server-side (mirrors the frontend guard).
    Empty values are handled by Laravel's ConvertEmptyStringsToNull middleware.
  - `getCompanies()` — **premium firms first**: added
    `orderBy('firm_profiles.is_premium', 'DESC')` as the PRIMARY sort before the
    chosen/default sort, so premium firms surface first while within-group order
    is preserved (no-op when no premium firms exist).
  - `getJobs()` — added `id DESC` tiebreaker after `created_at DESC` for stable
    newest-first dashboard job ordering.
- **`resources/views/resume/pdf.blade.php`** & **`resume/premium_minimal.blade.php`**
  — Skills now render as a one-per-line bullet list (matching Certifications /
  Achievements) for Classic, Modern and Premium Minimal, so the downloaded PDF
  matches the new editor preview. Premium Resume blade left intact (template
  disabled in the UI only).
- **`app/Http/Controllers/API/AdminController.php`** — `getAdminSubscriptions()`:
  fixed the "Viewing null" root cause. `firm_subscriptions.firm_id` is stored
  under two conventions (admin-assigned → `users.id`; payment/PhonePe →
  `firm_profiles.id`); the list now resolves the firm under BOTH via aliased
  left-joins + `COALESCE` so `firm_name` / `firm_email` are never null. Search
  updated to match across both aliases. No data migration.
- **`app/Http/Middleware/BlockImpersonationWrites.php`** — extended the
  impersonation read-only deny-list with `resume` (saveResume), `resume/pdf`
  (download) and `payout-details` (add/edit). `resume/preview-html` stays allowed
  so the admin can still view the resume; DELETE /resume already blocked by the
  verb rule.

Note: `/admin/notifications` (AdminNotificationController@index) already orders
`created_at DESC, id DESC` — verified, no change needed.

---

## 2026-06-24 — Public companies directory gated to approved firms

- **`app/Http/Controllers/API/FirmController.php`** — `getCompanies()` (POST
  `/getCompanies`, backs the students' All Companies page) now adds
  `where('firm_profiles.verification_status', 'approved')` to the base query, so
  only admin-approved firms are returned. Pending/rejected firms no longer
  surface in the directory.
- **`app/Http/Controllers/API/FirmController.php`** — `getCompanyDetails()`
  (POST `/getCompanyDetails/{id}`, backs the company detail page) now also
  requires `verification_status = 'approved'` (plus `users.is_deleted = false`),
  so a pending/rejected firm reached via direct URL returns "Company not found".

---

## 2026-06-24 — User-auth migration to user_sessions only — backend

Migrated ALL user authentication (students / firms / creators) to resolve
exclusively through the `user_sessions` table. The legacy hybrid path —
`users.api_token` / `users.token_expires_at` — is no longer read or written by
any user-facing code. **Admin auth is unchanged** (`admin_users.api_token` +
`admin_token` cookie). Impersonation (a `user_sessions` row) now works across
every endpoint, since nothing self-resolves `api_token` anymore.

Cutover note: low user count + forced re-login is acceptable. Existing logins
already had a `user_sessions` row, so most users stay logged in; any token that
only existed in `users.api_token` (pre-sessions) is now rejected → re-login.

- **`app/Helpers/AuthHelper.php`** (NEW) — single source of truth for "who is the
  logged-in user". `resolveUser($request)` / `resolveUserId($request)` reuse the
  `auth_user` attribute set by `ApiAuthMiddleware` when present, else resolve the
  `auth_token` cookie against `user_sessions` (covers optional-auth / public routes
  outside the middleware group). Never reads `users.api_token`. Expired sessions are
  deleted and treated as unauthenticated.
- **Phase 1 — controllers off `users.api_token`:** replaced every self-resolving
  `DB::table('users')->where('api_token',$token)` block with `AuthHelper::resolveUser`:
  - `JobsController` (10 sites); `UserController` (updateProfile, getProfile,
    dismissApplyLimitModal, requestAccountDeletion, updateDirectoryVisibility,
    trackRecruiterAction, reportStudentProfile; plus the two Eloquent
    `User::where('api_token')` sites — sendVerificationLink, verificationStatus —
    now resolve the id via session then load the Eloquent model by id);
  - `FirmController::getJobs` (optional-auth preserved); `ReferralController::index`
    (also dropped the now-redundant `token_expires_at` check — session expiry handles it);
    `SysCoinController`; `WalletController` + `PhonePeWalletController`
    (role='student' guard preserved); `PhonePeFirmController`;
    `AuthController::changePassword`; `AdminController::submitPremiumRequest`
    (a user-facing endpoint that lives outside the middleware group);
    `ErrorLogController::store` and `ErrorLogRecorder::resolveUser` (best-effort,
    optional — unchanged behavior).
- **Phase 2 — `AuthController::login`:** no longer writes `users.api_token` /
  `token_expires_at`. The token is written only to `user_sessions`. Cookie
  (`auth_token`, 7 days, Lax) unchanged.
- **Phase 3 — fallback removal:** `ApiAuthMiddleware` is now session-only (removed
  the legacy `api_token` lookup + the api_token-clearing on expiry).
  `AuthController::me` likewise resolves via `user_sessions` only (removed the
  `else` api_token branch); impersonation resolution unchanged.
- **Phase 4 — legacy cleanup:** removed `users.api_token` writes from
  `AuthController::logout`, `UserController::requestAccountDeletion` (now just
  deletes the user's sessions), `SessionController::destroy` (dropped the stray
  api_token clear), and `AdminController` deleteFirm / deleteStudent (force-logout
  is the `user_sessions` delete that was already there).
- **Phase 5 — DB drop (migration created, NOT yet run):**
  `database/migrations/2026_06_24_000003_drop_legacy_user_api_token_columns.php`
  drops `users.api_token` + `users.token_expires_at`. **Run only AFTER deploying
  this code to the server and verifying all flows** (running it before the new code
  is live would break old code paths). Reversible `down()` re-adds the (empty) columns.
- Note: `FirmDashboardController` still `unset()`s `api_token`/`token_expires_at`
  from candidate output — left as a harmless defensive no-op (unsetting an absent
  array key is safe once the columns are dropped).

## 2026-06-24 — Payout UX refinements — backend

- **`app/Http/Controllers/API/AdminReferralController.php`** — `sendPayoutDetailsMail`
  link now points to `/settings?tab=payout` (payout details moved from the referrals
  page to Settings). No other backend change — the centralized `user_payout_details`
  table and `PayoutDetailsService` already power both flows; Send-Mail "show only when
  details missing" is enforced on the frontend using the `payout_details` already
  attached to each payout by `listPayouts`.

## 2026-06-24 — Centralized payout details + referral payout flow — backend

Introduced a single `user_payout_details` table for all payout flows (creator +
referral + future), migrated the creator flow onto it (backward compatible),
added referrer payout-details collection + admin Send-Mail, and hardened
notifications ordering. **`creator_bank_details` is NOT dropped** — retained until
the migrated flows are verified live (a later migration drops it).

- **`app/Http/Controllers/API/AdminNotificationController.php`** — `index()` order
  is now `created_at DESC, id DESC` (stable tiebreaker; same-second notifications
  always surface newest-first). [Task 1]
- **Task 2 (no code change)** — the ₹500↔₹2000 mismatch is by design:
  `referral_payouts.reward_amount` is a snapshot captured at creation; old rows
  predate the setting change. Verified the settings update path
  (`AdminSystemSettingController::update` → `SystemSettingService::set`) busts the
  cache, so new payouts correctly read the current value. Historical rows unchanged.
- **`database/migrations/2026_06_24_000002_create_user_payout_details_table.php`**
  (new) — creates `user_payout_details` (one row per user, UPI or encrypted bank)
  and copies all legacy `creator_bank_details` rows in (idempotent). Mirrored in
  `db_changes.txt`.
- **`app/Models/UserPayoutDetail.php`** (new) — model for the centralized table.
- **`app/Services/PayoutDetailsService.php`** (new) — single source of truth:
  `has()` (checks new table OR legacy), `getForDisplay()` (new table → legacy
  fallback; masks account, decrypts IFSC), `save()` (method-aware validation;
  encrypts bank fields; writes new table only).
- **`app/Http/Controllers/API/CreatorMarketplaceController.php`** — `getBankDetails`,
  `saveBankDetails`, `getPayoutStatus` now route through `PayoutDetailsService`
  (reads new table w/ legacy fallback; writes new table). Response shapes unchanged.
- **`app/Http/Controllers/API/AdminPayoutsController.php`** — creator-payouts admin
  list now joins `user_payout_details` (was `creator_bank_details`); selects
  `preferred_method`/`upi_id` too. Decrypt logic unchanged.
- **`app/Http/Controllers/API/PayoutDetailsController.php`** (new) — user endpoints
  `GET/POST /payout-details` (show + save via the service).
- **`app/Http/Controllers/API/AdminReferralController.php`** — `listPayouts` now
  attaches each referrer's `payout_details` (via the service); new
  `sendPayoutDetailsMail()` emails the referrer a request to add payout details
  (reuses the shared mail stack; logged in `email_logs`).
- **`app/Enums/EmailPurpose.php`** — new `REFERRAL_PAYOUT_REQUEST` case (sender:
  support).
- **`app/Mail/ReferralPayoutRequestMail.php`** + **`resources/views/emails/referral/payout-request.blade.php`**
  (new) — the payout-details request email (includes a link to the referral payout page).
- **`app/Services/Notifications/EmailNotificationService.php`** — new
  `sendReferralPayoutRequest()` using the existing `queue()` primitive.
- **`routes/api.php`** — added `GET/POST /payout-details` (auth group) and
  `POST /admin/referral-payouts/{id}/send-mail`; imported `PayoutDetailsController`.

## 2026-06-24 — Admin Email Logs page (read-only analytics) — backend

New admin-only, read-only API over the shared `email_logs` table so admins can
monitor sent emails, delivery status, campaign type, and CTA click tracking.
Mirrors `ErrorLogController` (paginated index, stats, delete-all). No DB change —
reuses `email_logs` incl. the `click_count`/`clicked_at` columns added earlier
today. All `/admin/*` paths are already guarded by `AdminAuthMiddleware`.

- **`app/Http/Controllers/API/EmailLogController.php`** (new) — `index()` returns
  paginated logs (50/page, latest first) with filters: `status` (sent|failed|pending),
  `campaign_type` (email_purpose), `from`/`to` date range, optional `search`
  (email/subject). Left-joins `users` by email only to surface the recipient name;
  the total count is computed on `email_logs` alone so the join can never inflate it.
  `stats()` returns total/sent/failed/clicked counts + distinct campaign types (keeps
  the filter dropdown data-driven). `destroy()` clears ALL rows (irreversible).
- **`routes/api.php`** — added `EmailLogController` import and three admin routes:
  `GET /admin/email-logs`, `GET /admin/email-logs/stats`, `DELETE /admin/email-logs`
  (placed beside the error-logs routes; inherit admin protection).

## 2026-06-24 — Re-engagement email: 6-segment support + single CTA + click tracking

Reworked the existing re-engagement campaign to cover all six segments
(student|firm × unverified | verified-incomplete | verified-completed), collapse
the old multi-button CTA into one "Login to Continue" button, and track CTA
clicks via a signed redirect route. Backend only — no React/frontend changes; the
button lands on the existing frontend `/login` (unverified users can log in and
are routed to verification by the existing flow). Reuses the existing mailable,
Blade template, EmailLog, and queue/DispatchMailJob path.

- **`database/migrations/2026_06_24_000001_add_click_tracking_to_email_logs.php`**
  (new) — Adds `click_count` (INT, default 0) and `clicked_at` (nullable timestamp)
  to `email_logs`. `down()` drops both. SQL mirrored in `db_changes.txt`.
- **`app/Models/EmailLog.php`** — Added `click_count` + `clicked_at` to `$fillable`,
  cast `clicked_at` to datetime, and added `registerClick()` (bumps `click_count`
  every hit; stamps `clicked_at` on the first click only, in one `save()`).
- **`app/Mail/ReEngagementMail.php`** — Constructor signature changed from
  `(name, userType, verified, subjectLine, cta[])` to
  `(name, userType, verified, profileCompleted, subjectLine, trackingUrl)`.
  The `cta[]` array (multi-button URLs) is gone; the view now receives
  `profileCompleted` and a single `trackingUrl`.
- **`resources/views/emails/reengagement.blade.php`** — Content derivation extended
  from 2 states to 3 (`unverified | incomplete | complete`) via a `$state` switch,
  with per-segment "ask" lists matching the campaign spec. Replaced the
  verified/unverified multi-button CTA block with a single "Login to Continue"
  button → `$trackingUrl`. Fixed a pre-existing bug where `$lead` rendered twice
  (removed the duplicate in the motivation box) and reworded the firm social-proof
  line. Benefits-header conditional updated for 3 states.
- **`app/Console/Commands/SendReEngagementEmails.php`** — Added `--profile=0|1`
  option; removed the hard `profile_completed = 0` filter (now selected, not
  filtered) so verified+completed users are reachable. Selects
  `users.profile_completed`. Creates the `EmailLog` row BEFORE building the mailable
  so its id seeds a signed `URL::signedRoute('email.click', …)` tracking URL.
  Builds the mailable with the new signature (passes `profileCompleted` +
  `trackingUrl`). `subjectFor()` now takes `$completed` and returns 3-state subjects
  (also dropped the "— Start Your Story" suffix). `segmentOf()` reports 3 states for
  dry-run. Removed the now-unused `ctaUrls()` helper. Added `URL` facade import.
- **`routes/web.php`** — Added signed route `GET /e/click/{emailLog}`
  (`name=email.click`): records the click via `registerClick()`, then
  `redirect()->away()` to the frontend `/login`. Updated the dev-only
  `/mail-preview/reengagement` route to the new mailable signature (added a
  `profile=0|1` query param; passes `trackingUrl`).

## 2026-06-23 — Fix resume PDF "Undefined variable $c1" (Modern Minimal template)

`ResumeController::downloadPdf` threw `Undefined variable $c1` when rendering the
`modern_minimal` template. Its DB row (`resume_templates` id=2) split skills into two
columns via an inline `@php $c1 = ...; $c2 = ...; @endphp` block, but
`renderTemplateHtml()` strips all `@php..@endphp` blocks before Blade renders
admin-authored content — so the assignments were removed and the later
`@foreach($c1 ...)` referenced an undefined variable.

- **DB `resume_templates` (id=2, `modern_minimal`)** — removed the `@php` skill-split
  block and pointed the two skill-column loops at the controller's pre-computed
  `$d['skills_c1']` / `$d['skills_c2']` (see `normalizeResume()`), which exist precisely
  so DB templates need no `@php`. No application code changed.

## 2026-06-23 — Fix resume PDF download (Browsershot "Cannot find module 'puppeteer'")

`ResumeController::downloadPdf` failed because Browsershot's `browser.cjs` could not
resolve the `puppeteer` Node module and the configured Chrome path pointed at another
user's cache.

- Ran `npm install` in `sys_api/` — `puppeteer` (declared in `package.json`) was missing
  from `node_modules`.
- Ran `npx puppeteer browsers install chrome` to download Chrome for the current user
  (`C:/Users/Tushar/.cache/puppeteer/chrome/win64-148.0.7778.97/...`).
- **`.env`** (`RESUME_PDF_CHROME_PATH`) — repointed from the stale `C:/Users/PHP_651/...`
  path to the current user's puppeteer Chrome path; ran `php artisan config:clear`.

Note: this is a local-dev env fix. No application code changed.

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



---

## 2026-06-23 — Resume Builder: P0 security fixes + temporary HTML preview mode

### Modified: `app/Http/Controllers/API/ResumeController.php`
- **Template-injection fix (P0):** `renderTemplateHtml()` now strips `@php`/`@endphp` blocks and raw `<?php`/`<?=` tags from admin-authored template HTML before `Blade::render()`. Safe directives (`@if`, `@foreach`, `{{ }}`) remain. Closes a server-side code-execution vector via the admin template editor.
- **DB-driven template_key validation (P0):** new `activeTemplateKeys()` unions the builtin keys with live `is_active` rows from `resume_templates`, replacing the hardcoded `in:` list (newly created admin templates are now accepted without a deploy).
- **Field-level `resume_data` validation (P0):** new `resumeDataRules()` caps every string length and array size (education/experience ≤ 10, skills/certs/achievements ≤ 20), shared by save + pdf + preview. Mitigates memory-exhaustion DoS.
- `normalizeResume()` now also returns pre-computed `skills_c1`/`skills_c2` halves so DB-managed templates need no `@php` for the two-column skills split.
- **New `previewHtml()` (POST):** TEMPORARY template-development endpoint. Reuses the SAME `normalizeResume()` + `renderTemplateHtml()` pipeline as the PDF path, then wraps the document with `@page { size: A4 }` + an `.rb-a4-page` (210mm × 297mm) frame and returns `text/html` for in-browser preview. No mPDF involved.

### Modified: `app/Providers/AppServiceProvider.php`
- New `resume-pdf` named rate limiter: 5 requests/min per authenticated user (mPDF uses 20–50 MB RAM/call). Shared by `/resume/pdf` and `/resume/preview-html`.

### Modified: `routes/api.php`
- `POST /resume/pdf` now carries `->middleware('throttle:resume-pdf')`.
- New `POST /resume/preview-html` → `ResumeController@previewHtml` (same auth group + throttle). TEMPORARY.

### New: `database/migrations/2026_06_23_000001_patch_resume_templates_p0.php`
- Patches the seeded `resume_templates` rows (skips admin-edited rows via `updated_at !== created_at`): `modern_minimal` drops its `@php` skills-split in favour of `$d['skills_c1']`/`$d['skills_c2']`; `classic_professional` now renders `title`, `linkedin`, `website` (were captured but never printed). `down()` is a no-op to avoid clobbering admin edits.

### Modified: `resources/views/resume/pdf.blade.php` (static fallback)
- Mirrors the migration: Classic renders title/linkedin/website; Modern uses the pre-computed skills halves (no `@php`).

### Testing
- `php -l` clean on all changed PHP files + migration.

### Rollback Plan
- Remove `previewHtml()` + the `/resume/preview-html` route to drop preview mode.
- Revert `renderTemplateHtml()` strip lines to restore raw `Blade::render()` (NOT recommended — reopens injection).
- Drop the `resume-pdf` limiter + the two `->middleware('throttle:resume-pdf')` calls to remove throttling.
- `php artisan migrate:rollback` is a no-op for the patch migration by design.

---

## 2026-06-23 — Resume Builder: two templates only + duration model + template icons

### Modified: `app/Http/Controllers/API/ResumeController.php`
- **Two templates only:** builtin `TEMPLATE_KEYS` reduced to `classic_professional` + `modern_minimal`. New `coerceTemplateKey()` maps any retired/legacy/unknown key (e.g. `executive_sidebar`, `creative_professional`) to Classic. `saveResume`, `downloadPdf`, and `previewHtml` now `$request->merge()` the coerced key before validation — so old drafts/clients sending a retired key save + render as Classic instead of 422-ing (graceful fallback). On save this also migrates the stored `template_key`.
- **Experience duration validation:** added nullable rules for the new structured fields `startMonth`/`startYear`/`endMonth`/`endYear` (string) and `current` (boolean); bumped `duration` max 50→60 to fit "Mon YYYY – Mon YYYY". The canonical `duration` string is still what templates render — the structured fields are extra and round-trip in the JSON.

### New: `database/migrations/2026_06_23_000002_resume_templates_two_only_icons.php`
- Sets `is_active = false` for `executive_sidebar` + `creative_professional`.
- Overwrites Classic + Modern `html_content` with icon-enhanced versions (inline lucide-matching SVGs: Classic gets section + contact icons; Modern gets per-contact icons, section headings stay text-only to match the React editor). Appends `.ic` icon CSS to those rows' `css_content` (idempotent).
- `down()` re-activates the two retired templates; does not touch content.

### Modified: `resources/views/resume/pdf.blade.php` (static fallback)
- Added `rb_icon($name)` helper (inline SVGs) + `.ic` CSS; wired icons into the Classic + Modern cases to match the DB templates. Executive/Creative cases left intact (now dormant).

### Why inline SVG
- The React editor renders lucide icons but the backend HTML/PDF had none — that mismatch is the "icons missing in download/HTML preview" bug. Inline SVG renders identically in the browser HTML preview (the current active download path) and is the most portable option for mPDF. mPDF SVG fidelity should be re-verified when the PDF download path is re-enabled.

### Migration impact
- `php artisan migrate` runs both new migrations. Existing resume drafts are unaffected at the data layer; those referencing a retired template render as Classic and get migrated to Classic on their next save.

### Rollback Plan
- `php artisan migrate:rollback` re-activates Executive/Creative (content untouched).
- Revert `TEMPLATE_KEYS` to the 4-key list and remove the `coerceTemplateKey()` merges to re-offer all templates in the API.

---

## 2026-06-23 — Modern Minimal: Education section two-row layout

Modern Minimal template ONLY. No controller / data-structure changes; Classic and
all other templates untouched.

### New: `database/migrations/2026_06_23_000003_modern_minimal_education_layout.php`
- Surgically swaps the Modern Minimal education `<table>` (uniquely identified by its
  `margin-bottom:4px` inline style) for a two-row layout — Degree | Duration over
  Institute | Score — and appends the `.ed-deg/.ed-dur/.ed-inst/.ed-score` CSS to that
  row's `css_content`. Idempotent (regex finds no match if already patched/edited);
  `down()` is a no-op.

### Modified: `resources/views/resume/pdf.blade.php` (static fallback)
- Same two-row education layout + CSS in the `modern_minimal` case, mirroring the DB
  template. Old `{{ trim($e['year'].' '.$e['score']) }}` single-line row removed.

### Migration impact
- `php artisan migrate` runs the new migration. No schema change; updates only the
  Modern Minimal template row content/CSS.

### Rollback
- `php artisan migrate:rollback` (no-op down) — to fully revert, restore the prior
  Modern education block from git history.

---

## 2026-06-23 — Classic Professional: premium Skills chips (ATS-safe)

Classic Professional template ONLY. No controller / data-structure changes; Modern
Minimal and all other templates untouched.

### New: `database/migrations/2026_06_23_000004_classic_skills_chip_style.php`
- Swaps the `.classic .chip` CSS rule in the classic_professional row from the old
  tight tag (`padding: 1px 6px; font-size: 10px`) to a premium pill: `display:
  inline-block; border: 1px solid #e2e8f0; background-color: #f8fafc; border-radius:
  20px; padding: 5px 13px; margin: 0 5px 7px 0; font-size: 12px`. Markup unchanged
  (skills stay plain `<span class="chip">text</span>` → fully ATS-parseable).
  Idempotent str_replace; `down()` restores the old rule.

### Modified: `resources/views/resume/pdf.blade.php` (static fallback)
- Same `.classic .chip` rule update in the inline `<style>`, mirroring the DB row.

### ATS compliance (both templates — verification, no code change needed)
- Single-column, normal-flow layouts (no floats/absolute/overlap); standard section
  headings (Summary, Education, Experience, Skills, Certifications, Achievements);
  Arial/Helvetica body; all content is selectable text; section icons are decorative
  inline SVG paths with NO text nodes; skills render as plain text. Compliant.

### Migration impact
- `php artisan migrate` runs the new migration (CSS-only update to one row).

### Rollback
- `php artisan migrate:rollback` restores the previous chip rule.

---

## 2026-06-23 — Resume PDF: filename "<Name>_resume.pdf"

### Modified: `app/Http/Controllers/API/ResumeController.php`
- `downloadPdf` now names the file `<Name>_resume.pdf` (e.g. `Ananya_Iyer_resume.pdf`),
  or `resume.pdf` when no name is set. Replaced `Str::slug($name)."-resume.pdf"`
  (hyphenated, lowercased) with an underscore-joined ASCII name (`[^A-Za-z0-9]+` → `_`,
  trimmed). Removed the now-unused `Illuminate\Support\Str` import. ASCII-only keeps the
  Content-Disposition header safe.
- No change to the endpoint, auth, throttle, or rendering — the frontend Download
  buttons were simply re-pointed from the temporary HTML preview back to this PDF route.

### Rollback
- Restore the `Str::slug(...)."-resume.pdf"` line + the `use Illuminate\Support\Str;`
  import to revert the filename format.

---

## 2026-06-23 — New file-based template: Premium Minimal

Adds a third switchable template. pdf.blade.php is untouched.

### New: `resources/views/resume/premium_minimal.blade.php`
- User-authored premium single-column template, re-bound from its original
  `$resumeData/personalInfo` contract to the project's normalized `$d` contract
  (name/title/email/mobile/location/linkedin/website, summary, education[degree,
  institute,year,score], experience[company,role,duration,lines], skills[],
  certifications[], achievements[], showCertifications/showAchievements). CSS kept
  as authored (A4 .page, navy headings, mPDF-safe table rows, skill chips).
- NOTE: the original file's header comment contained the literal token "@php",
  which Blade's directive compiler pairs with the real raw-PHP block and silently
  swallowed the entire <head>. Reworded the comment to remove directive tokens.

### Modified: `app/Http/Controllers/API/ResumeController.php`
- Added `premium_minimal` to builtin `TEMPLATE_KEYS` (accepted by validation,
  preserved by `coerceTemplateKey`).
- New `FILE_TEMPLATES` map + a branch at the top of `renderTemplateHtml`: keys in the
  map render straight from their standalone Blade view (full self-contained HTML doc),
  bypassing the DB rows. This is the "switch between files" mechanism — classic/modern
  still render from their DB rows; premium_minimal renders from the file. Works for
  both POST /resume/pdf and POST /resume/preview-html.

### Verification
- `php -l` clean; rendered premium_minimal with sample data — head/CSS present and all
  bindings (name, title, summary, experience duration + bullets, education score,
  skill chips, certifications, achievements) resolve. coerceTemplateKey keeps
  `premium_minimal` and still maps retired/unknown keys to Classic.

### Rollback
- Remove `premium_minimal` from `TEMPLATE_KEYS` + the `FILE_TEMPLATES` map/branch, and
  delete the view file. Frontend: remove it from `RESUME_TEMPLATES`/`TEMPLATE_KEYS`.

---

## 2026-06-23 — Fix: premium_minimal PDF "Undefined array key -1" (mPDF)

### Modified: `resources/views/resume/premium_minimal.blade.php`
- `downloadPdf` was failing with `Undefined array key -1` (mPDF Mpdf.php:8352) and a
  500. Root cause: the template's own `@page { size: A4; margin: 0; }` rule combined
  with a bordered inline-block element (the skill chips) trips an mPDF span-border
  width-measurement bug. Bisected to that exact pair.
- Removed the local `@page` rule (redundant — mPDF gets A4 + zero margins from the
  controller config, and the HTML-preview path injects its own `@page`). Skills now
  render as plain chips and PDF generation succeeds for full / skills-only / single-
  skill / no-skills / empty payloads.
- No controller/data change.

---

## 2026-06-23 — New file-based template: Premium Resume (Blade conversion)

Converts the resume(1).html prototype into a switchable Blade template. pdf.blade.php
untouched.

### New: `resources/views/resume/premium_resume.blade.php`
- Bound to the normalized `$d` contract (same as premium_minimal). mPDF adaptations
  from the browser prototype: flexbox/CSS-grid → table-based two-column rows; CSS
  custom properties (`var(--x)`) → literal hex (mPDF doesn't resolve var()); removed
  the local `@page` (a local @page + bordered inline pills crashes mPDF — proven
  earlier); icons are decorative fill-based inline SVG via a guarded `pr_icon()`
  helper, all resume content stays plain selectable text (ATS-friendly).

### Modified: `app/Http/Controllers/API/ResumeController.php`
- Added `premium_resume` to `TEMPLATE_KEYS` (accepted by validation, preserved by
  coerceTemplateKey) and to `FILE_TEMPLATES` (renders from the standalone view).

### Removed
- Stray `resources/views/resume/resume(1).html` (downloaded prototype artifact). The
  reference prototype remains at `resources/views/resume/premium_resume_template.html`.

### Verification
- `php -l` clean; mPDF renders the template for full and empty payloads with no
  "Undefined array key -1"/crash (37 KB / 1.4 KB). Bindings (name, contacts, summary,
  experience rows + bullets, education rows, skill pills, certs/achievements) resolve;
  8 skill chips + 10 SVG icons present. Frontend `tsc` clean.

### Rollback
- Remove `premium_resume` from `TEMPLATE_KEYS` + `FILE_TEMPLATES` and delete the view;
  frontend: remove from `RESUME_TEMPLATES`/`TEMPLATE_KEYS`.

---

## 2026-06-23 — Premium Resume: final stabilization (browser + HTML preview + mPDF)

Polishing only — no redesign, no palette/typography changes. premium_resume only.

### Modified: `resources/views/resume/premium_resume.blade.php`
- ISSUE 1 (dividers): all dividers converted from dashed to solid border-based rules
  (`.header-divider` 2px navy, `.section-divider` + experience separator 1px #e5e7eb).
  Dashed borders rendered as broken "---" fragments in mPDF; solid borders are clean.
- ISSUE 2 (page breaks): `page-break-inside: avoid` on `.exp-item`, `.edu-item`, and
  each `.two-col .col`; `page-break-after: avoid` on `.sec-head` so a section header
  does not orphan at the bottom of a page.
- ISSUE 3 (header): contact row shows short "LinkedIn"/"Website" labels (not the raw
  URL); items stay `white-space: nowrap` so the row wraps between items, never inside.
- ISSUE 4 (long company): experience header cells given fixed widths (68% / 32%) so a
  long company name wraps on the left while the duration stays right-aligned.
- ISSUE 5/6 (skills): consistent chip height via fixed `line-height`; `.skills-wrap`
  container line-height gives even spacing between wrapped rows; global word-wrap so
  long skills/URLs/company names never overflow.
- ISSUE 8/14 (bullets + rhythm): bullet line-height/margins standardized.
- ISSUE 11 (icons): section-icon badges drawn as a single-cell table with
  `background-color` + `border-radius` (mPDF paints cell backgrounds reliably, unlike
  inline-block spans) so the white icon is always visible; SVG fill moved from inline
  `style` to the `fill` presentation attribute (mPDF SVG parser honours it).
- ISSUE 13 (margins): page padding standardized to 18mm all sides.

### Verification (ISSUE 15 — 5 datasets, all via mPDF, no errors)
- Minimal 31KB · Fresher 36KB · Articleship 37KB · Experienced 40KB · Very-long
  (long email/URLs, 80-char company, 10 bullets, long skill) 42KB — all OK.
- Empty-data render: every section (Summary/Experience/Education/Skills/Certs/Ach)
  fully suppressed (ISSUE 9). 6 white-fill section icons + contact icons present;
  no raw LinkedIn URL leaked.

### Regression risks
- Skill pills keep a border; combined with a local @page this previously crashed mPDF
  — this template intentionally has NO @page, so it stays safe. Do not re-add @page.
- Icon badge is now a nested table; visually a navy rounded square/circle — confirm in
  a real PDF that radius renders as expected on the target mPDF build.

### Rollback
- Revert this file from git history; no schema/controller change involved.

---

## 2026-06-23 — Resume PDF engine: mPDF → Spatie Browsershot (headless Chromium)

Swaps ONLY the rendering engine behind `POST /resume/pdf`. The endpoint, auth, rate
limit, validation, normalized `$d` contract, `<Name>_resume.pdf` filename, the Backend
Preview endpoint (`/resume/preview-html`) and the frontend are all UNCHANGED. The PDF
is now produced by a real browser, so it matches the Backend Preview far more closely
(no dashed-border breakage, accurate page breaks, crisp vector text, true-circle icon
badges) — the issues that dogged mPDF are gone.

### Added: `spatie/browsershot` (composer) + `puppeteer` (npm)
- `composer require spatie/browsershot` (v5.4). Pulls `spatie/temporary-directory`.
- `package.json` → new `dependencies: { "puppeteer": "^24.0.0" }`. Puppeteer ships a
  matching Chromium; `npm install` on the server fetches it.

### Modified: `app/Http/Controllers/API/ResumeController.php`
- `downloadPdf()` — removed the entire mPDF block (`new \Mpdf\Mpdf(...)`, `WriteHTML`,
  `Output`, the `storage/app/mpdf` temp dir). Now builds the same `renderTemplateHtml()`
  document and hands it to a new helper. Filename logic and the response headers are
  byte-for-byte the same.
- New private `renderResumePdf(string $html): string` — `Browsershot::html($html)
  ->format('A4')->showBackground()->margins(0,0,0,0)->timeout(...)->pdf()`. Resolves
  puppeteer from the project's `node_modules`; honors optional `node_binary` /
  `npm_binary` / `chrome_path` / `no_sandbox` from config. `margins 0` + `showBackground`
  reproduce the old mPDF intent (templates own their insets; full-bleed bands reach the
  edge; colored backgrounds print). Default print media honors `page-break-*` rules.
- No `\Mpdf` references remain in the controller. (mPDF was used nowhere else.)

### Added: `config/resumepdf.php`
- Per-environment Browsershot binary paths + `no_sandbox` (default true — Chromium
  refuses its sandbox as root on most VPSes) + render `timeout`. All from `.env`
  (`RESUME_PDF_*`), all optional. `.env.example` documents the keys.

### Modified: `resources/views/resume/premium_resume.blade.php` (1 additive print rule)
- Added an `@media print { html,body{background:#fff} .page{margin:0;min-height:0} }`
  block. The template's gray body canvas + `.page { margin:16px auto; min-height:297mm }`
  are on-screen "floating page" affordances; mPDF ignored them, but real Chromium
  honored them and spilled a near-empty SECOND page (every premium_resume render was
  +1 page) and printed the gray canvas. Print-media override neutralizes them for the
  PDF only. The Backend Preview (screen media) is byte-for-byte unchanged; resume
  content design/colors/spacing are untouched. NOT a redesign.

### Verification (real Chromium render, 4 templates × 4 datasets = 16 PDFs)
- All 16 valid (`%PDF` … `%%EOF`). classic/modern/premium_minimal/premium_resume.
- Page counts correct: short resumes 1 page, very-long (6 jobs × 6 bullets, 24 skills,
  4 degrees, long names/URLs) paginates cleanly — premium_resume 3 pages, others 2 —
  with NO experience/education block split across a page boundary.
- premium_resume minimal/fresher/experienced now 1 page (was 2 before the print fix).
- Visual eyeball of premium_resume PDFs: navy circular icon badges render as TRUE
  circles with centered white glyphs, solid dividers, rounded skill pills, two-column
  certs/achievements, navy footer bar, crisp text, white background (no gray canvas).
- Render time ~0.6–0.85 s warm per PDF (first call adds Chromium cold-start ~0.7 s).
  Sizes 65–170 KB.

### Required server packages / install (Hostinger VPS, Ubuntu/Debian)
1. Node + npm (Node 18+):
   `curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash - && sudo apt-get install -y nodejs`
2. Chromium runtime libraries (Puppeteer's bundled Chromium needs these shared libs):
   `sudo apt-get install -y libnss3 libatk1.0-0 libatk-bridge2.0-0 libcups2 libdrm2 \
     libxkbcommon0 libxcomposite1 libxdamage1 libxfixes3 libxrandr2 libgbm1 libasound2 \
     libpango-1.0-0 libpangocairo-1.0-0 libgtk-3-0 ca-certificates fonts-liberation`
3. Fonts (so Arial/Helvetica → Liberation Sans, and the Classic/Modern `dejavusans`
   fallback resolve; without these Chromium substitutes a default sans):
   `sudo apt-get install -y fonts-liberation fonts-dejavu fonts-noto-core`
4. In the project root: `composer install` then `npm install`.
5. Provide Chromium — pick ONE (Option B is the most reliable; Puppeteer's bundled-Chrome
   auto-resolution proved finicky when a stale cache from an older puppeteer was present):
   - Option A (bundled): `npx puppeteer browsers install chrome` — prints the resolved
     binary path; set `RESUME_PDF_CHROME_PATH` to that path so resolution can't drift.
   - Option B (system Chromium, recommended): `sudo apt-get install -y chromium-browser`
     then `RESUME_PDF_CHROME_PATH=/usr/bin/chromium-browser`.
   Verified locally: with `RESUME_PDF_CHROME_PATH` set, all 16 renders pass on puppeteer
   24.43.1 + Chrome 148. (Without it, puppeteer 24 failed to locate Chrome on a machine
   whose cache held only older builds.)
6. Set the user that php-fpm runs as so it can read node_modules / the Chrome binary; if
   php-fpm differs from the install user, set `RESUME_PDF_NODE_MODULES_PATH` to the project root.
7. `php artisan config:clear` (or `config:cache`) so `config/resumepdf.php` loads.

### Regression risks
- HARD DEPENDENCY on Node + Chromium being installed on the server. If missing/misconfigured,
  `/resume/pdf` returns 500 ("Failed to generate PDF.") and logs `ResumeController::downloadPdf`.
  There is intentionally NO silent mPDF fallback (it would reintroduce the mismatched output).
- Chromium as root needs `--no-sandbox` (`RESUME_PDF_NO_SANDBOX=true`, the default).
- Heavier per request: spawns Chromium (~0.6–0.85 s, 100–300 MB RAM) vs mPDF (~50–200 ms).
  The existing `throttle:resume-pdf` (5/min/user) caps concurrency; consider queueing if
  download volume grows. First request after idle pays a cold-start.
- Server fonts must be installed (step 3) or text metrics differ slightly from the
  designer's machine. No network is used at render time (templates are fully inline).

### Rollback
- Revert `downloadPdf()` + remove `renderResumePdf()`/`config/resumepdf.php`; the old mPDF
  code restores 1:1. `mpdf/mpdf` is still in composer.json (left installed for exactly
  this), so no `composer require` is needed to roll back. The `premium_resume` `@media
  print` block is harmless under mPDF (mPDF ignores `@media print`) and can stay.
- Optional cleanup once Browsershot is confirmed in production: `composer remove mpdf/mpdf`.

## 2026-06-30 — Support Ticket system (students, firms & admin)

New help-desk module. Students and firms can raise tickets, attach files, and chat
with the support team in a threaded conversation; admins triage from the admin panel
(stats, filters, assign-to-me, status changes, threaded replies, close-with-resolution).

### Added: Database (applied via `php artisan migrate --path=...`)
- `database/migrations/2026_06_30_000001_create_support_tickets_table.php` — `support_tickets`
  (ticket_no, user_id, user_type[student|firm], ticket_category, issue_brief, attachments JSON,
  status[submitted|in_process|closed], assigned_to_admin_id, resolution_note, closed_at, timestamps).
  Indexes: (user_id,user_type,created_at), status, assigned_to_admin_id, ticket_category; unique ticket_no.
- `database/migrations/2026_06_30_000002_create_support_ticket_messages_table.php` — `support_ticket_messages`
  (ticket_id, sender_type[student|firm|admin|system], sender_id, message, attachment_path, created_at).
  Index: (ticket_id, created_at).
- `db_changes.txt` — appended FORWARD/ROLLBACK SQL for both tables.

### Added: `app/Helpers/SupportTicketHelper.php`
- Category whitelist, status labels, `ticketNo()` (SYS-TKT-000001), `fileUrl()`, `decodeAttachments()`.

### Added: `app/Http/Controllers/API/SupportTicketController.php` (user-facing)
- `index()`  — GET /support-tickets — current user's tickets (paginated, newest first).
- `create()` — POST /support-tickets — validate + insert (status=submitted), generate ticket_no,
  store up to 3 attachments (jpg/jpeg/png/pdf/txt, ≤5MB), seed thread with opening message,
  notify user (in-app) + admin (in-app + FCM).
- `show()` — GET /support-tickets/{id} — ticket + thread (ownership enforced; 403 otherwise).
- `addMessage()` — POST /support-tickets/{id}/messages — user reply + optional attachment (blocked when closed).

### Added: `app/Http/Controllers/API/AdminSupportTicketController.php`
- `index()` — GET /admin/support-tickets — stats (total/submitted/in_process/closed/unassigned)
  + filters (status, category, user_type, assigned/unassigned, search by ticket_no/id/name/email) + pagination.
- `show()` — GET /admin/support-tickets/{id} — full detail incl. user info, attachments, thread.
- `assign()` — POST /admin/support-tickets/{id}/assign — assign to acting admin; submitted→in_process.
- `updateStatus()` — POST /admin/support-tickets/{id}/status — change status; close REQUIRES resolution_note
  (required_if), stamps closed_at, sends close email; system message + user notification per transition.
- `addMessage()` — POST /admin/support-tickets/{id}/messages — admin reply; notifies the ticket owner.

### Modified: `routes/api.php`
- Added 4 user routes under `Route::middleware([ApiAuthMiddleware::class])`.
- Added 5 admin routes under `Route::prefix('admin/support-tickets')` (auto-guarded by AdminAuthMiddleware).

### Modified: Notifications & Email (additive)
- `app/Services/Notifications/AdminNotificationService.php` — added `TYPE_SUPPORT_TICKET`.
- `app/Enums/EmailPurpose.php` — added `SUPPORT_TICKET_CLOSED` case + senderKey('support').
- `app/Mail/SupportTicketClosedMail.php` + `resources/views/emails/support-ticket-closed.blade.php` — close email (Ticket ID, Category, Resolution Note).
- `app/Services/Notifications/EmailNotificationService.php` — added `sendSupportTicketClosed()`.

### Impact
- Purely additive: 2 new tables, 2 new controllers, 9 new routes, 1 new mailable + helper. No existing
  table, route, controller, or auth flow modified. Existing notification/email pipelines reused unchanged.

## 2026-06-30 — Support tickets: stricter ownership (user_type)

Hardened the user-facing support endpoints so access requires BOTH the matching
user_id AND user_type — a student can never reach a firm ticket and vice-versa.

### Modified: `app/Http/Controllers/API/SupportTicketController.php`
- `index()` — now filters by `user_id` AND `user_type` (a user only ever sees their own type's tickets).
- `show()` / `addMessage()` — ownership check is now `user_id === auth id AND user_type === auth type` (403 otherwise).
- Auth itself unchanged: all routes already require ApiAuthMiddleware (unauthenticated → 401).

## 2026-06-30 — Lightweight activity tracking (firm/student business actions)

Async, fail-safe activity log for important firm/student business actions, written
off the request path via a queue job so logging can never slow down or break the
host operation. Single table, centralized helper, no admin UI yet (backend only).

### Added: `database/migrations/2026_06_30_000003_create_activity_logs_table.php`
- One table `activity_logs`: `id`, `actor_type` ENUM('firm','student'), `actor_id`
  (= acting account's `users.id`), `action_type` VARCHAR(64), `meta` JSON NULL,
  `created_at`. Indexes: `(actor_type,actor_id)`, `action_type`, `created_at`.
  Append-only; no FKs (matches the rest of the schema). NOTE: applied directly
  (Schema::create) + recorded in `migrations` because this DB was seeded from a
  SQL dump and `artisan migrate` would otherwise try to recreate dump-only tables.

### Added: `app/Enums/ActivityType.php`
- Backed string enum — the canonical, ONLY tracked actions: `JOB_POSTED`,
  `INTERVIEW_INVITE_SENT`, `INTERVIEW_SCHEDULED`, `CONTENT_CREATION_POSTED`,
  `SUBSCRIPTION_PURCHASED` (firm); `INTERVIEW_ACCEPTED`, `CONTENT_SUBMITTED`,
  `WALLET_RECHARGED` (student). Extend freely — no schema change needed.

### Added: `app/Jobs/LogActivityJob.php`
- Queued (`ShouldQueue`) single-row writer. `tries=3`, backoff [30,60,120].
  `handle()` wraps the INSERT in try/catch and swallows errors (logs a warning);
  `failed()` is a final backstop. A lost activity row can never surface to a user.

### Added: `app/Services/ActivityTracker.php`
- Central reusable entry point: `ActivityTracker::log($actorType, $actorId, $actionType, $meta=[])`.
  Dispatches `LogActivityJob` inside try/catch (non-blocking — a queue/dispatch
  failure never reaches the caller); skips null/≤0 actor ids. `::FIRM` / `::STUDENT`
  constants + `::actorFromRole()` helper. Always called AFTER the host op succeeds
  (after `DB::commit`).

### Modified: controllers — one non-blocking `ActivityTracker::log(...)` per success path
- `FirmController@createJob` → `JOB_POSTED` (after commit) `{job_id, job_title}`.
- `InterviewInviteController@invite` → `INTERVIEW_INVITE_SENT` `{invite_id, student_id}`.
- `InterviewInviteController@schedule` → `INTERVIEW_SCHEDULED` `{invite_id, student_id, interview_date}`.
- `InterviewInviteController@respond` (accepted only) → `INTERVIEW_ACCEPTED` `{invite_id, firm_id}`.
- `JobsController@scheduleInterview` → `INTERVIEW_SCHEDULED` `{application_id, job_id, student_id, interview_date}`
  (job-application interview flow — both interview flows are instrumented so the feed is complete).
- `JobsController@respondInterview` (accepted only) → `INTERVIEW_ACCEPTED` `{application_id, job_id}`.
- `CreatorMarketplaceController@createProject` (published only) → `CONTENT_CREATION_POSTED` `{project_id, title}`.
- `CreatorMarketplaceController@submitDeliverable` (after commit) → `CONTENT_SUBMITTED` `{engagement_id, submission_id, round}`.
- `PhonePeFirmController@verify` + `@webhook` (on fresh activation) → `SUBSCRIPTION_PURCHASED` `{subscription_id, plan, amount}`.
  Webhook resolves the firm's `user_id` from `firm_profiles` (no auth context in S2S).
- `PhonePeWalletController@verify` + `@webhook` (only on a fresh credit) → `WALLET_RECHARGED` `{recharge_id, amount}`.
  Existing idempotency guards ensure exactly one log per recharge across verify/webhook races.

### Failure safety (verified)
- `ActivityTracker::log` and `LogActivityJob::handle` are both fully non-throwing.
  Tested: invalid enum (job INSERT fails) → swallowed, no exception; oversized payload
  → caller returns normally + worker drains with 0 failed jobs; null/0 actor → skipped,
  nothing queued; business return value preserved regardless of logging outcome.

### Impact
- Purely additive: 1 new table, 1 enum, 1 job, 1 service, 10 success-path call sites.
  No existing table/route/auth flow changed; no behavior change to any host operation.
  Logging is async (DB queue, table `queue_jobs`) and best-effort. Requires a running
  queue worker (`php artisan queue:work`) to flush rows — already used by the app's
  email/digest jobs.

## 2026-06-30 — Activity Tracker: admin read-only viewer API

Backend for the admin "Activity Tracker" page — lists/filters the firm/student
`activity_logs` written by ActivityTracker (above). READ-ONLY: no store/update/delete.

### Added: `app/Http/Controllers/API/AdminActivityTrackerController.php`
- `index()` — GET `/admin/activity-tracker` — paginated (50/page), newest first.
  Filters: `actor_type` (firm|student), `action_type`, `date_from`, `date_to`, `search`.
  Left-joins `users` + `firm_profiles` off `activity_logs.actor_id` so each row carries a
  resolved `actor_name` (firm → `firm_name`, student → user `name`, graceful fallback),
  `actor_email`, and decoded `meta`. Search matches user name / firm name / email / action_type.
- `stats()` — GET `/admin/activity-tracker/stats` — headline counts for the stat cards:
  `total`, `firm`, `student`, `today` (single aggregate query).
- Defense-in-depth admin check (`admin_token` → `admin_users`) mirroring AdminActivityLogController.

### Modified: `routes/api.php`
- Registered the two GET routes next to the existing `/admin/activity-logs` block
  (stats route declared before the base route). Both auto-guarded by AdminAuthMiddleware
  (all `/admin/*` paths). The existing admin audit-trail routes are untouched.

### Testing (HTTP, via `artisan serve`)
- No `admin_token` → **401**. With token: `stats` → `{total,firm,student,today}` correct;
  list returns resolved `actor_name`/`actor_email` + decoded `meta`; `actor_type`,
  `action_type`, `date_from`, and `search` filters each return the expected subset.
- Test rows seeded then truncated; `php -l` clean.

### Impact
- Purely additive: 1 new controller, 2 new GET routes. No existing route/controller/auth
  changed. New route path (`/admin/activity-tracker`) deliberately avoids the existing
  `/admin/activity-logs` audit endpoint.

## 2026-06-30 — Two new reminder flows: pending interview response + applicants awaiting review

Adds the two missing reminder workflows identified in the scheduled-jobs audit, reusing
the existing scheduler (`routes/console.php`), notification helper, and email service.
Both are async (database queue), fully fail-isolated, and change no existing behavior.

### New scheduled jobs (registered in `routes/console.php`)
- `send-interview-response-reminders` — **hourly** — `Schedule::job(new SendInterviewResponseReminderJob())->withoutOverlapping()`.
- `send-firm-applicant-reminders` — **daily @ 09:00** — `Schedule::job(new SendFirmApplicantReminderJob())->withoutOverlapping()`.

### Added: `app/Jobs/SendInterviewResponseReminderJob.php` (Reminder Flow 1)
- Reminds students with an interview invitation still awaiting accept/reject.
- Source: `interview_invites` where `invite_status='pending' AND active_flag=1`, student not deleted.
- Escalation off `invited_at`: **≥24h → R1, ≥72h → R2, ≥120h(5d) → R3 (final)**, then stop.
- Persists progress in `interview_invites.response_reminders_sent` (0–3) + `last_response_reminder_at`,
  computed from a "due count" so missed scheduler runs catch up and never double-send.
- Channels: in-app (`NotificationHelper::create`) **and** email
  (`EmailNotificationService::sendInterviewResponseReminder` → `DispatchMailJob`, queued + logged).
- Per-invite try/catch; counter advanced only after a successful pass (failed iteration retries next run).
- Elapsed time computed from raw timestamps (`strtotime`), not `Carbon::diffInHours`, because
  Laravel 13 ships **Carbon 3** whose `diff*` is **signed** (a past date returns negative).

### Added: `app/Jobs/SendFirmApplicantReminderJob.php` (Reminder Flow 2)
- Reminds firms of applicants awaiting review on their **active** jobs.
- "Awaiting review" = `applications.recruiter_status='Applied'` (the untouched default before the
  firm shortlists/rejects/requests an interview) on `jobs.is_active=1`.
- **Anti-spam:** per-job cooldown via `jobs.last_applicant_reminder_at` — a job recurs only if NULL
  or older than **3 days**. One email + one in-app notification **per firm**, summarising all its
  jobs needing review; included jobs are stamped only after a successful send.
- Channels: in-app + email (`EmailNotificationService::sendFirmApplicantReminder` → `DispatchMailJob`).
- Per-firm try/catch isolates failures.

### Added: emails
- `app/Mail/InterviewResponseReminderMail.php` + `resources/views/emails/interview/response-reminder.blade.php`.
- `app/Mail/FirmApplicantReminderMail.php` + `resources/views/emails/firm/applicant-reminder.blade.php`.
- Both implement `HasEmailPurpose`, sent via the existing `EmailNotificationService::queue()` primitive.

### Modified
- `app/Enums/EmailPurpose.php` — added `INTERVIEW_RESPONSE_REMINDER` (senderKey `interview`, recipient student)
  and `FIRM_APPLICANT_REMINDER` (senderKey `support`, recipient firm).
- `app/Services/Notifications/EmailNotificationService.php` — added `sendInterviewResponseReminder()`
  and `sendFirmApplicantReminder()` (reuse the shared `queue()` primitive → email_logs + DispatchMailJob).
- `routes/console.php` — 2 new imports + 2 new `Schedule::job(...)` registrations.

### DB changes (additive; no data migration)
- `database/migrations/2026_06_30_000004_add_response_reminder_tracking_to_interview_invites.php`
  — `interview_invites`: `response_reminders_sent` TINYINT UNSIGNED default 0, `last_response_reminder_at` TIMESTAMP NULL.
- `database/migrations/2026_06_30_000005_add_applicant_reminder_tracking_to_jobs.php`
  — `jobs`: `last_applicant_reminder_at` TIMESTAMP NULL.
- Applied directly (Schema::table) + recorded in `migrations` — `artisan migrate` is unusable on this
  dump-seeded DB (it would try to recreate dump-only tables). Also appended to `db_changes.txt`.

### Failure safety (verified)
- Neither job throws to its caller; `NotificationHelper::create` is internally non-throwing; email send
  runs in `DispatchMailJob` (3 retries, marks `email_logs` sent/failed). A reminder failure never affects
  any business operation, and un-advanced tracking columns mean the next run retries cleanly.

### Testing (tinker, seeded then fully cleaned up)
- **Flow 1:** 25h→sent=1, re-run→no double-send, 73h→sent=2, 121h→sent=3 (final), max→stop, accepted invite→skipped;
  in-app + email created each stage with correct copy.
- **Flow 2:** firm with 2 active jobs (2+1 pending, 1 Shortlisted excluded) → one summary "3 applicants across
  2 job postings", email + notification; immediate re-run → cooldown blocks (no spam); jobs stamped.
- **Failure:** notification huge-input → returns false, no throw; simulated mail outage → job doesn't throw,
  counter not advanced; next run recovers and sends. `php -l` clean on all files; `schedule:list` shows both
  new tasks; `failed_jobs`=0.

### Impact
- Purely additive: 2 jobs, 2 mailables + 2 blades, 2 service methods, 2 enum cases, 3 columns across 2 tables,
  2 scheduler entries. No existing table/route/controller/auth/job altered; existing reminders + digest untouched.

## 2026-07-02 — Free actions: stop counting 'shortlisted' (feature disabled)

### Why
- Firm "H Mistry & Associates" (firm_id 25) reported having 0 free actions left despite never using
  interview invite / schedule interview. Audit showed all their usage was `shortlisted` (Save Candidate)
  rows in `recruiter_actions` (3 distinct students, June 24–25). Since the shortlist feature has been
  temporarily removed from the UI, historical shortlist rows should no longer consume the free limit.

### Change
- `app/Helpers/FreeActionsHelper.php` — `getUsedCount()` no longer counts `shortlisted`; `saved` is
  hard-coded to 0 (key kept for API shape). Limit now = distinct `interview_invite` + `interview_requested`
  students only. Inline note explains how to re-enable if the shortlist feature returns.
- No DB changes; existing `shortlisted` rows left intact (students keep their activity history).
- Limit-check gates in UserController / JobsController for shortlisting left in place (dormant while UI is off).

### Verified (tinker, local copy of live DB)
- Before: `getStatus(25)` → used=3, saved=3, remaining=0. After: used=0, remaining=2.
- Fixes every firm that burned free actions on Save Candidate, not just firm 25.

## 2026-07-04 — Companies "Openings" = total vacancies (SUM), not job-post count

### Why
- Student-side /companies cards showed the NUMBER of active job posts as "Openings"
  (e.g. Job A openings=1 + Job B openings=2 displayed as 2, not 3). The `jobs.openings`
  vacancy column was never aggregated. The list page also silently counted Draft jobs
  (filtered on `is_active` only), while the detail page required `status='Active'`,
  so the two pages could disagree.

### Change (`app/Http/Controllers/API/FirmController.php` — queries only, no route/schema changes)
- `getCompanies()` derived table: `COUNT(*)` → `SUM(COALESCE(openings, 1))`, filter
  tightened from `is_active = 1` to `is_active = 1 AND status = 'Active'`.
- `getCompanyDetails()` scalar subquery: `count(*)` → `coalesce(sum(coalesce(jobs.openings, 1)), 0)`
  (filter already had `status = 'Active'`).
- `openings_breakdown`: `count(*)` → `SUM(COALESCE(openings, 1))` + added `status = 'Active'`
  filter, so per-type counts add up to the headline number.
- NULL openings counts as 1 (a live job is ≥1 vacancy; legacy rows predate the UI default of 1).
  Draft and Closed jobs are excluded everywhere; matches the student job feed filter.
- Both `current_openings` response fields now cast `(int)` — SUM arrives as a string from PDO,
  unlike the old COUNT(*), and the frontend types it as number.

### Verified (tinker against local DB, real controller methods, test rows cleaned up)
- Firm 16 (active jobs 2+2+1): list card and detail both 5 (was 3 under COUNT).
- Edge rows added (openings=NULL Active, Draft openings=2, Closed openings=10): both pages
  → 6 (NULL counted as 1, Draft/Closed excluded); breakdown summed to 6. After cleanup → 5.
- JSON types confirmed integer on both endpoints; `php -l` clean.
- No frontend changes needed: field names/consumers unchanged (`companies.index.tsx` card,
  `companies.$id.index.tsx` badge + `hasOpenings` gate). `/master/companies`, admin pages,
  per-job `openings` displays (student dashboard, firm-jobs) untouched.

## 2026-07-04 — Unread-messages reminder EMAIL system removed entirely (never shipped)

### Why
- Final messaging notification strategy confirmed: EMAIL fires only when a NEW
  conversation is initiated (NewMessageRequestMail, both directions — unchanged);
  replies/existing-conversation messages notify via PUSH alone (aggregation +
  cooldown gates unchanged). The 3-hourly unread-messages reminder email
  (added 2026-07-03, never deployed to production) contradicts this and was
  removed completely rather than left dormant.

### Removed
- `routes/console.php` — `send-unread-messages-email` schedule entry + the
  `SendUnreadMessagesEmailJob` import (tombstone comment left in place).
- `app/Jobs/SendUnreadMessagesEmailJob.php` — deleted.
- `app/Mail/UnreadMessagesReminderMail.php` — deleted.
- `resources/views/emails/messaging/unread-reminder.blade.php` — deleted.
- `database/migrations/2026_07_03_000003_create_user_message_email_state_table.php` — deleted.
- `user_message_email_state` table dropped from the dev DB (was empty);
  db_changes.txt CREATE block replaced with a do-not-create tombstone that
  includes the DROP statement in case an earlier copy was ever applied.
- Stale notifyPeer comments updated to the final strategy wording.

### Kept (explicitly)
- `NewMessageRequestMail` + both queue sites in startConversation (new-conversation email).
- All push logic: pushToPeer gates/aggregation, NotificationHelper::create hook,
  `send-unread-digest-push` hourly job (push-only, bell-based — unrelated to chat email).
- `NewMessageReplyMail` class stays dormant (pre-existing, zero dispatch sites).

### Verified
- `php -l` clean on routes/console.php + MessagingController.php.
- `php artisan schedule:list` boots clean; send-unread-messages-email gone,
  send-unread-digest-push still listed.
- Repo-wide grep: zero remaining references outside changelog history and the
  db_changes tombstone. Dev queue had no pending jobs of the removed class.

## 2026-07-05 — Fallback flush for suppressed chat pushes (closes the "stuck message" gap)

### Why
- pushToPeer suppresses pushes while the recipient is active (180s window) and relies
  on the NEXT message event to flush the aggregate. If a user closed the app right
  after a message was suppressed and no further message arrived, that message was
  never notified on ANY channel (no reply email by design; digest push counts only
  bell/recruiter actions, not chat). Confirmed against prod logs of 2026-07-04/05.

### Change (additive — no existing gate/cooldown/aggregation/collapse behaviour altered)
- **`app/Jobs/FlushSuppressedMessagePushJob.php`** (new, tries=1, non-throwing):
  delayed re-check that pushes ONLY if all hold — (1) recipient had NO activity at/after
  the LAST suppressed message (activity after it = open tab got it via Reverb);
  (2) conversation still unread for them (DB counters, not cache; blocked/ignored skip);
  (3) aggregate counter still pending (0 = an event push already flushed);
  (4) shared per-conversation cooldown slot free (atomic Cache::add). Payload/deep-link/
  `conv_{id}` collapse tag identical to pushToPeer; body = attachment-aware
  `last_message_preview` snapshot.
- **`MessagingController`**: active-skip branch of `pushToPeer` now records the
  suppression timestamp (`msg_push_flush_ts_*`, TTL = aggregate TTL) and schedules ONE
  flush job per conversation+recipient burst via atomic `Cache::add` on
  `msg_push_flush_*` (TTL = delay + 60s), `->delay(PUSH_FLUSH_DELAY_SECONDS = 120)`.
  Push constants made public (single source of truth for the job). Comparing against
  the suppression moment — not a fixed window — is what makes the 2-min delay safe:
  a user who closes the app the second a message lands still gets the fallback.
- Worst-case notification delay for a "last message before silence" goes from
  infinite to ~2 minutes; users active after the message are never double-pushed.

### Verified (tinker on dev DB, transactional, rolled back; cache keys cleaned)
- Two suppressions → exactly ONE delayed job (available_at = +119s), aggregate=2.
- Activity after suppression → skip, aggregate preserved. Inactive + unread + pending
  → 1 push queued: title "2 new messages from {firm}", body = preview, tag conv_{id};
  aggregate cleared + cooldown set. Immediate re-flush → blocked by cooldown.
  pending=0 → skip; unread=0 → skip; blocked conversation → skip. php -l clean.

### Deploy
- Queue-worker restart required (new job class). No schema/route/env changes.

## 2026-07-05 — Activity Tracker: job_applied action added + entity names in meta

Root cause of the "Devesh Mishra applications missing from Activity Tracker" audit:
`job_applied` was never a tracked action — no `ActivityType` case and no
`ActivityTracker::log` call in the apply flow (affected ALL students since launch,
not just Devesh). Also: tracker Details rendered raw IDs ("Candidate #112") because
meta only stored IDs.

### Changes
- **`app/Enums/ActivityType.php`**: new student case `JOB_APPLIED = 'job_applied'`.
- **`app/Http/Controllers/API/JobsController.php`**:
  - `applyJob`: logs `job_applied` after `DB::commit()` (per ActivityTracker contract)
    with `application_id, job_id, job_title, firm_id, firm_name, payment_source` —
    names passed inline (all in scope), zero extra queries.
  - student interview-accept: meta now also carries `firm_id` so firm name resolves.
- **`app/Jobs/LogActivityJob.php`**: new `enrichMeta()` — central name resolution at
  write time for the shared meta ID conventions (`student_id`→`student_name` via
  users, `firm_id`→`firm_name` via firm_profiles [firm_id is ALWAYS firm_profiles.id
  in tracker meta], `job_id`→`job_title` via jobs). Skips keys the caller pre-filled;
  runs in the queue worker (zero request-path cost); own try/catch — a failed lookup
  inserts the row with IDs only (old behaviour). This gives ALL 8 existing call sites
  names without touching their controllers. Names are frozen at write time so history
  survives renames.
- **`app/Http/Controllers/API/CreatorMarketplaceController.php`**: `content_submitted`
  meta now includes `project_title` + `firm_id` (both already in scope).
- No schema change (`meta` is JSON), no API change (meta passed through), old rows
  untouched — frontend falls back to `#ID` format for them.

### Verified
- `php -l` clean on all 4 files.
- Real end-to-end run (tinker, dev DB): `LogActivityJob('student', 178, 'job_applied',
  [job_id 18, firm_id 48, student_id 178, ...])->handle()` inserted meta with
  `student_name: "Devesh Mishra"`, `firm_name: "CA Pritam Mahure & Associates"`,
  `job_title: "Articleship Trainee - Indirect Tax (GST & Customs)"`; test row deleted.

### Deploy
- Queue-worker restart required (`php artisan queue:restart`) — LogActivityJob code
  changed. Constructor signature unchanged, so any in-flight queued payloads remain
  compatible. No schema/route/env changes.
- Historical backfill of `job_applied` rows from `applications` (applied_at →
  created_at) is possible but NOT included — decide separately.

## 2026-07-05 — Dev-only email template gallery: browser previews at /dev/emails

Design/modify any email template by viewing it in the browser instead of sending
test mail. Edit the blade under `resources/views/emails/`, refresh the tab.

### Added
- **`app/Http/Controllers/Dev/MailPreviewController.php`** (new): registry of all
  23 mailables → 27 preview keys (variant keys where behaviour branches:
  accepted/declined invite response, 1h/24h reminders, normal/final response
  reminder, firm/candidate message request, student/firm welcome), each built with
  realistic sample data (Devesh Mishra / CA Pritam Mahure & Associates / the GST
  articleship job). `GET /dev/emails` = grouped index page; `GET /dev/emails/{key}`
  returns the Mailable (Laravel renders its HTML directly). Adding a template = one
  registry entry.
- **`routes/web.php`**: the two routes, registered ONLY when
  `app()->environment(['local','development'])`; the controller re-checks the same
  gate (404 otherwise) so production can never serve these. The older TEMPORARY
  `/mail-preview/reengagement` route kept (its query params preview re-engagement
  variants) with a note that /dev/emails supersedes it.

### Verified
- `php -l` clean; all 27 registry entries `render()`ed OK via tinker (0 failures —
  proves every blade gets all variables it needs).
- HTTP smoke test on `php artisan serve --port=8123`: index 200 (4.3 KB),
  `welcome-student` 200 (8.7 KB), `interview-scheduled` contains the sample
  candidate/job strings, unknown key → 404.

### Deploy
- Nothing to do — routes don't register on production (APP_ENV=production). Purely
  additive; no existing route/controller touched except the routes-file comment.

## 2026-07-05 — Premium reusable email layout + student feature-release campaign

New brand-reference email system (600px, blue header, dark-navy 4-column footer,
light/dark mode) for campaign-grade emails. ADDITIVE — the existing
emails/layouts/app.blade.php and all transactional templates are untouched.

### Added
- **`resources/views/emails/layouts/premium.blade.php`** (new, reusable): shared
  header (SYS logo tile, brand + tagline, right-side positioning line) and footer
  (brand block, Quick Links, Company, Follow Us social circles, contact row,
  copyright) around a `@yield('content')` body. 100% table-based + inline CSS;
  `<style>` only carries mobile stacking (≤620px: .stack-card/.gap-col/.btn-block/
  .hide-sm) and dark-mode overrides (`prefers-color-scheme: dark` + `[data-ogsc]`
  for Outlook apps; #0D1117/#161B22/#F8FAFC/#94A3B8/#30363D). Container uses
  width="600" attr + CSS width:100%;max-width:600px (Outlook vs responsive).
  Icons are Unicode glyphs in coloured circles — zero images. Footer links point
  at real frontend routes; social icons link to the site root (TODO in-file:
  swap in real profile URLs when published).
- **`resources/views/emails/campaign/student-feature-release.blade.php`** (new):
  re-engagement + feature-release body — hero, "What's New for You" 4 feature
  cards (td-as-card pattern → equal heights, spacer tds hidden on mobile),
  "We miss you!" CTA card (bulletproof table button, full-width on mobile,
  trust line), 3-card "Stay updated" strip.
- **`app/Mail/StudentFeatureReleaseMail.php`** (new): Mailable
  (name, ctaUrl → default {frontend}/login), EmailPurpose::REENGAGEMENT,
  subject "Big updates are now live on StartYourStory 🚀".
- Registered in /dev/emails gallery ('student-feature-release', Campaigns group).

### Verified (rendered via Playwright screenshots, 4 modes)
- Desktop 700px viewport: container exactly 600px; 4 feature cards equal-height;
  trust line single-line. Dark mode matches spec palette. Mobile 390px: container
  shrinks to viewport (no horizontal scroll), cards stack with 10px gaps, CTA
  button spans the card, footer stacks, header right-tagline hidden.
- Fixed en route: `display:block` on the button table broke width:100% (anonymous
  table shrink-to-fit) → block only the anchor; fixed CSS width:600px preventing
  mobile shrink → width:100%;max-width:600px.
- All 28 /dev/emails previews render OK (0 failures); php -l clean.

### Deploy
- Nothing required — new files only; nothing sends this campaign yet (send wiring
  is a separate decision: campaign system or a console command).

## 2026-07-05 — Email preview mode toggle + premium layout uses real logo

- **`Dev/MailPreviewController@show`**: new `?mode=light|dark` query param pins the
  preview's colour scheme regardless of the OS/browser theme (the template kept
  "showing dark" for anyone on a dark Windows theme — that was prefers-color-scheme
  working as designed, but you couldn't design the light variant without switching
  the OS). Preview-only trick: rewrites the `(prefers-color-scheme: dark)` media
  condition to `not all` (never) / `all` (always). No param = follows the browser,
  like a real client. Index page now shows ☀ light / 🌙 dark pill links per template.
- **`emails/layouts/premium.blade.php`**: header (28px in 44px tile) and footer
  (20px in 30px tile) now use the real logo `https://startyourstory.in/favicon.ico`
  (same asset the transactional layout uses) instead of the "SYS" text span;
  alt="SYS" keeps a text fallback while images are blocked.
- Verified over HTTP (Playwright): `?mode=light` under a dark browser renders the
  light bg (#F5F7FA ✓), `?mode=dark` under a light browser renders #0D1117 ✓,
  no param auto-follows the browser theme ✓; logo visible in the header tile.

## 2026-07-05 — /dev/emails test-send route + ?name override (premium system complete)

Completes the premium email system ask. Audit confirmed everything else already
existed (premium layout, campaign template, mailable, 28-template preview
gallery with light/dark toggle) — reused untouched, zero redesign.

### Added
- **`Dev/MailPreviewController@send`** + route `GET /dev/emails/{key}/send`
  (same local/development env gate): sends a REAL test email for ANY registry
  template. Params: `?to=` (default tusharbhise908@gmail.com), `?name=` (sample
  candidate name), `?via=smtp` (force a configured mailer — local default is
  'log', which only writes to laravel.log; response says so explicitly).
  Deliberately reuses the EXACT production pipeline: creates the same
  email_logs row EmailNotificationService::queue() writes (subject prefixed
  [TEST]) and runs DispatchMailJob via dispatchSync — sender-identity
  resolution by purpose, send, markSent/markFailed — so a test send exercises
  precisely what production executes. 404 unknown key, 422 bad ?to/?via.
- **`registry(?string $candidate)`**: optional candidate-name override;
  `?name=` now also works on previews (`/dev/emails/{key}?name=Tushar`).
- Index page: ✉ send-test link per template + params hint line.

### Verified (live, port 8125)
- Send via default log mailer → status true + log-mailer warning note;
  email_logs #1043 status=sent, purpose=reengagement, sender_identity=marketing.
- REAL SMTP send (`?via=smtp`) delivered to tusharbhise908@gmail.com —
  email_logs #1044 status=sent, no error.
- Preview `?name=Tushar` renders "Hello Tushar,". Unknown key → 404;
  invalid ?to → 422. Index 200; all 28 previews render (0 failures); php -l clean.

### Regression safety
- No existing file's behaviour changed: EmailNotificationService, DispatchMailJob,
  queue jobs, campaigns, transactional templates and layouts untouched. New route
  is additive and never registers on production (env gate + in-controller gate).
- Existing transactional templates were NOT migrated to the premium layout on
  purpose (visual change to production emails needs design review; migration is
  a one-line `@extends` swap per template when desired).

## 2026-07-05 — ALL email templates migrated to the premium layout

Every email the platform sends now renders inside emails/layouts/premium.blade.php
(shared header/footer, dark mode, responsive) — layout used exactly as-is (incl.
the simplified footer edited earlier today); zero layout redesign.

### Migrated (22 templates; body content preserved verbatim)
- 18 that extended layouts.app (verify-email, welcome, firm-approved,
  firm-rejected, password-reset, support-ticket-closed, creator/selected,
  creator/accepted, application/digest, firm/applicant-reminder, interview/
  scheduled, accepted, rejected, reminder, invite, invite-response,
  response-reminder, reschedule-accepted) via a mechanical recipe:
  extends swap ('heading' param → 'title': the old layout NEVER rendered it, so
  nothing visible was lost); body wrapped in one dm-p typography div carrying the
  old layout's exact `content p` styles (16px/1.8 #4b5563); `.button` /
  `.info-box` classes (styles lived in the old layout's <style>) inlined with
  their original values (+dm-btn; firm-rejected's gray support button kept gray,
  no dm-btn). Inline-styled detail cards/tables untouched — light tint + dark
  text stays readable in both modes. welcome h1/h3 got dm-h (inline #111827
  would vanish on the dark panel).
- 4 standalone full-HTML shells rebuilt as premium bodies, content preserved:
  messaging/new-request, messaging/new-reply, referral/payout-request,
  reengagement (entire userType×lifecycle @php block kept verbatim; preheader =
  lead, title = heading; motivation/benefits/CTA/info sections identical).
- Gotcha fixed en route: the literal word "@php" inside a Blade comment in the
  rewritten reengagement was captured by Blade's php-block extractor (runs
  before comment stripping), swallowing @section — "Cannot end a section
  without first starting one". Reworded the comment.

### Verified
- 28/28 gallery previews render with premium header+footer markers and zero
  old-layout markers; all 9 reengagement variants (3 userTypes × 3 states) OK.
- Playwright screenshots light+dark: verify-email, interview-scheduled,
  application-digest, re-engagement — body content identical to before in light
  mode; dark mode readable everywhere (dm-p wrapper text flips, cards stay
  light-tinted).
- grep: no template outside layouts/ references emails.layouts.app.

### Rollback / notes
- layouts/app.blade.php kept on disk (now unused) — reverting any single
  template = restore its old extends/wrapper from git/backup; no code outside
  resources/views/emails changed in this step.
- Mailables, EmailNotificationService, jobs, routes: untouched. The old
  /mail-preview/reengagement route now shows the premium version (expected).
- Visual change to production emails is the point of this migration — verify a
  couple in real clients via /dev/emails/{key}/send?via=smtp before deploy.

## 2026-07-05 — Premium email layout polish (width, spacing, dark-mode cards)

Layout-level refinements only — header/footer markup and all body content
untouched.

### premium.blade.php
- Container 600px → **680px** (`width="680"` attr for Outlook + CSS
  width:100%;max-width:680px); mobile stacking breakpoint 620px → 700px.
- Header→body gap: content padding 30px → **42px top** (34px sides, 18px bottom).
- **Body→footer transition**: new panel row with settle space + hairline
  divider (#E5E7EB / dark #30363D) before the navy footer.
- Dark mode "black dominance" fixed: page (.dm-bg) now **#161B22** with the
  email panel (.dm-panel) #0D1117 — the mail reads as an elevated surface.
- New `.dm-row-a/.dm-row-b` classes (dark zebra rows #161B22/#0D1117), in both
  the prefers-color-scheme and [data-ogsc] blocks.

### Templates (20 files, classes only — light mode pixel-identical)
- Every light-tinted inline card/box now carries dm-* classes so dark mode
  renders it #161B22 bg / #30363D border / #F8FAFC-#94A3B8 text: info boxes,
  interview detail cards (incl. red final-reminder + rejection variants),
  messaging preview quotes, digest/applicant tables (header row stays navy,
  zebra rows via dm-row-a/b, cell text dm-h/dm-p), reengagement motivation/
  benefit/amber boxes, welcome coupon box (code text dm-hi), greeting/lead
  lines with inline colours.

### Verified
- 28/28 previews render with premium header+footer; container measured exactly
  680px in Chromium. Screenshots: interview-scheduled dark (dark cards, elevated
  panel, divider), application-digest dark (navy header row + dark zebra rows),
  student-feature-release light 680px (roomier cards/spacing). php -l n/a
  (blade); view:clear + full re-render clean.

## 2026-07-05 — 3 email fixes: firm welcome coupon, re-engagement dark-mode strong, firm campaign template

1. **Firm welcome coupon "missing"** — audit outcome: NOT a production bug. The
   only active dispatch (UserController@verify → sendWelcomeEmail) passes
   `$user->referral_code` as the coupon for ALL roles, all 44 firms have codes,
   and the blade's coupon box has no role gate. The only firm welcome that ever
   rendered without a coupon was the /dev/emails 'welcome-firm' PREVIEW sample,
   seeded with null. Fixed the sample → `WELCOME100` (desc documents that
   production passes the firm's referral_code). No backend logic changed.
2. **Re-engagement dark mode** — "less than two minutes" invisible: the amber
   info-box `<strong>` carried inline `color:#0f172a` and was the single #0f172a
   in the file without a `dm-h` class → near-black on the #161B22 dark card.
   Added `class="dm-h"` (dark → #F8FAFC; light unchanged). Verified computed
   color rgb(248,250,252) in dark via Playwright.
3. **New firm campaign template** — `emails/campaign/firm-reengagement-feature.blade.php`
   (+ `App\Mail\FirmFeatureReleaseMail`, purpose REENGAGEMENT, ctaUrl default
   {frontend}/login) — firm copy per spec, exact student-template styling: same
   premium layout, hero, flanking-lines title, td-as-card feature cards (2 ×
   half-width), CTA card, 3 benefit cards. Registered in /dev/emails
   ('firm-reengagement-feature', Campaigns) → previewable + test-sendable.

### Verified
- 29/29 previews render; firm welcome shows "Your Welcome Coupon"+WELCOME100;
  dark-mode contrast asserted; firm campaign screenshotted light+dark (matches
  student template styling). Nothing else modified.

## 2026-07-05 — Campaign commands: mail:student-reengagement-feature / mail:firm-reengagement-feature

Reusable campaign infrastructure (thin commands → services → standard mail
pipeline). No direct Mail::send; nothing existing modified except two additive
wrapper methods on EmailNotificationService.

### Added
- **`app/Services/Campaign/CampaignRecipientService.php`**: recipient resolution
  (role + is_deleted=0 + non-empty email; select id,name,email). Full audience
  streams via lazyById(500) — bounded memory; --limit uses a bounded query;
  byEmail() resolves the user's real name (stub row for arbitrary addresses).
  Future segments (creators, inactive, incomplete-profile) belong here.
- **`app/Services/Campaign/CampaignEmailService.php`**: iterates recipients and
  queues each via EmailNotificationService (→ email_logs row + DispatchMailJob).
  Per-recipient safety: invalid emails skipped, exceptions caught+logged+counted,
  one bad recipient never aborts. Progress callback feeds command output.
  New campaign = one match-arm + one mailer wrapper.
- **`EmailNotificationService`**: two public campaign wrappers using the private
  queue() primitive — sendStudentFeatureReleaseEmail (subject "🚀 Big Updates Now
  Live on StartYourStory", fallback name Candidate) and
  sendFirmFeatureReleaseEmail ("🚀 New Hiring Features Now Live on
  StartYourStory", fallback Hiring Partner); ->subject() set on the mailable so
  the delivered subject matches the log row. Purpose: REENGAGEMENT.
- **Commands** (thin: options → services → output):
  `mail:student-reengagement-feature` and `mail:firm-reengagement-feature`, both
  with --dry-run (count + sample table, sends nothing), --limit=N, --email=addr,
  --force. FULL-audience runs prompt for confirmation unless --force (schedulers
  must pass it — non-interactive confirm defaults to abort). Shared abstract
  base class (skipped by command discovery). Existing mail:reengagement untouched.

### Verified (live)
- php -l clean ×6; both commands registered in artisan list.
- Dry runs: 122 students / 44 firms found, --limit=2 caps the would-queue count
  and sample table, nothing sent.
- End-to-end: --email run queued email_log #1048 → `queue:work --once` processed
  DispatchMailJob → status=sent, purpose=reengagement, sender resolved
  (From: StartYourStory <info@startyourstory.in>), subject correct, body present
  in mail log (MAIL_MAILER=log locally).
