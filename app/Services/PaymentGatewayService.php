<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

/**
 * PaymentGatewayService
 * 
 * Orchestrates payments across Stripe and PayPal.
 * Handles invoice state transitions and payout creation.
 * 
 * @package     GoGlobe Trainer
 * @subpackage  Services
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class PaymentGatewayService
{
    /**
     * Pay an invoice via the selected gateway.
     *
     * @param Invoice $invoice The invoice being paid
     * @param string  $gateway One of 'stripe'|'paypal'
     * @param array   $payload Gateway-specific payload (payment_method_id/order_id)
     * @return array{success:bool,transaction_id:?string,error:?string}
     */
    public function payInvoice(Invoice $invoice, string $gateway, array $payload): array
    {
        $amountCents = (int) round($invoice->total_amount * 100);
        $metadata = [
            'invoice_id' => (string) $invoice->id,
            'trainer_id' => (string) $invoice->trainer_id,
            'client_id' => (string) $invoice->client_id,
        ];

        $result = ['success' => false, 'transaction_id' => null, 'error' => 'Unsupported gateway'];

        $central = new CentralGatewayService();
        if ($gateway === 'stripe')
        {
            $creds = $central->getStripeCredentials();
            $service = new StripePaymentService();
            $result = $service->pay($amountCents, $payload['payment_method_id'] ?? '', $metadata, $creds['secret'] ?? null);
        }
        elseif ($gateway === 'paypal')
        {
            $creds = $central->getPayPalCredentials();
            $service = new PayPalPaymentService();
            $result = $service->capture($payload['order_id'] ?? '', $creds['client_id'] ?? null, $creds['client_secret'] ?? null, $creds['sandbox'] ?? null);
        }

        // Update invoice, record transaction, and create payout on success/failure
        if ($result['success'])
        {
            $invoice->status = 'paid';
            $invoice->payment_method = $gateway;
            $invoice->transaction_id = $result['transaction_id'];

            $commissionRate = app(CentralGatewayService::class)->getDefaultCommissionRate();
            $invoice->commission_amount = round($invoice->total_amount * $commissionRate, 2);
            $invoice->net_amount = round($invoice->total_amount - $invoice->commission_amount, 2);
            $invoice->save();

            // Record successful transaction
            Transaction::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'trainer_id' => $invoice->trainer_id,
                'gateway_id' => null,
                'payment_method' => $gateway,
                'amount' => $invoice->total_amount,
                'status' => 'succeeded',
                'transaction_id' => $result['transaction_id'],
                'response_data' => [
                    'metadata' => $metadata,
                    'payload_keys' => array_keys($payload),
                ],
            ]);

            // Create payout record for trainer
            Payout::create([
                'trainer_id' => $invoice->trainer_id,
                'amount' => $invoice->net_amount,
                'payout_status' => 'pending',
            ]);
        }
        else
        {
            $invoice->status = 'failed';
            $invoice->payment_method = $gateway;
            $invoice->transaction_id = $result['transaction_id'];
            $invoice->save();

            // Record failed transaction
            Transaction::create([
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'trainer_id' => $invoice->trainer_id,
                'payment_method' => $gateway,
                'amount' => $invoice->total_amount,
                'status' => 'failed',
                'transaction_id' => $result['transaction_id'],
                'response_data' => [
                    'error' => $result['error'] ?? null,
                    'metadata' => $metadata,
                    'payload_keys' => array_keys($payload),
                ],
            ]);

            Log::error('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'gateway' => $gateway,
                'error' => $result['error'] ?? 'unknown',
            ]);
        }

        return $result;
    }
}
