<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payout;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function invoices(Request $request)
    {
        $query = Invoice::query();
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        $invoices = $query->latest()->paginate(20)->appends($request->query());
        return view('admin.billing.invoices.index', compact('invoices'));
    }

    public function payouts(Request $request)
    {
        $query = Payout::query();
        if ($request->filled('status')) {
            $query->where('payout_status', $request->string('status'));
        }
        $payouts = $query->latest()->paginate(20)->appends($request->query());
        return view('admin.billing.payouts.index', compact('payouts'));
    }

    public function exportPayouts()
    {
        return response()->noContent();
    }

    public function dashboard()
    {
        $totals = [
            'revenue' => Invoice::where('status', 'paid')->sum('total_amount'),
            'pending_payouts' => Payout::where('payout_status', 'processing')->sum('amount'),
            'fees_collected' => Payout::sum('fee_amount'),
        ];
        return view('admin.billing.dashboard', compact('totals'));
    }

    public function retryPayout($id)
    {
        $payout = Payout::findOrFail($id);
        $payout->payout_status = 'processing';
        $payout->save();
        return redirect()->route('admin.payouts.index')->with('success', 'Payout retry triggered');
    }
}
