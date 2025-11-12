<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class ClientInvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::where('client_id', Auth::id())->latest()->paginate(20);
        return response()->json(['success' => true, 'invoices' => $invoices]);
    }
}

