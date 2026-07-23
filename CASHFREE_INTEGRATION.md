# Multi-Gateway Payments (PhonePe + Cashfree) — Integration Notes

Cashfree added alongside PhonePe with an **admin-selectable active gateway**. All
existing PhonePe payments, pending orders, webhooks, history, invoices, emails,
referral payouts and analytics are preserved. Adding a future gateway (Razorpay…)
needs only a new gateway class + one factory line — no controller, route, schema
or business-logic change.

## Architecture

- **`app/Contracts/PaymentGateway.php`** — gateway-agnostic contract:
  `createOrder`, `verifyPayment` (normalized), `parseWebhook` (verifies signature
  + normalizes), `refund` (refund-ready), `name`.
- **`app/Services/Payment/PhonePeGateway.php`** — unchanged on the wire; wraps its
  existing status/webhook logic into the normalized methods.
- **`app/Services/Payment/CashfreeGateway.php`** — new. Cashfree Orders API
  (2023-08-01), raw `Http` (matches PhonePe style). Order create → payment session,
  order status verify, HMAC webhook verify, refund.
- **`app/Services/Payment/PaymentGatewayFactory.php`** — resolves `phonepe|cashfree`.
- **`app/Services/Payment/PaymentManager.php`** — `active()` (admin default),
  `gateway($name)` (from stored row). Injected via DI. **No controller does
  `new …Gateway()` anymore** — the only instantiation is inside the factory.
- **`app/Services/Payment/Settlement/{Wallet,FirmPremium,CreatorEscrow}SettlementService.php`**
  — one shared, row-locked, amount-verified, idempotent settlement per domain,
  called by BOTH verify and webhook. CA Library keeps its existing shared
  `settlePayment()` (adapted to the normalized result).

**Resolution rule:** initiate = active gateway (name stamped on the row); verify =
gateway read from the row; webhook = the endpoint's own gateway. This is what keeps
a pending PhonePe order verifiable after the admin switches to Cashfree.

## Files modified / added

Backend (added): CashfreeGateway, PaymentManager, 3 settlement services.
Backend (modified): PaymentGateway contract, PhonePeGateway, PaymentGatewayFactory,
PhonePeWalletController, PhonePeFirmController, PhonePeEngagementController,
CaTestSubmissionController, AdminSystemSettingController, PremiumActivationEmailHelper,
`config/services.php`, `routes/api.php`, `SystemSettingsSeeder.php`, `db_changes.txt`,
`resources/views/emails/firm/premium-activated.blade.php`.

Frontend (added): `src/services/paymentCheckout.ts` (gateway launcher).
Frontend (modified): `src/services/api.ts` (initiate/verify → neutral endpoints +
checkout fields), `admin.system-settings.tsx` (gateway dropdown), and 5 launch
sites (wallet.recharge, firm.payments, creator-marketplace.payment.$engagementId,
ca-library_.my-library, UploadAnswerSheetModal) now call `launchCheckout()`.

## Database changes (in `db_changes.txt`, dated 2026-07-23 — run manually)

- `wallet_recharges.payment_method`, `firm_subscriptions.payment_gateway`,
  `creator_engagement_payments.payment_method`: ENUM → **VARCHAR(20)** (superset of
  existing values; no data migration). `ca_payments.gateway` already VARCHAR.
- New `system_settings` row `default_payment_gateway='phonepe'` (also in
  `SystemSettingsSeeder`, idempotent).

## Routes (all in `routes/api.php`)

- Gateway-neutral (preferred): `POST /wallet/recharge/{initiate,verify}`,
  `POST /payments/{initiate,verify}`,
  `POST /creator-marketplace/engagements/{id}/payment/{initiate,verify}`.
  CA already neutral (`/ca-library/submissions/{id}/pay`, `/ca-library/payments/verify`).
- Legacy `/phonepe/{initiate,verify}` kept as compatibility aliases.
- New Cashfree webhooks: `/wallet/recharge/cashfree/webhook`,
  `/payments/cashfree/webhook`, `/creator-marketplace/payments/cashfree/webhook`,
  `/ca-library/payments/cashfree/webhook`. Existing PhonePe webhooks unchanged.
- **Cashfree uses per-order `notify_url`** (`order_meta.notify_url`, set by each
  domain's initiate to its own webhook above) so each payment's S2S callback goes
  straight to the correct domain endpoint — **no dashboard webhook registration
  needed for Cashfree**. PhonePe has no per-order webhook field, so it stays
  dashboard-configured. Both still verify signature + settle idempotently, and the
  frontend verify remains the primary settlement path.

## Config / env vars (add to `.env` for Cashfree)

All Cashfree config is **environment-only** — `config/services.php` maps the vars
below with **no hardcoded defaults**, and `CashfreeGateway` reads only from config.
Empty placeholders live in `.env.example`. If Cashfree is selected while any of
`CASHFREE_APP_ID`, `CASHFREE_SECRET_KEY`, `CASHFREE_BASE_URL`, `CASHFREE_API_VERSION`
is blank, the gateway throws a clear "not configured" error (fails loud, never
silently falls back). If Cashfree is unconfigured and the admin hasn't switched,
the app runs entirely on PhonePe.

```
CASHFREE_APP_ID=          # sandbox App ID (then production)
CASHFREE_SECRET_KEY=      # sandbox Secret Key — also signs webhooks (HMAC)
CASHFREE_API_VERSION=     # Cashfree x-api-version date, e.g. 2023-08-01
CASHFREE_BASE_URL=        # sandbox https://sandbox.cashfree.com/pg | prod https://api.cashfree.com/pg
CASHFREE_MODE=            # sandbox | production (also drives the frontend JS SDK)
```
Cashfree signs webhooks with the secret key (no separate secret to set). Webhook
URLs are supplied per-order via `notify_url`, so no dashboard webhook registration
is required — you may still add one dashboard webhook as a belt-and-braces backup.
Note: `notify_url` must be a **public HTTPS** URL for Cashfree to reach it, so on a
local `http://127.0.0.1` backend the webhook won't fire — the frontend verify flow
still settles the payment (use a tunnel like ngrok to exercise the webhook locally).

## Admin panel

Platform Settings → Payment Settings → **Default Payment Gateway** dropdown
(PhonePe / Cashfree). Saves instantly via `POST /admin/system-settings/{key}`
(validated to `phonepe|cashfree`, audited). Switching requires no deploy.

## Testing checklist

Run `db_changes.txt`, seed the setting, add Cashfree `.env`, then for each domain
(Wallet, Firm Premium, Creator Escrow, CA Library) with the admin toggle on
**phonepe** then **cashfree**:
- Initiate → hosted checkout → success → verify credits/activates exactly once.
- Duplicate webhook + concurrent verify → single settlement (lock-serialized).
- Failed / amount-tampered / pending → no settlement.
- Pending PhonePe order verifies after switching active to Cashfree.
- Regression: PhonePe history, invoices, referral payouts, emails, analytics intact.

## Rollback

- Set the admin gateway back to `phonepe` (instant, no deploy).
- VARCHAR widening is backward-compatible; ENUM restore SQL is in `db_changes.txt`.
- Settlement services moved logic verbatim; per-domain phased validation is the
  primary safety net.

## Known follow-ups (not changed — flagged only)

- `firm_subscriptions.razorpay_response` column name is legacy; it stores every
  gateway's JSON (rename is cosmetic + risky, left as-is).
- `CreatorMarketplaceController` has a pre-existing unused `use App\Contracts\PaymentGateway;` import.
- Refund methods exist on both gateways but no business flow calls them yet ("refund-ready").
