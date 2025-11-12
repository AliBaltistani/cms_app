## Current State
- Data: `invoices`, `invoice_items`, `transactions`, `payouts`, `trainer_stripe_accounts` exist; due_date/note already added.
- Trainer API: Creates invoices and handles Stripe Connect onboarding (`App\Http\Controllers\Api\TrainerBillingController`), but no bank details/disconnect endpoints; invoice total uses request `amount` not auto-sum (see app/Http/Controllers/Api/TrainerBillingController.php:191–201, 204–212).
- Client API: Payments via `PaymentGatewayService` with Stripe/PayPal, list/retry/cancel; methods list is driven by `config('services')` not DB.
- Admin Web: Billing dashboards and lists; no gateway management.
- Services: Central payment orchestration (`App\Services\PaymentGatewayService.php`), Stripe (`StripePaymentService`), PayPal (`PayPalPaymentService`), Stripe Connect (`StripeConnectService`). Commission/currency via `config/billing.php`.

## Gaps vs Requirements
- No centralized, admin-managed payment gateways table/CRUD.
- Missing endpoints: `GET /trainer/bank/details`, `DELETE /trainer/bank/disconnect`, `GET /client/payment/details/{invoice_id}`.
- `transactions` lacks `trainer_id` and `amount`.
- Invoice creation doesn’t auto-calc from workouts.
- No webhooks to sync payment/payout statuses.
- Admin cannot list trainer bank account statuses from a single page.

## Database Changes
1. Create `payment_gateways` table
- Columns: `id`, `name`, `type` (`stripe|paypal`), `credentials` (json, encrypted at rest), `status` (`enabled|disabled`), `created_at/updated_at`.
- Indexes: `type`, `status`.
2. Alter `transactions`
- Add `trainer_id` (FK → `users.id`), `amount` decimal(10,2), indexes on `trainer_id`.

## Models & Config
- Add `App\Models\PaymentGateway` with casts: `credentials` array and encrypted storage via accessors/mutators; scopes: `enabled()`.
- Update `App\Models\Transaction` fillable to include `trainer_id`, `amount`.

## Services
1. Central gateway config resolver
- Add `App\Services\CentralGatewayService`: `getEnabledGateways()`, `getStripeCredentials()`, `getPayPalCredentials()` reading `payment_gateways`.
2. Gateway usage
- Update `PaymentGatewayService` to fetch credentials via `CentralGatewayService` and pass overrides to Stripe/PayPal services.
- Update `StripePaymentService::pay()` and `verifyPaymentMethod()` to accept optional secret override.
- Update `PayPalPaymentService::capture()` to accept `client_id`, `client_secret`, `sandbox` override.
- Record `Transaction` with `trainer_id` and `amount` on success/failure; create `Payout` with `pending` status as today.

## Trainer API
- Add `GET /trainer/bank/details`: returns `TrainerStripeAccount` snapshot (account_id, verification_status, bank_verification_status, bank_name, account_last4, routing_last4, details_submitted_at).
- Add `DELETE /trainer/bank/disconnect`: clears local bank hints and, if possible, detaches external account via Stripe; sets `bank_verification_status` to `pending`.
- Update `POST /trainer/invoice/create`: auto-calculate `total_amount` by summing selected workouts’ prices; keep session bookings line items descriptive; set `status=unpaid`, `due_date`, `note` as today; ignore client-provided `amount` (or treat it as optional).
- Keep existing `GET /trainer/invoices`, `GET /trainer/payouts` unchanged.

## Client API
- Update `GET /client/payment-methods`: return enabled gateways from `payment_gateways` with currency from `config/billing.php`.
- Add `GET /client/payment/details/{invoice_id}`: returns confirmation payload (amount, datetime, trainer, services, transaction_id, payment_method) for a single invoice.
- Keep `POST /client/pay`, `POST /client/payment/retry`, `POST /client/payment/cancel` but route through the centralized gateway credentials.

## Admin Web & API
- Add Admin CRUD for `payment_gateways` (index/create/edit/update/toggle): credentials stored securely (encrypted JSON); enable/disable.
- Add Admin list page for trainer bank accounts (from `trainer_stripe_accounts`) with filters on `verification_status` and `bank_verification_status`.
- Hook into existing Billing menu; add links: `Gateways`, `Bank Accounts` under Billing.
- Optional Admin API endpoints mirroring CRUD for mobile/admin:
  - `GET /api/admin/billing/gateways`
  - `POST /api/admin/billing/gateways`
  - `PUT /api/admin/billing/gateways/{id}`
  - `PATCH /api/admin/billing/gateways/{id}/toggle`

## Webhooks
- Add `Api\WebhookController`:
  - `POST /api/webhooks/stripe`: handle `payment_intent.succeeded|payment_intent.payment_failed|payout.paid|payout.failed`; update `invoices`, `transactions`, `payouts` accordingly.
  - `POST /api/webhooks/paypal`: handle `PAYMENT.CAPTURE.COMPLETED|PAYMENT.CAPTURE.DENIED` similarly.
- Verify signatures (Stripe signature header; PayPal `Webhook-Id` and verify via SDK) and log audits.

## Routes
- `routes/api.php`:
  - Trainer: add `GET /trainer/bank/details`, `DELETE /trainer/bank/disconnect`; adjust `invoice/create` to new validation (amount optional).
  - Client: add `GET /client/payment/details/{invoice_id}`; update listPaymentMethods to use DB.
  - Admin: add `prefix('admin/billing/gateways')` for CRUD if API needed.
  - Webhooks: add `POST /webhooks/stripe`, `POST /webhooks/paypal` (public).
- `routes/web.php`:
  - Admin pages for Gateways and Bank Accounts under existing Billing group.

## Payment & Payout Logic
- On successful client payment:
  - Set `invoice.status=paid`, `payment_method`, `transaction_id`;
  - Compute commission via `config('billing.commission_rate')`; set `commission_amount`, `net_amount`;
  - Create `transactions` row with `trainer_id`, `amount=invoice.total_amount`, raw response metadata;
  - Create `payouts` row with `trainer_id`, `amount=invoice.net_amount`, `payout_status=pending`.
- Optional Stripe transfer scheduling: prepare for Connect transfers post-verification using `StripeConnectService` (future job); for sandbox keep payouts pending and update via webhook.

## Audit & Security
- Encrypt `payment_gateways.credentials` using Laravel `Crypt` in model mutators/accessors to avoid storing raw secrets.
- Log all gateway changes with admin user context; add simple audit entries to `storage/logs`.
- Validate and authorize all admin operations; trainers/clients strictly limited to their own data.

## Testing (Dev Server)
- Seed one Stripe and one PayPal gateway as `enabled` with sandbox keys.
- Trainer: create invoice from workouts; verify auto-total and items; connect Stripe; fetch bank details; disconnect.
- Client: list methods; pay invoice via Stripe (test PM `pm_card_visa`); retry fail; cancel pending.
- Admin: view dashboards, gateways CRUD, bank accounts list.
- Webhooks: simulate Stripe and PayPal webhook payloads; assert DB status transitions `paid/failed/pending/completed`.

## Files To Update/Create
- Migrations: `create_payment_gateways_table`, `alter_transactions_add_trainer_id_amount`.
- Models: `PaymentGateway.php` (new), `Transaction.php` (update fillable).
- Services: `CentralGatewayService.php` (new), update `PaymentGatewayService.php`, `StripePaymentService.php`, `PayPalPaymentService.php`.
- Controllers: update `TrainerBillingController.php`, `ClientBillingController.php`; add `Admin\PaymentGatewayController.php`, `Admin\TrainerBankController.php`, `Api\WebhookController.php`.
- Routes: update `routes/api.php`, `routes/web.php`.
- Views: add admin views under `resources/views/admin/billing/gateways/*` and `bank-accounts.blade.php` using existing styling.

If approved, I will implement migrations and code changes, wire endpoints, update routes and admin pages, and verify end-to-end with sandbox keys.