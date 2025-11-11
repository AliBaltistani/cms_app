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
            $accountLink = $stripe->accountLinks->create([
                'account' => $accountId,
                'refresh_url' => $appUrl . '/trainer/stripe/connect/refresh',
                'return_url' => $appUrl . '/trainer/stripe/connect/return',
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
}