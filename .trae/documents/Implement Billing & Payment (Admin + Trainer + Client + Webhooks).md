## Architecture Overview
- Introduce a cohesive Billing module aligned with current Laravel + Blade structure
- Components: Eloquent models, migrations, controllers (Admin + API), services (Stripe/PayPal), jobs for webhook processing, policies/middleware, Blade admin pages
- Follow UI patterns from `resources/views/admin/bookings/index.blade.php`, `.../bookings/show.blade.php`, and `.../nutrition-plans/create.blade.php` for layout, tables, filters, modals, and actions

## Data Model & Migrations
- `payment_gateways`: id, name, type (`stripe`/`paypal`), public_key, secret_key, webhook_secret, connect_client_id (Stripe OAuth), is_default, enabled, timestamps; encrypt secrets at-rest
- `trainer_bank_accounts`: id, trainer_id, gateway (`stripe`), account_id (Stripe `acct_...`), display_name, country, verification_status, last_status_sync_at, raw_meta (json), timestamps
- `invoices`: id, trainer_id, client_id, total_amount, currency, due_date, notes, status (`draft`/`pending`/`paid`/`failed`/`cancelled`), created_by (`trainer`/`admin`), timestamps
- `invoice_items`: id, invoice_id, workout_id (nullable), title, amount, qty, timestamps
- `transactions`: id, invoice_id, client_id, trainer_id, gateway_id, amount, currency, transaction_id, status (`pending`/`paid`/`failed`/`refunded`), response (json), timestamps
- `payouts`: id, trainer_id, amount, currency, fee_amount, payout_status (`processing`/`completed`/`failed`), gateway_payout_id, scheduled_at, timestamps
- `webhook_logs`: id, gateway_id, event_type, payload (json), processed_at, status, notes, timestamps
- Indexes: FKs on `trainer_id`, `client_id`, `gateway_id`, `invoice_id`; composite indexes for common queries (e.g., `invoices(trainer_id,status)`, `transactions(invoice_id,status)`)
- Casts: encrypted casts for key fields in `payment_gateways`; json casts for `raw_meta`, `response`, `payload`

## Eloquent Models & Relationships
- `PaymentGateway`: hasMany `transactions`; scopes for enabled/default
- `TrainerBankAccount`: belongsTo `User` (trainer); belongsTo `PaymentGateway`
- `Invoice`: belongsTo trainer/client; hasMany `InvoiceItem`; hasOne/Many `Transaction`
- `InvoiceItem`: belongsTo `Invoice`; optional belongsTo `Workout`
- `Transaction`: belongsTo `Invoice`, `PaymentGateway`; belongsTo trainer/client
- `Payout`: belongsTo trainer; optional belongsTo `PaymentGateway`
- `WebhookLog`: belongsTo `PaymentGateway`

## Services (Strategy Pattern)
- `StripeService`: create PaymentIntents (destination charges with `transfer_data[destination]`), apply `application_fee_amount`; verify webhook signatures; fetch connected account status; perform transfers/payout lookups
- `PayPalService`: create Orders/Captures (sandbox), return `approve_url`; verify webhooks via signature validation; capture payments and record transactions
- Common `PaymentService` interface for: createPayment, retryPayment, cancelPayment, getPaymentDetails, verifyWebhook, syncPayout

## Admin Web (Routes, Controllers, UI)
- Routes (`routes/web.php` under `admin` group):
  - `GET /admin/payment-gateways` list; `POST /admin/payment-gateways` store; `PUT /admin/payment-gateways/{id}` update; `POST /admin/payment-gateways/{id}/enable`; `POST /admin/payment-gateways/{id}/set-default`
  - `GET /admin/trainers/{id}/bank-accounts` list trainer accounts
  - `GET /admin/invoices` list/search; `GET /admin/payouts` list/export; dashboard metrics endpoints
- Controllers: `Admin\PaymentGatewayController`, `Admin\BillingController` (invoices, payouts, dashboard), `Admin\TrainerBankController` (view trainer connect status)
- Blade pages (mirroring bookings style): tables with filters, bulk actions, modals for enable/disable, forms with encrypted key handling; badges for statuses; DataTable where useful
- Sidebar: add "Billing" section with links to Payment Gateways, Invoices, Payouts, and Billing Dashboard (reuse icon styles)

## Trainer API (Sanctum + `trainer` middleware)
- Routes (`routes/api.php` in `/trainer`):
  - `POST /trainer/bank/connect` → returns Stripe Connect OAuth URL (using `connect_client_id`)
  - `GET /trainer/bank/callback` → exchanges `code` for `account_id`; saves `trainer_bank_accounts`
  - `GET /trainer/bank` → list connected accounts + statuses
  - `POST /trainer/bank/disconnect` → marks disconnected; optionally calls Stripe to deauthorize
  - `POST /trainer/invoice/create` → build invoice from matched workouts; accepts custom line items; status `pending`
  - `GET /trainer/invoices` → list
  - `GET /trainer/payouts` → list
- Controllers: `Api\TrainerBankController`, `Api\TrainerInvoiceController`, `Api\TrainerPayoutController`
- Behavior: validation, role checks, consistent JSON responses, audit logging

## Client API (Sanctum + `client` middleware)
- Routes (`routes/api.php` in `/client`):
  - `GET /client/payment-gateways` → enabled gateways
  - `GET /client/invoices` → list own invoices
  - `POST /client/invoice/{id}/pay` → Stripe: create PaymentIntent, return `client_secret`; PayPal: create order, return approve link
  - `POST /client/payment/retry` → retry failed payment (new PaymentIntent/Capture)
  - `POST /client/payment/cancel` → mark invoice `cancelled` if pending
  - `GET /client/payment/{transaction_id}` → transaction details
- Controllers: `Api\ClientPaymentController`, `Api\ClientInvoiceController`

## Webhooks (Public)
- Routes (`routes/api.php` or `routes/web.php` public): `POST /webhook/stripe`, `POST /webhook/paypal`
- Controllers: `Api\WebhookController` with actions `stripe()` and `paypal()`
- Flow: verify signature, persist `webhook_logs`, dispatch jobs (`ProcessStripeWebhookJob`, `ProcessPayPalWebhookJob`) to update `transactions`, `invoices`, `payouts`, `trainer_bank_accounts`
- Stripe events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `transfer.paid`, `charge.refunded`, `account.updated`
- PayPal events: `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.CAPTURE.DENIED`

## Payment Flows (Implementation Details)
- Gateway setup: Admin creates gateways; secrets encrypted; toggle enable/default
- Trainer Connect (Stripe): generate OAuth URL → callback saves `account_id`, status/meta; show status in Admin
- Invoice creation: auto-load workouts by `trainer_id` + `client_id` into line items; allow adjustments; compute totals; status `pending`
- Client pay (Stripe): create PaymentIntent (`amount`, `currency`, `transfer_data[destination]` trainer `account_id`, `application_fee_amount` for commission); return `client_secret`; webhook sets invoice `paid` and creates/updates transaction; payout via destination charge or scheduled transfer
- Retry/Cancel: recreate intents; set invoice `cancelled` when applicable
- Payouts: list from Stripe transfers/payouts; retry failed payouts; update statuses from webhook

## Security & Operations
- Secrets: encrypt in DB using Laravel encrypted casts or `Crypt`; never log raw secrets
- Role-based middleware: `auth:sanctum` + `admin`/`trainer`/`client` already present; add policies where needed
- Webhooks: HTTPS, signature validation (Stripe `Stripe-Signature`; PayPal verification headers), idempotency via event id
- Queue workers: database queue already configured; use jobs for webhook processing/payout routines
- Audit: log all interactions to `webhook_logs` and `transactions.response`

## Testing & Acceptance
- Unit tests: models (totals, relationships), services (intent creation, signature verify with fakes)
- Integration tests: webhook handling updates invoice/transaction status; trainer connect callback persists account
- Scenarios to cover (from spec): connect, invoice creation, payment success/failure + retry, payouts view, disconnect
- Use Stripe test keys and PayPal sandbox; seed a default Stripe gateway for dev

## Routes & Files Impact (Concrete Additions)
- `routes/web.php` (admin): add groups for `/payment-gateways`, `/invoices`, `/payouts`, `/billing-dashboard`
- `routes/api.php` (trainer/client): add bank/invoice/payment/payout endpoints under existing `trainer` and `client` middleware groups
- Controllers: create under `app/Http/Controllers/Admin` and `app/Http/Controllers/Api` using existing naming conventions
- Blade: add admin pages under `resources/views/admin/billing/*` with layout and components matching bookings/nutrition patterns (cards, tables, modals)

## Implementation Phases
- Phase 1: Migrations + Models + casts/relationships
- Phase 2: Stripe/PayPal services + configuration loaders from `payment_gateways`
- Phase 3: Admin web UI (Gateways, Invoices, Payouts, Dashboard) mirroring bookings style
- Phase 4: Trainer API (connect, invoices, payouts)
- Phase 5: Client API (gateways, pay/retry/cancel, payment details)
- Phase 6: Webhooks + jobs + idempotency + logging
- Phase 7: Payout scheduling and admin retry tools
- Phase 8: Tests (unit/integration) + dev sandbox validation

## Open Decisions (Default Recommendation)
- Use Stripe destination charges with `transfer_data[destination]` and `application_fee_amount` for platform commission; this simplifies trainer fund flow and fee accounting while keeping compliance straightforward
- Commission percentage configurable (e.g., `platform_commission_percent`) via config/env

## Deliverable Checklist
- DB tables created and migrated; models with relationships/casts
- Admin pages for gateways/invoices/payouts/dashboard with filters and actions
- Trainer API for Stripe Connect + invoices + payouts
- Client API for gateways + payments + retries/cancel + details
- Webhook endpoints with queued processors and comprehensive logging
- Unit/integration tests passing in dev; sandbox flows verified