<?php

namespace App\Services;

use Stripe\StripeClient;
use Exception;

/**
 * StripePaymentService
 * 
 * Handles Stripe payment intent creation and confirmation for invoices.
 * Requires STRIPE_SECRET in environment.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Services
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class StripePaymentService
{
    /**
     * Verify a Stripe PaymentMethod exists and is usable.
     *
     * @param string $paymentMethodId
     * @return bool
     */
    public function verifyPaymentMethod(string $paymentMethodId): bool
    {
        $secret = config('services.stripe.secret');
        if (empty($secret))
        {
            return false;
        }

        try
        {
            $stripe = new StripeClient($secret);
            $pm = $stripe->paymentMethods->retrieve($paymentMethodId);
            return !empty($pm) && ($pm->id === $paymentMethodId);
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    /**
     * Pay the given amount using a Stripe Payment Method ID.
     *
     * @param int    $amountCents Amount in cents
     * @param string $paymentMethodId Stripe PaymentMethod ID
     * @param array  $metadata Additional metadata for intent
     * @return array{success:bool,transaction_id:?string,error:?string}
     */
    public function pay(int $amountCents, string $paymentMethodId, array $metadata = []): array
    {
        $secret = config('services.stripe.secret');

        if (empty($secret))
        {
            return [
                'success' => false,
                'transaction_id' => null,
                'error' => 'Stripe secret not configured',
            ];
        }

        try
        {
            $stripe = new StripeClient($secret);

            // Create and confirm PaymentIntent
            $intent = $stripe->paymentIntents->create([
                'amount' => $amountCents,
                'currency' => config('billing.currency', 'usd'),
                'payment_method' => $paymentMethodId,
                'confirm' => true,
                'metadata' => $metadata,
                'description' => 'Workout Invoice Payment',
            ]);

            return [
                'success' => in_array($intent->status, ['succeeded', 'requires_capture']) ? true : false,
                'transaction_id' => $intent->id,
                'error' => null,
            ];
        }
        catch (Exception $e)
        {
            return [
                'success' => false,
                'transaction_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}