## Security & Compliance First
- Store `secret_key` and `webhook_secret` using field-level encryption (Laravel encrypt/decrypt accessors) and never log secrets.
- Validate and sanitize all inputs; return 422 with `{success:false, message, errors:{field:[messages]}}` consistently.
- Enforce role-based authorization on Admin and Trainer endpoints; ensure client can only access own invoices.
- Add DB indexes on frequently queried columns and rate-limit payment endpoints to prevent abuse.

## Phase 1: Schema Updates (Migrations)
- Refactor `payment_gateways` from JSON to structured columns:
  - Columns: `gateway_name`, `gateway_type (stripe|paypal|manual)`, `public_key`, `secret_key (encrypted)`, `webhook_secret (encrypted)`, `account_id (nullable)`, `is_default (boolean)`, `status (boolean)` + timestamps.
  - Data migration: If old `credentials` JSON exists, parse and populate new columns; mark Stripe as default if none.
- Ensure related tables exist/updated:
  - `invoices` (already present): include `payment_method`, `transaction_id`, `status` values `pending|paid|failed`.
  - `invoice_items` (already present).
  - `trainer_bank_accounts`: as specified (trainer Stripe Connect account details + `verification_status`).
  - `transactions`: ensure `gateway_id`, `currency`, `response_data (JSON)` present with indexes.
  - `payouts`: fields `trainer_id, amount, payout_status, stripe_payout_id` with indexes.

## Phase 2: Core Services
- Update `CentralGatewayService` to read structured columns and expose:
  - `getEnabledGateways()`, `getDefaultGateway()`, `getGatewayCredentials(type)` with decrypted secrets.
- Update `StripePaymentService` & `PayPalPaymentService` to accept credentials from `CentralGatewayService` and support sandbox.
- Implement commission calculation utility usable in payment and payout flows.

## Phase 3: Admin Panel — Gateways & Billing
- Controllers & Views (under `resources/views/admin/billing/*`):
  - Payment Gateways CRUD: forms with discrete fields (no JSON), enable/disable, set default (unique constraint on `is_default`).
  - Invoices listing: filter by status; show payment method and transaction id.
  - Transactions listing: show gateway, amount, status, created_at; link to invoice.
  - Payouts listing: show trainer, amount, status, date; link to transaction.
  - Trainers’ Bank Accounts: list with `verified|pending|disconnected` controls.
- Business rules:
  - Only one default gateway (`is_default=true`); switching updates previous default to false.
  - Manual gateway appears in client list with admin-provided instructions (content configurable in view/setting).

## Phase 4: Trainer API
- Endpoints:
  - `POST /trainer/bank/connect` → Create/attach Stripe Connect account; store in `trainer_bank_accounts` with returned `account_id` & status.
  - `GET /trainer/bank/details` → Return account and `verification_status` from DB; optionally refresh from Stripe.
  - `DELETE /trainer/bank/disconnect` → Mark as disconnected and clear `account_id`.
  - `POST /trainer/invoice/create` → Select client; auto-fetch assigned workouts; compute totals; set `due_date`, `notes` and create `invoice_items`.
  - `GET /trainer/invoices` → Paginated list with statuses.
  - `GET /trainer/payouts` → Paginated list, showing `amount`, `payout_status`, `stripe_payout_id`.
- Stripe Connect payouts:
  - On successful client payment, compute commission, record transaction, and schedule transfer/payout to trainer’s connected account.

## Phase 5: Client API
- Endpoints (already present; refine):
  - `GET /client/payment-methods` → Read from `payment_gateways` (Stripe, PayPal, Manual active only).
  - `POST /client/pay` and `POST /client/billing/invoices/{invoiceId}/pay` →
    - Validate `method`, `token`, and `invoice_id` when not in route; bind route `{invoiceId}` properly.
    - Stripe: use PaymentIntent with `payment_method_id`; PayPal: order capture; Manual: mark invoice `pending` and include instructions.
  - `POST /client/payment/retry` and `/client/billing/invoices/{invoiceId}/retry` → Only when invoice `failed`.
  - `POST /client/payment/cancel` → Cancel `failed/pending` invoices.
  - `GET /client/payment/details/{invoice_id}` → Return confirmation payload (amount, date, trainer, service, transaction id, method).
- Response format: always `{success, data, message}`; 422 contains `errors` keyed by field.

## Phase 6: Webhooks & Status Sync
- Stripe Webhook:
  - Verify signature using `webhook_secret` from `payment_gateways`.
  - Handle `payment_intent.succeeded|payment_intent.payment_failed` to update `transactions` and `invoices`.
  - For Connect payouts, handle `payout.paid|payout.failed` to update `payouts`.
- PayPal Webhook:
  - Verify using credentials; handle `PAYMENT.CAPTURE.COMPLETED|DENIED` similarly.

## Phase 7: Routes & Method Signatures
- Admin: `/admin/payment-gateways` (CRUD + set default), `/admin/invoices`, `/admin/transactions`, `/admin/payouts`, `/admin/trainers/banks`.
- Trainer: `/trainer/bank/*`, `/trainer/invoice/create`, `/trainer/invoices`, `/trainer/payouts`.
- Client: keep existing `/client/*` and `/client/billing/*` aliases; ensure controller methods accept `PaymentGatewayService` first, then `Request`, then optional `{invoiceId}` to match Laravel injection.

## Phase 8: Validation & Error Messaging
- Use Laravel validator in all endpoints with clear messages:
  - Missing `invoice_id` on alias routes → "The invoice id is required." (422 with `errors.invoice_id`).
  - Invalid `method` → 422 with `errors.method`.
  - Ownership checks → 404 "Invoice not found for this client."
  - Business rules (paid/failed) → 409 or 422 with informative `message`.

## Phase 9: Testing (Sandbox)
- Unit tests: Admin gateways CRUD and default switching; Trainer bank connect/disconnect; Invoice creation totals.
- Integration tests: Client pay/retry/cancel, webhook handling for Stripe/PayPal.
- Manual testing scripts with Stripe/PayPal sandbox keys; seed demo data.
- Aim for 80%+ coverage; document scenarios and expected outcomes.

## Phase 10: Performance & Indexing
- Add indexes: `transactions (invoice_id, trainer_id, gateway_id, status)`, `invoices (client_id, status)`, `payouts (trainer_id, payout_status)`.
- Paginate lists; avoid N+1 with eager loads (`trainer`, `client`, `items`).

## Backward Compatibility & Rollout
- Migrations include data backfill from existing JSON `credentials` when present.
- Retain existing client route aliases; no UI overhauls beyond gateway forms changing from JSON to discrete fields.
- Provide rollback migration stubs.

## Confirmation
- If this plan aligns with your requirements, confirm and I’ll proceed to implement the schema changes, service updates, controllers, views, routes, and tests accordingly, ensuring sandbox verification for Stripe & PayPal.