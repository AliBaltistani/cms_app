<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiBaseController;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ClientPaymentController extends ApiBaseController
{
    public function gateways()
    {
        $gateways = PaymentGateway::where('enabled', true)->orderByDesc('is_default')->get(['id','name','type','is_default']);
        return response()->json(['success' => true, 'gateways' => $gateways]);
    }

    public function pay($id, Request $request)
    {
        $invoice = Invoice::where('client_id', Auth::id())->find($id);
        if (!$invoice) {
            return $this->sendError('Invoice Not Found', ['error' => 'Invoice not found or inaccessible'], 404);
        }

        if (strtolower((string) $invoice->status) === 'paid') {
            return response()->json(['success' => true, 'data' => $invoice]);
        }

        $gateway = PaymentGateway::where('enabled', true)->where('is_default', true)->first();
        if (!$gateway) {
            return $this->sendError('Gateway Not Configured', ['error' => 'No enabled default payment gateway'], 404);
        }

        $txn = Transaction::create([
            'invoice_id' => $invoice->id,
            'client_id' => Auth::id(),
            'trainer_id' => $invoice->trainer_id,
            'gateway_id' => $gateway->id,
            'amount' => $invoice->total_amount,
            'currency' => $invoice->currency,
            'status' => 'pending',
        ]);

        if ($gateway->type === 'stripe') {
            try {
                $accountId = optional(\App\Models\TrainerBankAccount::where('trainer_id', $invoice->trainer_id)->latest()->first())->account_id;
                if (!$accountId || !str_starts_with((string) $accountId, 'acct_')) {
                    $accountId = null;
                }
                $destinationId = $accountId;
                if ($accountId) {
                    try {
                        $stripe = new \Stripe\StripeClient($gateway->secret_key);
                        $acct = $stripe->accounts->retrieve((string) $accountId);
                        $caps = (array) ($acct->capabilities ?? []);
                        $canTransfer = ((string) ($caps['transfers'] ?? '')) === 'active' || ((string) ($caps['legacy_payments'] ?? '')) === 'active';
                        if (!$canTransfer) {
                            $destinationId = null;
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Stripe capability check failed', ['trainer_id' => $invoice->trainer_id, 'account_id' => $accountId, 'error' => $e->getMessage()]);
                        $destinationId = null;
                    }
                }
                $successUrl = $request->string('success_url') ?: rtrim((string) config('app.url'), '/') . '/api/payment/stripe/return?invoice=' . $invoice->id;
                $cancelUrl = $request->string('cancel_url') ?: rtrim((string) config('app.url'), '/') . '/api/payment/stripe/cancel?invoice=' . $invoice->id;
                $service = new \App\Services\Payments\StripePaymentService();
                $session = $service->createCheckoutSession($invoice, $destinationId, 10, (string) $successUrl, (string) $cancelUrl);
                $txn->transaction_id = $session['id'] ?? null;
                $txn->save();
                return response()->json(['success' => true, 'data' => $txn, 'stripe' => ['checkout_url' => $session['url']]]);
            } catch (\Throwable $e) {
                Log::error('Stripe checkout session creation failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
                return $this->sendError('Stripe Error',  ['error' => 'Failed to initialize Stripe checkout: ' . $e->getMessage()], 500);
            }
        }

        if ($gateway->type === 'paypal') {
            try {
                $service = new \App\Services\Payments\PayPalPaymentService();
                $returnUrl = $request->string('return_url') ?: rtrim((string) config('app.url'), '/') . '/api/payment/paypal/return?invoice=' . $invoice->id;
                $cancelUrl = $request->string('cancel_url') ?: rtrim((string) config('app.url'), '/') . '/api/payment/paypal/cancel?invoice=' . $invoice->id;
                $order = $service->createOrder($invoice, (string) $returnUrl, (string) $cancelUrl);
                $txn->transaction_id = $order['id'] ?? null;
                $txn->save();
                return response()->json(['success' => true, 'data' => $txn, 'paypal' => ['approve_url' => $order['approve_url']]]);
            } catch (\Throwable $e) {
                Log::error('PayPal order creation failed', [['invoice_id' => $invoice->id, 'error' => $e->getMessage()]]);
                return $this->sendError('PayPal Error', ['success' => false, 'data' => ['error' => 'Failed to initialize PayPal order']], 500);
            }
        }

        return $this->sendError('Unsupported Gateway', ['error' => 'Unsupported payment gateway'], 422);
    }

    public function retry(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string'
        ], [
            'transaction_id.required' => 'Transaction ID is required.',
            'transaction_id.string' => 'Transaction ID must be a string.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $txn = Transaction::where('client_id', Auth::id())->find($request->string('transaction_id'));
        if (!$txn) {
            return $this->sendError('Transaction Not Found', ['transaction_id' => 'Transaction not found'], 404);
        }
        return response()->json(['success' => true, 'transaction' => $txn]);
    }

    public function cancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|integer'
        ], [
            'invoice_id.required' => 'Invoice ID is required.',
            'invoice_id.integer' => 'Invoice ID must be an integer.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $invoice = Invoice::where('client_id', Auth::id())->find($request->integer('invoice_id'));
        if (!$invoice) {
            return $this->sendError('Invoice Not Found', ['invoice_id' => 'Invoice not found'], 404);
        }
        if ($invoice->status === 'pending') {
            $invoice->status = 'cancelled';
            $invoice->save();
        }
        return response()->json(['success' => true, 'invoice' => $invoice]);
    }

    public function show($transaction_id)
    {
        $txn = Transaction::where('client_id', Auth::id())->where('transaction_id', $transaction_id)->firstOrFail();
        return response()->json(['success' => true, 'transaction' => $txn]);
    }

    public function paypalCapture(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string'
        ], [
            'order_id.required' => 'Order ID is required.',
            'order_id.string' => 'Order ID must be a string.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        try {
            $service = new \App\Services\Payments\PayPalPaymentService();
            $capture = $service->captureOrder($request->string('order_id'));
        } catch (\Throwable $e) {
            Log::error('PayPal capture failed', ['order_id' => $request->string('order_id'), 'error' => $e->getMessage()]);
            return $this->sendError('PayPal Error', ['error' => 'Failed to capture PayPal order'], 500);
        }

        $txn = Transaction::where('client_id', Auth::id())->where('transaction_id', $request->string('order_id'))->first();
        if ($txn) {
            $ok = ($capture['result']->status ?? '') === 'COMPLETED';
            $txn->status = $ok ? 'paid' : 'failed';
            $txn->response = $capture['result'] ?? [];
            $txn->save();
            $invoice = Invoice::find($txn->invoice_id);
            if ($invoice) {
                $invoice->status = $ok ? 'paid' : 'failed';
                $invoice->save();
            }
        }
        return response()->json(['success' => true, 'capture' => $capture, 'transaction' => $txn]);
    }

    public function stripeReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'required|string'
        ], [
            'session_id.required' => 'Session ID is required.',
            'session_id.string' => 'Session ID must be a string.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $sessionId = (string) $request->string('session_id');
        try {
            $service = new \App\Services\Payments\StripePaymentService();
            $session = $service->retrieveCheckoutSession($sessionId);
        } catch (\Throwable $e) {
            Log::error('Stripe session retrieval failed', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return $this->sendError('Stripe Error', ['error' => 'Failed to retrieve Stripe session'], 500);
        }
        $txn = Transaction::where('transaction_id', $sessionId)->first();
        if ($txn) {
            $paid = ($session['payment_status'] ?? '') === 'paid';
            $txn->status = $paid ? 'paid' : 'failed';
            $txn->response = $session;
            $txn->save();
            $invoice = Invoice::find($txn->invoice_id);
            if ($invoice) {
                $invoice->status = $paid ? 'paid' : 'failed';
                $invoice->save();
            }
        } else {
            $pi = $session['payment_intent'] ?? null;
            $metaInvoiceId = is_object($pi) && isset($pi->metadata['invoice_id']) ? (int) $pi->metadata['invoice_id'] : null;
            if ($metaInvoiceId) {
                $invoice = Invoice::find($metaInvoiceId);
                if ($invoice) {
                    $paid = ($session['payment_status'] ?? '') === 'paid';
                    $invoice->status = $paid ? 'paid' : 'failed';
                    $invoice->save();
                }
            }
        }
        $redirectTo = (string) $request->string('redirect_to');
        if ($redirectTo !== '') {
            $host = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host === null || $host === '' || $host === $appHost) {
                return redirect()->to($redirectTo);
            }
        }
        return response()->json(['success' => true, 'session' => $session, 'transaction' => $txn]);
    }

    public function stripeCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'session_id' => 'nullable|string|required_without:invoice',
            'invoice' => 'nullable|integer|required_without:session_id'
        ], [
            'session_id.string' => 'Session ID must be a string.',
            'session_id.required_without' => 'Session ID is required when invoice is not provided.',
            'invoice.integer' => 'Invoice ID must be an integer.',
            'invoice.required_without' => 'Invoice ID is required when session ID is not provided.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $sessionId = (string) $request->string('session_id');
        $invoiceId = $request->integer('invoice');
        $txn = null;
        if ($sessionId) {
            $txn = Transaction::where('transaction_id', $sessionId)->first();
        } elseif ($invoiceId) {
            $txn = Transaction::where('invoice_id', $invoiceId)->latest()->first();
        }
        if ($txn) {
            $txn->status = 'cancelled';
            $txn->save();
        }
        $invoice = $invoiceId ? Invoice::find($invoiceId) : ($txn ? Invoice::find($txn->invoice_id) : null);
        if ($invoice && $invoice->status === 'pending') {
            $invoice->status = 'cancelled';
            $invoice->save();
        }
        $redirectTo = (string) $request->string('redirect_to');
        if ($redirectTo !== '') {
            $host = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host === null || $host === '' || $host === $appHost) {
                return redirect()->to($redirectTo);
            }
        }
        return response()->json(['success' => true, 'transaction' => $txn, 'invoice' => $invoice]);
    }

    public function paypalReturn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string'
        ], [
            'token.required' => 'Token is required.',
            'token.string' => 'Token must be a string.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $token = (string) $request->string('token');
        try {
            $service = new \App\Services\Payments\PayPalPaymentService();
            $capture = $service->captureOrder($token);
        } catch (\Throwable $e) {
            Log::error('PayPal return capture failed', ['token' => $token, 'error' => $e->getMessage()]);
            return $this->sendError('PayPal Error', ['error' => 'Failed to capture PayPal order'], 500);
        }
        $txn = Transaction::where('transaction_id', $token)->first();
        if ($txn) {
            $ok = ($capture['result']->status ?? '') === 'COMPLETED';
            $txn->status = $ok ? 'paid' : 'failed';
            $txn->response = $capture['result'] ?? [];
            $txn->save();
            $invoice = Invoice::find($txn->invoice_id);
            if ($invoice) {
                $invoice->status = $ok ? 'paid' : 'failed';
                $invoice->save();
            }
        }
        $redirectTo = (string) $request->string('redirect_to');
        if ($redirectTo !== '') {
            $host = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host === null || $host === '' || $host === $appHost) {
                return redirect()->to($redirectTo);
            }
        }
        return response()->json(['success' => true, 'capture' => $capture, 'transaction' => $txn]);
    }

    public function paypalCancel(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'invoice' => 'nullable|integer'
        ], [
            'token.required' => 'Token is required.',
            'token.string' => 'Token must be a string.',
            'invoice.integer' => 'Invoice ID must be an integer.'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $token = (string) $request->string('token');
        $txn = Transaction::where('transaction_id', $token)->first();
        if ($txn) {
            $txn->status = 'cancelled';
            $txn->save();
        }
        $invoiceId = $request->integer('invoice');
        $invoice = $invoiceId ? Invoice::find($invoiceId) : ($txn ? Invoice::find($txn->invoice_id) : null);
        if ($invoice && $invoice->status === 'pending') {
            $invoice->status = 'cancelled';
            $invoice->save();
        }
        $redirectTo = (string) $request->string('redirect_to');
        if ($redirectTo !== '') {
            $host = parse_url($redirectTo, PHP_URL_HOST);
            $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
            if ($host === null || $host === '' || $host === $appHost) {
                return redirect()->to($redirectTo);
            }
        }
        return response()->json(['success' => true, 'transaction' => $txn, 'invoice' => $invoice]);
    }
}
