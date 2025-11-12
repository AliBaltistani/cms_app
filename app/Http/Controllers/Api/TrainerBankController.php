<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\TrainerBankAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Stripe\StripeClient;

class TrainerBankController extends Controller
{
    public function connect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|string|in:stripe,paypal',
            'country' => 'required|string|size:2',
            'flow' => 'nullable|string|in:express,oauth',
            'business_type' => 'nullable|string|in:individual,company',
            'email' => 'nullable|email'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $gatewayType = (string) $request->string('gateway');
        if ($gatewayType === 'stripe') {
            $pg = PaymentGateway::where('type', 'stripe')->where('enabled', true)->where('is_default', true)->first();
            if (!$pg || !$pg->secret_key) {
                return response()->json(['success' => false, 'message' => 'Stripe is not configured'], 422);
            }

            $flow = (string) $request->string('flow');
            $flow = $flow !== '' ? $flow : 'express';
            $countryInput = strtoupper((string) $request->string('country'));
            $country = $countryInput === 'UK' ? 'GB' : $countryInput;
            $businessType = (string) $request->string('business_type');
            $businessType = $businessType !== '' ? $businessType : 'individual';
            $email = (string) $request->string('email');
            $email = $email !== '' ? $email : (string) Auth::user()->email;

            if ($flow === 'oauth') {
                if (!$pg->connect_client_id) {
                    return response()->json(['success' => false, 'message' => 'Stripe Connect OAuth is not configured'], 422);
                }
                $base = rtrim((string) config('app.url'), '/');
                $params = http_build_query([
                    'response_type' => 'code',
                    'client_id' => $pg->connect_client_id,
                    'scope' => 'read_write',
                    'state' => (string) Auth::id(),
                    'stripe_user[email]' => $email,
                    'stripe_user[country]' => $country,
                ]);
                $redirectUrl = 'https://connect.stripe.com/oauth/authorize?' . $params;
                return response()->json(['success' => true, 'url' => $redirectUrl]);
            }

            try {
                $stripe = new StripeClient($pg->secret_key);
                $acct = $stripe->accounts->create([
                    'type' => 'express',
                    'country' => $country,
                    'email' => $email,
                    'business_type' => $businessType,
                    'capabilities' => [
                        'card_payments' => ['requested' => true],
                        'transfers' => ['requested' => true],
                    ],
                ]);

                $refreshUrl = rtrim((string) config('app.url'), '/') . '/api/trainer/bank/connect';
                $returnUrl = rtrim((string) config('app.url'), '/') . '/api/trainer/bank/callback?account_id=' . $acct->id;
                $link = $stripe->accountLinks->create([
                    'account' => $acct->id,
                    'refresh_url' => $refreshUrl,
                    'return_url' => $returnUrl,
                    'type' => 'account_onboarding',
                ]);

                $account = TrainerBankAccount::create([
                    'trainer_id' => Auth::id(),
                    'gateway' => 'stripe',
                    'account_id' => $acct->id,
                    'display_name' => 'Stripe',
                    'country' => $country,
                    'verification_status' => 'pending',
                    'raw_meta' => ['flow' => 'express'],
                ]);

                return response()->json(['success' => true, 'url' => $link->url, 'account' => $account]);
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        if ($gatewayType === 'paypal') {
            $pg = PaymentGateway::where('type', 'paypal')->where('enabled', true)->first();
            if (!$pg) {
                return response()->json(['success' => false, 'message' => 'PayPal is not configured'], 422);
            }

            $email = (string) $request->string('email');
            $country = strtoupper((string) $request->string('country'));

            $existing = TrainerBankAccount::where('trainer_id', Auth::id())->where('gateway', 'paypal')->where('account_id', $email)->first();
            if ($existing) {
                return response()->json(['success' => true, 'account' => $existing]);
            }

            $account = TrainerBankAccount::create([
                'trainer_id' => Auth::id(),
                'gateway' => 'paypal',
                'account_id' => $email,
                'display_name' => 'PayPal',
                'country' => $country,
                'verification_status' => 'pending',
                'raw_meta' => [],
            ]);

            return response()->json(['success' => true, 'account' => $account]);
        }

        return response()->json(['success' => false, 'message' => 'Unsupported gateway'], 422);
    }

    public function callback(Request $request)
    {
        $gatewayType = (string) $request->input('gateway', 'stripe');
        if ($gatewayType !== 'stripe') {
            return response()->json(['success' => false, 'message' => 'Unsupported callback'], 422);
        }

        $pg = PaymentGateway::where('type', 'stripe')->where('enabled', true)->where('is_default', true)->first();
        if (!$pg || !$pg->secret_key) {
            return response()->json(['success' => false, 'message' => 'Stripe is not configured'], 422);
        }

        $stripe = new StripeClient($pg->secret_key);

        $code = (string) $request->string('code');
        $accountId = (string) $request->string('account_id');
        $stateTrainerId = (int) ($request->input('state') ?? 0);

        try {
            if ($code) {
                if (!$pg->secret_key) {
                    return response()->json(['success' => false, 'message' => 'Missing Stripe secret key'], 422);
                }
                $resp = Http::asForm()->post('https://connect.stripe.com/oauth/token', [
                    'client_secret' => $pg->secret_key,
                    'code' => (string) $code,
                    'grant_type' => 'authorization_code',
                ]);

                if (!$resp->successful()) {
                    return response()->json(['success' => false, 'message' => 'Stripe token exchange failed', 'response' => $resp->json()], 422);
                }
                $data = $resp->json();
                $accountId = (string) ($data['stripe_user_id'] ?? '');
                if ($accountId === '') {
                    return response()->json(['success' => false, 'message' => 'Missing stripe_user_id'], 422);
                }
                if ($stateTrainerId <= 0) {
                    return response()->json(['success' => false, 'message' => 'Missing state for trainer context'], 422);
                }
                $trainer = User::find($stateTrainerId);
                if (!$trainer || $trainer->role !== 'trainer') {
                    return response()->json(['success' => false, 'message' => 'Invalid trainer'], 422);
                }
                $existing = TrainerBankAccount::where('trainer_id', $trainer->id)->where('gateway', 'stripe')->where('account_id', $accountId)->first();
                if (!$existing) {
                    $existing = TrainerBankAccount::create([
                        'trainer_id' => $trainer->id,
                        'gateway' => 'stripe',
                        'account_id' => $accountId,
                        'display_name' => 'Stripe',
                        'verification_status' => 'pending',
                        'raw_meta' => ['flow' => 'oauth'],
                    ]);
                }
            }

            if ($accountId) {
                $acct = $stripe->accounts->retrieve((string) $accountId);
                $status = ($acct->charges_enabled && $acct->payouts_enabled) ? 'verified' : 'pending';
                $record = TrainerBankAccount::where('gateway', 'stripe')->where('account_id', $accountId)->first();
                if ($record) {
                    $displayName = (string) (($acct->business_profile->name ?? '') ?: ($acct->email ?? 'Stripe'));
                    $country = (string) ($acct->country ?? ($record->country ?? ''));
                    $record->verification_status = $status;
                    $record->last_status_sync_at = now();
                    $record->display_name = $displayName;
                    $record->country = $country;
                    $record->raw_meta = array_merge((array) $record->raw_meta, [
                        'charges_enabled' => (bool) $acct->charges_enabled,
                        'payouts_enabled' => (bool) $acct->payouts_enabled,
                        'requirements_due' => $acct->requirements->currently_due ?? [],
                    ]);
                    $record->save();
                }
                $payload = ['success' => true, 'account' => $record];
                if ($request->wantsJson() || (string) $request->input('format') === 'json') {
                    return response()->json($payload);
                }
                $appUrl = rtrim((string) config('app.url'), '/');
                $html = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">'
                    . '<title>Bank Account Connected</title>'
                    . '<style>body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;background:#f7f7f9;color:#222;margin:0;padding:0}'
                    . '.wrap{max-width:560px;margin:10vh auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 4px 16px rgba(0,0,0,0.06);padding:28px}'
                    . 'h1{font-size:22px;margin:0 0 8px}p{margin:8px 0 16px;color:#444}a.btn{display:inline-block;background:rgb(255, 106, 0);color:#fff;text-decoration:none;padding:10px 16px;border-radius:8px}'
                    . 'small{color:#666;display:block;margin-top:12px}</style></head><body>'
                    . '<div class="wrap">'
                    . '<h1>Bank Account Connected</h1>'
                    . '<p>Your payment account has been linked successfully. You can return to the app.</p>'
                    . '<a class="btn" href="' . $appUrl . '">Go Back to App</a>'
                    . '<small>If this window does not close automatically, use the button above.</small>'
                    // . '<script>(function(){try{if(window.opener){window.close()}else{setTimeout(function(){window.location.href="' . $appUrl . '"},1500)}}catch(e){}})()</script>'
                    . '</div></body></html>';
                return response($html, 200)->header('Content-Type', 'text/html');
            }

            return response()->json(['success' => false, 'message' => 'Missing account context'], 422);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $accounts = TrainerBankAccount::where('trainer_id', Auth::id())->get();

        $stripePg = PaymentGateway::where('type', 'stripe')->where('enabled', true)->where('is_default', true)->first();
        $stripe = $stripePg && $stripePg->secret_key ? new StripeClient($stripePg->secret_key) : null;

        foreach ($accounts as $acc) {
            if ($acc->gateway === 'stripe' && $stripe) {
                try {
                    $acct = $stripe->accounts->retrieve((string) $acc->account_id);
                    $status = ($acct->charges_enabled && $acct->payouts_enabled) ? 'verified' : 'pending';
                    $acc->verification_status = $status;
                    $acc->last_status_sync_at = now();
                    $acc->raw_meta = array_merge((array) $acc->raw_meta, [
                        'charges_enabled' => (bool) $acct->charges_enabled,
                        'payouts_enabled' => (bool) $acct->payouts_enabled,
                        'requirements_due' => $acct->requirements->currently_due ?? [],
                    ]);
                    $acc->save();
                } catch (\Exception $e) {
                }
            }
        }

        return response()->json(['success' => true, 'accounts' => $accounts]);
    }

    public function disconnect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'account_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $acc = TrainerBankAccount::where('trainer_id', Auth::id())->where('account_id', (string) $request->string('account_id'))->first();
        if (!$acc) {
            return response()->json(['success' => false, 'message' => 'Account not found'], 404);
        }

        if ($acc->gateway === 'stripe') {
            $pg = PaymentGateway::where('type', 'stripe')->where('enabled', true)->where('is_default', true)->first();
            if ($pg && $pg->secret_key) {
                try {
                    $stripe = new StripeClient($pg->secret_key);
                    $flow = (string) (($acc->raw_meta['flow'] ?? '') ?: 'express');
                    if ($flow === 'oauth' && $pg->connect_client_id) {
                        $resp = Http::asForm()->post('https://connect.stripe.com/oauth/deauthorize', [
                            'client_id' => $pg->connect_client_id,
                            'client_secret' => $pg->secret_key,
                            'stripe_user_id' => (string) $acc->account_id,
                        ]);
                    } else {
                        $stripe->accounts->delete((string) $acc->account_id);
                    }
                } catch (\Exception $e) {
                }
            }
        }

        $acc->delete();
        return response()->json(['success' => true]);
    }
}

