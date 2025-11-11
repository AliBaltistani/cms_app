@extends('layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Invoices</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.billing.index') }}" class="btn btn-outline-primary btn-wave waves-effect waves-light">
                <i class="ri-arrow-go-back-line fw-semibold align-middle me-1"></i> Billing Dashboard
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Filters -->
    <div class="card custom-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.billing.invoices') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="status" class="form-label">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="" {{ empty($status) ? 'selected' : '' }}>All</option>
                        <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="unpaid" {{ $status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply</button>
                    <a href="{{ route('admin.billing.invoices') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Invoices Table -->
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainer</th>
                            <th>Client</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Commission</th>
                            <th>Net</th>
                            <th>Transaction</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td>{{ optional($invoice->trainer)->name }}</td>
                                <td>{{ optional($invoice->client)->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'failed' ? 'danger' : ($invoice->status === 'draft' ? 'secondary' : 'warning')) }}">{{ ucfirst($invoice->status) }}</span>
                                </td>
                                <td>{{ ucfirst($invoice->payment_method ?? 'n/a') }}</td>
                                <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                <td>${{ number_format($invoice->commission_amount ?? 0, 2) }}</td>
                                <td>${{ number_format($invoice->net_amount ?? 0, 2) }}</td>
                                <td>{{ $invoice->transaction_id ?? '-' }}</td>
                                <td>{{ optional($invoice->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted p-4">No invoices found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($invoices, 'links'))
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted">Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} entries</div>
                <div>{{ $invoices->links() }}</div>
            </div>
        @endif
    </div>
@endsection