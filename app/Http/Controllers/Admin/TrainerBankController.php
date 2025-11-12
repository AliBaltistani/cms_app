<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainerBankAccount;
use App\Models\User;

class TrainerBankController extends Controller
{
    public function index($id)
    {
        $trainer = User::findOrFail($id);
        $accounts = TrainerBankAccount::where('trainer_id', $trainer->id)->latest()->get();
        return view('admin.billing.trainers.bank-accounts', compact('trainer', 'accounts'));
    }
}

