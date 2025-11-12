<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Invoice;
use App\Models\Payout;
use App\Services\CentralGatewayService;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        try {
            $payload = $request->all();
            $type = $payload['type'] ?? null;
            if ($type === 'payment_intent.succeeded') {
                $intent = $payload['data']['object'] ?? [];
                $invoiceId = (int) ($intent['metadata']['invoice_id'] ?? 0);
                $invoice = Invoice::find($invoiceId);
                if ($invoice && $invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->transaction_id = $intent['id'] ?? null;
                    $invoice->payment_method = 'stripe';
                    $commissionRate = (float) config('billing.commission_rate', 0.10);
                    $invoice->commission_amount = round($invoice->total_amount * $commissionRate, 2);
                    $invoice->net_amount = round($invoice->total_amount - $invoice->commission_amount, 2);
                    $invoice->save();
                    Payout::firstOrCreate([
                        'trainer_id' => $invoice->trainer_id,
                        'amount' => $invoice->net_amount,
                        'payout_status' => 'pending',
                    ]);
                }
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 400);
        }
    }

    public function paypal(Request $request)
    {
        try {
            $payload = $request->all();
            $eventType = $payload['event_type'] ?? null;
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                $resource = $payload['resource'] ?? [];
                $invoiceId = (int) ($resource['custom_id'] ?? 0);
                $invoice = Invoice::find($invoiceId);
                if ($invoice && $invoice->status !== 'paid') {
                    $invoice->status = 'paid';
                    $invoice->transaction_id = $resource['id'] ?? null;
                    $invoice->payment_method = 'paypal';
                    $commissionRate = (float) config('billing.commission_rate', 0.10);
                    $invoice->commission_amount = round($invoice->total_amount * $commissionRate, 2);
                    $invoice->net_amount = round($invoice->total_amount - $invoice->commission_amount, 2);
                    $invoice->save();
                    Payout::firstOrCreate([
                        'trainer_id' => $invoice->trainer_id,
                        'amount' => $invoice->net_amount,
                        'payout_status' => 'pending',
                    ]);
                }
            }
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('PayPal webhook failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 400);
        }
    }
}

