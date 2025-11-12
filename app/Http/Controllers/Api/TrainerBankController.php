<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use App\Models\TrainerBankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerBankController extends Controller
{
    public function connect(Request $request)
    {
        $gateway = PaymentGateway::where('type', 'stripe')->where('enabled', true)->where('is_default', true)->first();
        if (!$gateway || !$gateway->connect_client_id) {
            return response()->json(['success' => false, 'message' => 'Stripe Connect not configured'], 422);
        }
        $redirectUrl = 'https://connect.stripe.com/oauth/authorize?response_type=code&client_id=' . urlencode($gateway->connect_client_id) . '&scope=read_write';
        return response()->json(['success' => true, 'url' => $redirectUrl]);
    }

    public function callback(Request $request)
    {
        $code = $request->string('code');
        if (!$code) {
            return response()->json(['success' => false, 'message' => 'Missing code'], 422);
        }
        $accountId = 'acct_placeholder';
        $account = TrainerBankAccount::create([
            'trainer_id' => Auth::id(),
            'gateway' => 'stripe',
            'account_id' => $accountId,
            'verification_status' => 'pending',
        ]);
        return response()->json(['success' => true, 'account' => $account]);
    }

    public function index()
    {
        $accounts = TrainerBankAccount::where('trainer_id', Auth::id())->get();
        return response()->json(['success' => true, 'accounts' => $accounts]);
    }

    public function disconnect(Request $request)
    {
        $request->validate(['account_id' => 'required|string']);
        $acc = TrainerBankAccount::where('trainer_id', Auth::id())->where('account_id', $request->string('account_id'))->first();
        if (!$acc) {
            return response()->json(['success' => false, 'message' => 'Account not found'], 404);
        }
        $acc->delete();
        return response()->json(['success' => true]);
    }
}

