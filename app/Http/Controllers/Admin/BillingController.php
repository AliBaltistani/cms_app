<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BillingController (Admin)
 *
 * Provides billing dashboard metrics and list pages for invoices and payouts.
 * Aggregates revenue, commission, and payout status for administrators.
 *
 * @package     GoGlobe Trainer
 * @subpackage  Controllers
 * @category    Billing
 * @author      Dev Team
 * @since       1.0.0
 */
class BillingController extends Controller
{
    /**
     * Display the billing dashboard with high-level metrics.
     *
     * @param Request $request Incoming request, supports optional date filters
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        try
        {
            // Optional date range filters
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $invoiceQuery = Invoice::query();
            $payoutQuery = Payout::query();

            if ($startDate)
            {
                $invoiceQuery->whereDate('created_at', '>=', $startDate);
                $payoutQuery->whereDate('created_at', '>=', $startDate);
            }
            if ($endDate)
            {
                $invoiceQuery->whereDate('created_at', '<=', $endDate);
                $payoutQuery->whereDate('created_at', '<=', $endDate);
            }

            // Core metrics
            $stats = [
                'total_invoices' => (int) $invoiceQuery->count(),
                'unpaid_invoices' => (int) (clone $invoiceQuery)->where('status', 'unpaid')->count(),
                'paid_invoices' => (int) (clone $invoiceQuery)->where('status', 'paid')->count(),
                'failed_invoices' => (int) (clone $invoiceQuery)->where('status', 'failed')->count(),
                'total_revenue' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('total_amount'),
                'total_commission' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('commission_amount'),
                'net_revenue' => (float) (clone $invoiceQuery)->where('status', 'paid')->sum('net_amount'),
                'pending_payouts' => (int) (clone $payoutQuery)->where('payout_status', 'pending')->count(),
                'completed_payouts' => (int) (clone $payoutQuery)->where('payout_status', 'paid')->count(),
                'failed_payouts' => (int) (clone $payoutQuery)->where('payout_status', 'failed')->count(),
                'payouts_sum_pending' => (float) (clone $payoutQuery)->where('payout_status', 'pending')->sum('amount'),
                'payouts_sum_paid' => (float) (clone $payoutQuery)->where('payout_status', 'paid')->sum('amount'),
            ];

            $commissionRate = (float) config('billing.commission_rate', 0.10);

            // Latest records for quick overview
            $recentInvoices = Invoice::with(['trainer', 'client'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            $recentPayouts = Payout::with(['trainer'])
                ->orderByDesc('created_at')
                ->limit(10)
                ->get();

            return view('admin.billing.index', [
                'stats' => $stats,
                'commissionRate' => $commissionRate,
                'recentInvoices' => $recentInvoices,
                'recentPayouts' => $recentPayouts,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
            ]);
        }
        catch (\Throwable $e)
        {
            Log::error('Admin billing dashboard failed', [
                'message' => $e->getMessage(),
            ]);

            // User-friendly error display
            return back()->with('error', 'Unable to load billing dashboard. Please try again.');
        }
    }

    /**
     * Display a list of invoices with optional status filters.
     *
     * @param Request $request Incoming request with filters: status
     * @return \Illuminate\Contracts\View\View
     */
    public function invoices(Request $request)
    {
        try
        {
            $status = $request->input('status');
            $query = Invoice::with(['trainer', 'client'])->orderByDesc('created_at');

            if ($status)
            {
                $query->where('status', $status);
            }

            $invoices = $query->paginate(20)->appends(['status' => $status]);

            return view('admin.billing.invoices', [
                'invoices' => $invoices,
                'status' => $status,
            ]);
        }
        catch (\Throwable $e)
        {
            Log::error('Admin invoices list failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load invoices. Please try again.');
        }
    }

    /**
     * Display a list of payouts with optional status filters.
     *
     * @param Request $request Incoming request with filters: payout_status
     * @return \Illuminate\Contracts\View\View
     */
    public function payouts(Request $request)
    {
        try
        {
            $payoutStatus = $request->input('payout_status');
            $query = Payout::with(['trainer'])->orderByDesc('created_at');

            if ($payoutStatus)
            {
                $query->where('payout_status', $payoutStatus);
            }

            $payouts = $query->paginate(20)->appends(['payout_status' => $payoutStatus]);

            return view('admin.billing.payouts', [
                'payouts' => $payouts,
                'payoutStatus' => $payoutStatus,
            ]);
        }
        catch (\Throwable $e)
        {
            Log::error('Admin payouts list failed', [
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to load payouts. Please try again.');
        }
    }
}