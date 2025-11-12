<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerStripeAccount;
use Illuminate\Http\Request;

class TrainerBankController extends Controller
{
    public function index(Request $request)
    {
        $verification = $request->input('verification_status');
        $bankStatus = $request->input('bank_verification_status');
        $query = TrainerStripeAccount::query()->orderByDesc('id');
        if ($verification) {
            $query->where('verification_status', $verification);
        }
        if ($bankStatus) {
            $query->where('bank_verification_status', $bankStatus);
        }
        $accounts = $query->paginate(20)->appends([$verification, $bankStatus]);
        return view('admin.billing.bank-accounts', compact('accounts', 'verification', 'bankStatus'));
    }
}

