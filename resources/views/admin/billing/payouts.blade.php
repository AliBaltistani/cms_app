@extends('layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Payouts</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.billing.index') }}">Billing</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payouts</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.billing.index') }}" class="btn btn-outline-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-go-back-line fw-semibold align-middle me-1"></i> Billing Dashboard
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Filters -->
    <div class="card custom-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.billing.payouts') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="payout_status" class="form-label">Status</label>
                    <select name="payout_status" id="payout_status" class="form-select">
                        <option value="" {{ empty($payoutStatus) ? 'selected' : '' }}>All</option>
                        <option value="pending" {{ $payoutStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="paid" {{ $payoutStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="failed" {{ $payoutStatus === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply</button>
                    <a href="{{ route('admin.billing.payouts') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Payouts Table -->
    <div class="card custom-card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Trainer</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Stripe Payout ID</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $payout)
                            <tr>
                                <td>{{ $payout->id }}</td>
                                <td>{{ optional($payout->trainer)->name }}</td>
                                <td>
                                    <span class="badge bg-{{ $payout->payout_status === 'paid' ? 'success' : ($payout->payout_status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($payout->payout_status) }}</span>
                                </td>
                                <td>${{ number_format($payout->amount, 2) }}</td>
                                <td>{{ $payout->stripe_payout_id ?? '-' }}</td>
                                <td>{{ optional($payout->created_at)->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No payouts found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(method_exists($payouts, 'links'))
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted">Showing {{ $payouts->firstItem() }} to {{ $payouts->lastItem() }} of {{ $payouts->total() }} entries</div>
                <div>{{ $payouts->links() }}</div>
            </div>
        @endif
    </div>
@endsection