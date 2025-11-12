<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Webhook as StripeWebhook;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        try {
            $gateway = PaymentGateway::where('type', 'stripe')->where('is_default', true)->first();
        } catch (\Throwable $e) {
            $gateway = null;
        }
        $sig = $request->header('Stripe-Signature');
        $secret = $gateway?->webhook_secret;
        try {
            $event = StripeWebhook::constructEvent($request->getContent(), $sig, (string) $secret);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Invalid signature'], 400);
        }
        $payload = [
            'type' => $event->type,
            'data' => ['object' => $event->data->object],
        ];
        $log = null;
        if (Schema::hasTable('webhook_logs')) {
            $log = WebhookLog::create([
                'gateway_id' => $gateway?->id,
                'event_type' => $payload['type'] ?? null,
                'payload' => $payload,
                'status' => 'received',
            ]);
        }
        if (Schema::hasTable('transactions') && Schema::hasTable('invoices')) {
            \App\Jobs\ProcessStripeWebhook::dispatch($payload);
        }
        return response()->json(['success' => true, 'id' => $log?->id]);
    }

    public function paypal(Request $request)
    {
        try {
            $gateway = PaymentGateway::where('type', 'paypal')->where('is_default', true)->first();
        } catch (\Throwable $e) {
            $gateway = null;
        }
        $payload = $request->all();
        $log = null;
        if (Schema::hasTable('webhook_logs')) {
            $log = WebhookLog::create([
                'gateway_id' => $gateway?->id,
                'event_type' => $payload['event_type'] ?? null,
                'payload' => $payload,
                'status' => 'received',
            ]);
        }
        if (Schema::hasTable('transactions') && Schema::hasTable('invoices')) {
            \App\Jobs\ProcessPayPalWebhook::dispatch($payload);
        }
        return response()->json(['success' => true, 'id' => $log?->id]);
    }
}
