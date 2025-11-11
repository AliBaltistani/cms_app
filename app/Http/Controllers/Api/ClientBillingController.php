<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Invoice;
use App\Services\PaymentGatewayService;

/**
 * ClientBillingController
 *
 * API endpoints for clients to manage payments.
 * - Add payment method (Stripe/PayPal)
 * - Pay invoice via chosen gateway
 * - Retry failed payments
 *
 * @package     GoGlobe Trainer
 * @subpackage  Controllers
 * @category    API
 * @author      Dev Team
 * @since       1.0.0
 */
class ClientBillingController extends Controller
{
    /**
     * Add a payment method for the authenticated client.
     * This endpoint validates the payment method with providers
     * and returns a token/id that can be used for payments.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function addPaymentMethod(Request $request)
    {
        // Pre-initialize variables to avoid undefined errors in catch blocks
        $userId = Auth::id();
        $method = $request->input('method');
        $token = $request->input('token');

        // NOTE: We deliberately do not persist payment methods server-side
        // to avoid adding new tables/features beyond the scope. Clients
        // should store the returned token and pass it when paying invoices.
        //
        // For minimal verification, we perform a lightweight provider check
        // without storing customer profiles.
        try
        {
            $request->validate([
                'method' => 'required|in:stripe,paypal',
                // For Stripe: expects payment_method id created client-side via Stripe SDK
                // For PayPal: expects payer_id or billing agreement id depending on flow
                'token' => 'required|string',
            ]);

            if ($method === 'stripe')
            {
                // Optionally verify payment method exists via Stripe API
                $service = app(\App\Services\StripePaymentService::class);
                $verified = $service->verifyPaymentMethod($token);
                if (!$verified)
                {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid Stripe payment method.',
                    ], 422);
                }
            }

            if ($method === 'paypal')
            {
                // For PayPal we trust client-side approval and token presence
                // A real integration may verify payer info here.
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'method' => $method,
                    'token' => $token,
                    'user_id' => $userId,
                ],
                'message' => 'Payment method added successfully.',
            ], 201);
        }
        catch (ValidationException $e)
        {
            // Provide user-friendly validation feedback with detailed errors
            Log::warning('Add payment method validation failed', [
                'user_id' => $userId,
                'method' => $method,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid payment method request.',
                'errors' => $e->errors(),
            ], 422);
        }
        catch (\Throwable $e)
        {
            Log::error('Add payment method failed', [
                'user_id' => $userId,
                'method' => $method,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add payment method.',
            ], 500);
        }
    }

    /**
     * Pay an invoice using Stripe or PayPal.
     *
     * @param  Request $request
     * @param  PaymentGatewayService $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function payInvoice(Request $request, PaymentGatewayService $gateway)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'method' => 'required|in:stripe,paypal',
            'token' => 'required|string',
        ]);

        $clientId = Auth::id();
        $invoice = Invoice::find($request->input('invoice_id'));

        if (!$invoice || (int) $invoice->client_id !== (int) $clientId)
        {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this client.',
            ], 404);
        }

        if ($invoice->status === 'paid')
        {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already paid.',
            ], 409);
        }

        try
        {
            $method = $request->input('method');
            $token = $request->input('token');
            $payload = $method === 'stripe' ? ['payment_method_id' => $token] : ['order_id' => $token];

            $result = $gateway->payInvoice(
                $invoice,
                $method,
                $payload
            );

            if (($result['success'] ?? false))
            {
                // Refresh invoice to ensure latest data from service
                $invoice->refresh();
                $invoice->load(['trainer', 'items']);

                $confirmation = [
                    'invoice_id' => $invoice->id,
                    'amount_paid' => (float) $invoice->total_amount,
                    'currency' => config('billing.currency', 'usd'),
                    'paid_at' => optional($invoice->updated_at)->toDateTimeString(),
                    'trainer_name' => $invoice->trainer?->name,
                    'services' => $invoice->items->map(function ($item) { return $item->title; })->values(),
                    'transaction_id' => $invoice->transaction_id,
                    'payment_method' => $invoice->payment_method,
                ];

                return response()->json([
                    'success' => true,
                    'data' => $confirmation,
                    'message' => 'Payment successful.',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Payment failed.',
            ], 422);
        }
        catch (\Throwable $e)
        {
            Log::error('Invoice payment failed', [
                'invoice_id' => $invoice->id,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed.',
            ], 500);
        }
    }

    /**
     * List invoices for the authenticated client (payment history).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listInvoices()
    {
        $clientId = Auth::id();

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->with('items')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Retry payment for a failed invoice.
     * Accepts the same payload as payInvoice.
     *
     * @param  Request                 $request
     * @param  PaymentGatewayService   $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function retryInvoice(Request $request, PaymentGatewayService $gateway)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'method' => 'required|in:stripe,paypal',
            'token' => 'required|string',
        ]);

        $clientId = Auth::id();
        $invoice = Invoice::find($request->input('invoice_id'));

        if (!$invoice || (int) $invoice->client_id !== (int) $clientId)
        {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this client.',
            ], 404);
        }

        if ($invoice->status === 'paid')
        {
            return response()->json([
                'success' => false,
                'message' => 'Invoice already paid.',
            ], 409);
        }

        if ($invoice->status !== 'failed')
        {
            return response()->json([
                'success' => false,
                'message' => 'Only failed invoices can be retried.',
            ], 422);
        }

        try
        {
            $method = $request->input('method');
            $token = $request->input('token');
            $payload = $method === 'stripe' ? ['payment_method_id' => $token] : ['order_id' => $token];

            $result = $gateway->payInvoice(
                $invoice,
                $method,
                $payload
            );

            if (($result['success'] ?? false))
            {
                // Refresh and return confirmation payload similar to payInvoice
                $invoice->refresh();
                $invoice->load(['trainer', 'items']);

                $confirmation = [
                    'invoice_id' => $invoice->id,
                    'amount_paid' => (float) $invoice->total_amount,
                    'currency' => config('billing.currency', 'usd'),
                    'paid_at' => optional($invoice->updated_at)->toDateTimeString(),
                    'trainer_name' => $invoice->trainer?->name,
                    'services' => $invoice->items->map(function ($item) { return $item->title; })->values(),
                    'transaction_id' => $invoice->transaction_id,
                    'payment_method' => $invoice->payment_method,
                ];

                return response()->json([
                    'success' => true,
                    'data' => $confirmation,
                    'message' => 'Payment successful.',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Payment failed.',
            ], 422);
        }
        catch (\Throwable $e)
        {
            Log::error('Invoice retry payment failed', [
                'invoice_id' => $invoice->id,
                'client_id' => $clientId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed.',
            ], 500);
        }
    }

    /**
     * List available payment methods for the authenticated client.
     * Returns Stripe and PayPal availability based on configuration.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPaymentMethods()
    {
        $methods = [
            [
                'name' => 'stripe',
                'enabled' => !empty(config('services.stripe.secret')) && !empty(config('services.stripe.key')),
            ],
            [
                'name' => 'paypal',
                'enabled' => !empty(config('services.paypal.client_id')) && !empty(config('services.paypal.client_secret')),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'currency' => config('billing.currency', 'usd'),
                'methods' => $methods,
            ],
        ]);
    }

    /**
     * List paid payments (confirmations) for the authenticated client.
     * Includes amount, date, trainer name, services, and transaction ID.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function listPayments()
    {
        $clientId = Auth::id();

        $invoices = Invoice::query()
            ->where('client_id', $clientId)
            ->where('status', 'paid')
            ->with(['trainer', 'items'])
            ->orderByDesc('id')
            ->paginate(20);

        // Map to confirmation format
        $payload = $invoices->getCollection()->map(function ($invoice) {
            return [
                'invoice_id' => $invoice->id,
                'amount_paid' => (float) $invoice->total_amount,
                'currency' => config('billing.currency', 'usd'),
                'paid_at' => optional($invoice->updated_at)->toDateTimeString(),
                'trainer_name' => $invoice->trainer?->name,
                'services' => $invoice->items->map(function ($item) { return $item->title; })->values(),
                'transaction_id' => $invoice->transaction_id,
                'payment_method' => $invoice->payment_method,
            ];
        });

        // Replace paginator collection with transformed payload
        $invoices->setCollection($payload);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    /**
     * Cancel a failed payment, reverting invoice to unpaid.
     * Clears transaction and payment method so client can pay again.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelInvoice(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $clientId = Auth::id();
        $invoice = Invoice::find($request->input('invoice_id'));

        if (!$invoice || (int) $invoice->client_id !== (int) $clientId)
        {
            return response()->json([
                'success' => false,
                'message' => 'Invoice not found for this client.',
            ], 404);
        }

        if ($invoice->status !== 'failed')
        {
            return response()->json([
                'success' => false,
                'message' => 'Only failed invoices can be cancelled.',
            ], 422);
        }

        $invoice->status = 'unpaid';
        $invoice->payment_method = null;
        $invoice->transaction_id = null;
        $invoice->save();

        return response()->json([
            'success' => true,
            'message' => 'Payment cancelled. Invoice reverted to unpaid.',
        ], 200);
    }
}