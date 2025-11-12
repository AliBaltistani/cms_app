<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderByDesc('id')->paginate(20);
        return view('admin.billing.gateways.index', compact('gateways'));
    }

    public function create()
    {
        return view('admin.billing.gateways.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'gateway_name' => 'required|string',
            'gateway_type' => 'required|in:stripe,paypal,manual',
            'public_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'account_id' => 'nullable|string',
            'is_default' => 'sometimes|boolean',
            'status' => 'required|boolean',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        if (!empty($data['is_default'])) {
            PaymentGateway::where('is_default', true)->update(['is_default' => false]);
        }

        PaymentGateway::create($data);
        return redirect()->route('admin.billing.gateways.index');
    }

    public function edit(PaymentGateway $gateway)
    {
        return view('admin.billing.gateways.edit', compact('gateway'));
    }

    public function update(Request $request, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'gateway_name' => 'required|string',
            'gateway_type' => 'required|in:stripe,paypal,manual',
            'public_key' => 'nullable|string',
            'secret_key' => 'nullable|string',
            'webhook_secret' => 'nullable|string',
            'account_id' => 'nullable|string',
            'is_default' => 'sometimes|boolean',
            'status' => 'required|boolean',
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        if (!empty($data['is_default'])) {
            PaymentGateway::where('id', '!=', $gateway->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $gateway->update($data);
        return redirect()->route('admin.billing.gateways.index');
    }

    public function toggle(PaymentGateway $gateway)
    {
        $gateway->status = !$gateway->status;
        $gateway->save();
        return redirect()->route('admin.billing.gateways.index');
    }
}
