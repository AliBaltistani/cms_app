<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderByDesc('is_default')->orderBy('name')->get();
        return view('admin.billing.payment-gateways.index', compact('gateways'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:stripe,paypal',
            'public_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'connect_client_id' => 'nullable|string',
            'enabled' => 'sometimes|boolean',
        ]);

        $gateway = PaymentGateway::create($data);
        return redirect()->route('admin.payment-gateways.index')->with('success', 'Gateway created');
    }

    public function update($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'public_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'connect_client_id' => 'nullable|string',
            'enabled' => 'sometimes|boolean',
        ]);
        $gateway->update($data);
        return redirect()->route('admin.payment-gateways.index')->with('success', 'Gateway updated');
    }

    public function enable($id, Request $request)
    {
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->enabled = (bool) $request->input('enabled', true);
        $gateway->save();
        return redirect()->route('admin.payment-gateways.index')->with('success', 'Gateway status updated');
    }

    public function setDefault($id)
    {
        PaymentGateway::query()->update(['is_default' => false]);
        $gateway = PaymentGateway::findOrFail($id);
        $gateway->is_default = true;
        $gateway->enabled = true;
        $gateway->save();
        return redirect()->route('admin.payment-gateways.index')->with('success', 'Default gateway set');
    }
}

