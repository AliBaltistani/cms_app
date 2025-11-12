<?php

namespace App\Services;

use App\Models\PaymentGateway;

class CentralGatewayService
{
    public function getEnabledGateways(): array
    {
        return PaymentGateway::enabled()->get()->map(function ($g) {
            return [
                'name' => $g->gateway_name,
                'type' => $g->gateway_type,
                'public_key' => $g->public_key,
                'is_default' => (bool) $g->is_default,
                'status' => (bool) $g->status,
            ];
        })->values()->all();
    }

    public function getStripeCredentials(): array
    {
        $gateway = PaymentGateway::enabled()->where('gateway_type', 'stripe')->first();
        if ($gateway) {
            return [
                'secret' => $gateway->secret_key ?? config('services.stripe.secret'),
                'key' => $gateway->public_key ?? config('services.stripe.key'),
                'webhook_secret' => $gateway->webhook_secret ?? env('STRIPE_WEBHOOK_SECRET'),
            ];
        }
        return [
            'secret' => config('services.stripe.secret'),
            'key' => config('services.stripe.key'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ];
    }

    public function getPayPalCredentials(): array
    {
        $gateway = PaymentGateway::enabled()->where('gateway_type', 'paypal')->first();
        if ($gateway) {
            return [
                'client_id' => $gateway->public_key ?? config('services.paypal.client_id'),
                'client_secret' => $gateway->secret_key ?? config('services.paypal.client_secret'),
                'sandbox' => config('services.paypal.sandbox'),
            ];
        }
        return [
            'client_id' => config('services.paypal.client_id'),
            'client_secret' => config('services.paypal.client_secret'),
            'sandbox' => config('services.paypal.sandbox'),
        ];
    }

    public function getDefaultCommissionRate(): float
    {
        $default = PaymentGateway::enabled()->where('is_default', true)->first();
        if ($default && $default->commission_rate !== null) {
            return (float) $default->commission_rate / 100.0;
        }
        return (float) config('billing.commission_rate', 0.10);
    }
}
