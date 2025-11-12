<?php

namespace App\Services;

use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use Exception;

/**
 * PayPalPaymentService
 * 
 * Captures PayPal orders for invoice payments.
 * Requires PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in environment.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Services
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class PayPalPaymentService
{
    /**
     * Capture a PayPal order.
     *
     * @param string $orderId PayPal Order ID from client checkout
     * @return array{success:bool,transaction_id:?string,error:?string}
     */
    public function capture(string $orderId, ?string $clientIdOverride = null, ?string $clientSecretOverride = null, ?bool $sandboxOverride = null): array
    {
        $clientId = $clientIdOverride ?: config('services.paypal.client_id');
        $clientSecret = $clientSecretOverride ?: config('services.paypal.client_secret');
        $sandbox = ($sandboxOverride !== null) ? $sandboxOverride : (bool) config('services.paypal.sandbox', true);

        if (empty($clientId) || empty($clientSecret))
        {
            return [
                'success' => false,
                'transaction_id' => null,
                'error' => 'PayPal credentials not configured',
            ];
        }

        try
        {
            $environment = $sandbox
                ? new SandboxEnvironment($clientId, $clientSecret)
                : new ProductionEnvironment($clientId, $clientSecret);

            $client = new PayPalHttpClient($environment);
            $request = new OrdersCaptureRequest($orderId);
            $request->prefer('return=representation');

            $response = $client->execute($request);

            $status = $response->result->status ?? 'FAILED';
            $id = $response->result->id ?? null;

            return [
                'success' => ($status === 'COMPLETED'),
                'transaction_id' => $id,
                'error' => $status === 'COMPLETED' ? null : 'PayPal capture failed',
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
