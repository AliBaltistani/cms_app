@extends('layouts.master')

@section('content')
<div class="container">
    <h4>Add Payment Gateway</h4>
    <form method="POST" action="{{ route('admin.billing.gateways.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Gateway Name</label>
            <input type="text" name="gateway_name" class="form-control" value="{{ old('gateway_name') }}" required>
            @error('gateway_name')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Gateway Type</label>
            <select name="gateway_type" class="form-select" required>
                <option value="stripe">Stripe</option>
                <option value="paypal">PayPal</option>
                <option value="manual">Manual</option>
            </select>
            @error('gateway_type')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Public Key / Client ID</label>
            <input type="text" name="public_key" class="form-control" value="{{ old('public_key') }}">
            @error('public_key')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Secret Key / Client Secret</label>
            <input type="text" name="secret_key" class="form-control" value="{{ old('secret_key') }}">
            @error('secret_key')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Webhook Secret</label>
            <input type="text" name="webhook_secret" class="form-control" value="{{ old('webhook_secret') }}">
            @error('webhook_secret')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Account ID (Stripe Connect)</label>
            <input type="text" name="account_id" class="form-control" value="{{ old('account_id') }}">
            @error('account_id')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="mb-3">
            <label class="form-label">Commission Rate (%)</label>
            <input type="number" step="0.01" min="0" max="100" name="commission_rate" class="form-control" value="{{ old('commission_rate', 10.00) }}" required>
            @error('commission_rate')<div class="text-danger small">{{ $message }}</div>@enderror
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="is_default">
            <label class="form-check-label" for="is_default">Set as Default</label>
        </div>
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="status" value="1" id="status" checked>
            <label class="form-check-label" for="status">Enabled</label>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>
@endsection
