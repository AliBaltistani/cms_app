<?php

namespace App\Services;

use App\Models\TrainerStripeAccount;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * StripeConnectService
 * 
 * Handles Stripe Connect onboarding for trainers:
 * - Creates or retrieves Express accounts
 * - Generates account onboarding links
 * - Persists account ID and basic verification status
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Services
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class StripeConnectService
{
    /**
     * Create or retrieve a Stripe Connect account for the trainer and return onboarding link.
     *
     * @param  int   $trainerId Trainer user ID
     * @return array {
     *   @var bool   $success
     *   @var string $account_id
     *   @var string $onboarding_url
     *   @var string $verification_status
     *   @var string|null $error
     * }
     */
    public function initiateOnboarding(int $trainerId): array
    {
        $secret = config('services.stripe.secret');

        if (empty($secret))
        {
            return [
                'success' => false,
                'account_id' => null,
                'onboarding_url' => null,
                'verification_status' => null,
                'error' => 'Stripe secret not configured',
            ];
        }

        try
        {
            $stripe = new StripeClient($secret);

            // Fetch existing connect account if present
            $existing = TrainerStripeAccount::where('trainer_id', $trainerId)->first();
            $accountId = $existing?->account_id;

            if (!$accountId)
            {
                // Create Express account with transfers capability
                $account = $stripe->accounts->create([
                    'type' => 'express',
                    'country' => 'US',
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                $accountId = $account->id;

                // Persist locally
                TrainerStripeAccount::updateOrCreate(
                    ['trainer_id' => $trainerId],
                    [
                        'account_id' => $accountId,
                        'verification_status' => ($account->details_submitted ?? false) ? 'verified' : 'pending',
                    ]
                );
            }

            // Generate onboarding link
            $appUrl = rtrim(config('app.url'), '/');
            $callbackQuery = '?trainer_id=' . $trainerId;
            $accountLink = $stripe->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => $appUrl . '/api/stripe/connect/refresh' . $callbackQuery,
                'return_url' => $appUrl . '/api/stripe/connect/return' . $callbackQuery,
                'type' => 'account_onboarding',
            ]);

            // Retrieve current account to determine verification status
            $account = $stripe->accounts->retrieve($accountId);
            $verificationStatus = ($account->details_submitted ?? false) ? 'verified' : 'pending';

            // Update stored status if changed
            TrainerStripeAccount::where('trainer_id', $trainerId)
                ->update(['verification_status' => $verificationStatus]);

            return [
                'success' => true,
                'account_id' => $accountId,
                'onboarding_url' => $accountLink->url,
                'verification_status' => $verificationStatus,
                'error' => null,
            ];
        }
        catch (\Throwable $e)
        {
            Log::error('Stripe Connect onboarding failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'account_id' => null,
                'onboarding_url' => null,
                'verification_status' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure a Stripe Connect account exists for the trainer and return its ID.
     *
     * @param  int $trainerId
     * @return array{success: bool, account_id: string|null, error: string|null}
     */
    public function ensureAccount(int $trainerId): array
    {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return ['success' => false, 'account_id' => null, 'error' => 'Stripe secret not configured'];
        }

        try {
            $stripe = new StripeClient($secret);

            // Fetch existing connect account if present
            $existing = TrainerStripeAccount::where('trainer_id', $trainerId)->first();
            $accountId = $existing?->account_id;

            if (!$accountId) {
                $account = $stripe->accounts->create([
                    'type' => 'express',
                    'country' => 'US',
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);
                $accountId = $account->id;

                TrainerStripeAccount::updateOrCreate(
                    ['trainer_id' => $trainerId],
                    [
                        'account_id' => $accountId,
                        'verification_status' => ($account->details_submitted ?? false) ? 'verified' : 'pending',
                    ]
                );
            }

            return ['success' => true, 'account_id' => $accountId, 'error' => null];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Ensure Stripe Connect account failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'account_id' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * Attach an external bank account to the trainer's Stripe Connect account.
     * Returns bank hints for local persistence without storing sensitive numbers.
     *
     * @param  int   $trainerId
     * @param  array $bank {
     *   @var string $account_holder_name
     *   @var string $routing_number
     *   @var string $account_number
     *   @var string|null $account_holder_type  individual|company (optional)
     *   @var string|null $country             2-letter ISO, default US
     *   @var string|null $currency            3-letter currency, default usd
     * }
     * @return array{
     *   success: bool,
     *   account_id: string|null,
     *   external_account_id: string|null,
     *   bank_name: string|null,
     *   last4: string|null,
     *   routing_last4: string|null,
     *   error: string|null,
     *   onboarding_url?: string|null,
     *   requires_onboarding?: bool
     * }
     */
    public function attachExternalBankAccount(int $trainerId, array $bank): array
    {
        $secret = config('services.stripe.secret');
        if (empty($secret)) {
            return [
                'success' => false,
                'account_id' => null,
                'external_account_id' => null,
                'bank_name' => null,
                'last4' => null,
                'routing_last4' => null,
                'error' => 'Stripe secret not configured',
            ];
        }

        $ensure = $this->ensureAccount($trainerId);
        if (!$ensure['success'] || empty($ensure['account_id'])) {
            return [
                'success' => false,
                'account_id' => null,
                'external_account_id' => null,
                'bank_name' => null,
                'last4' => null,
                'routing_last4' => null,
                'error' => $ensure['error'] ?? 'Unable to ensure Connect account',
            ];
        }

        try {
            $stripe = new StripeClient($secret);

            // Guard: only Custom accounts can attach bank accounts server-side.
            // For Express/Standard accounts, return onboarding URL to add bank in Stripe.
            $account = $stripe->accounts->retrieve($ensure['account_id']);
            if (($account->type ?? 'express') !== 'custom') {
                $onboarding = $this->initiateOnboarding($trainerId);
                return [
                    'success' => false,
                    'account_id' => $ensure['account_id'],
                    'external_account_id' => null,
                    'bank_name' => null,
                    'last4' => null,
                    'routing_last4' => null,
                    'error' => 'Bank account must be added via Stripe onboarding.',
                    'onboarding_url' => $onboarding['onboarding_url'] ?? null,
                    'requires_onboarding' => true,
                ];
            }

            $country = $bank['country'] ?? 'US';
            $currency = $bank['currency'] ?? 'usd';
            $accountHolderType = $bank['account_holder_type'] ?? 'individual';

            // Create a bank account token to avoid handling raw numbers directly
            $token = $stripe->tokens->create([
                'bank_account' => [
                    'country' => $country,
                    'currency' => $currency,
                    'account_holder_name' => $bank['account_holder_name'],
                    'account_holder_type' => $accountHolderType,
                    'routing_number' => $bank['routing_number'],
                    'account_number' => $bank['account_number'],
                ],
            ]);

            // Attach the external account to the Connect account
            $external = $stripe->accounts->createExternalAccount($ensure['account_id'], [
                'external_account' => $token->id,
            ]);

            // Determine bank hints
            $bankName = $external->bank_name ?? null;
            $last4 = $external->last4 ?? null;
            // Stripe does not expose routing last4 directly; mask from provided input
            $routingLast4 = substr((string)($bank['routing_number'] ?? ''), -4) ?: null;

            // Update local record hints
            TrainerStripeAccount::where('trainer_id', $trainerId)->update([
                'external_account_id' => $external->id,
                'bank_name' => $bankName,
                'account_holder_name' => $bank['account_holder_name'],
                'bank_account_last4' => $last4,
                'routing_number_last4' => $routingLast4,
                'bank_verification_status' => 'pending',
                'bank_added_at' => now(),
            ]);

            return [
                'success' => true,
                'account_id' => $ensure['account_id'],
                'external_account_id' => $external->id,
                'bank_name' => $bankName,
                'last4' => $last4,
                'routing_last4' => $routingLast4,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Attach external bank account failed', [
                'trainer_id' => $trainerId,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'account_id' => $ensure['account_id'],
                'external_account_id' => null,
                'bank_name' => null,
                'last4' => null,
                'routing_last4' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
