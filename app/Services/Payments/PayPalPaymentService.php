<?php

namespace App\Services\Payments;

use App\Models\Invoice;
use App\Models\PaymentGateway;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;

class PayPalPaymentService
{
    protected PayPalHttpClient $client;

    public function __construct()
    {
        $gateway = PaymentGateway::where('type', 'paypal')->where('enabled', true)->firstOrFail();
        $env = new SandboxEnvironment($gateway->public_key ?? '', $gateway->secret_key ?? '');
        $this->client = new PayPalHttpClient($env);
    }

    public function createOrder(Invoice $invoice, string $returnUrl = '', string $cancelUrl = ''): array
    {
        $request = new OrdersCreateRequest();
        $request->prefer('return=representation');
        $returnUrl = $returnUrl !== '' ? $returnUrl : rtrim((string) config('app.url'), '/') . '/api/payment/paypal/return?invoice=' . $invoice->id;
        $cancelUrl = $cancelUrl !== '' ? $cancelUrl : rtrim((string) config('app.url'), '/') . '/api/payment/paypal/cancel?invoice=' . $invoice->id;
        $request->body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => (string) $invoice->id,
                'amount' => [
                    'value' => number_format($invoice->total_amount, 2, '.', ''),
                    'currency_code' => strtoupper($invoice->currency),
                ],
            ]],
            'application_context' => [
                'brand_name' => config('app.name'),
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];
        $response = $this->client->execute($request);
        $approve = null;
        foreach ($response->result->links ?? [] as $link) {
            if (($link->rel ?? '') === 'approve') {
                $approve = $link->href;
                break;
            }
        }
        return ['id' => $response->result->id ?? null, 'approve_url' => $approve];
    }

    public function captureOrder(string $orderId): array
    {
        $request = new OrdersCaptureRequest($orderId);
        $request->prefer('return=representation');
        $response = $this->client->execute($request);
        return ['status' => $response->statusCode ?? null, 'result' => $response->result ?? null];
    }
}
