<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Support\Facades\Auth;

class TrainerPayoutController extends Controller
{
    public function index()
    {
        $payouts = Payout::where('trainer_id', Auth::id())->latest()->paginate(20);
        return response()->json(['success' => true, 'payouts' => $payouts]);
    }
}

