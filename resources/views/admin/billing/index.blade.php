@extends('layouts.master')

@section('styles')
    <!-- Billing dashboard specific styles (reserved for charts or widgets) -->
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Billing Dashboard</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Billing</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.billing.invoices') }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-file-list-2-line fw-semibold align-middle me-1"></i> All Invoices
            </a>
            <a href="{{ route('admin.billing.payouts') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-bank-card-2-line fw-semibold align-middle me-1"></i> All Payouts
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Filters -->
    <div class="card custom-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.billing.index') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" value="{{ $filters['start_date'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" id="end_date" name="end_date" class="form-control" value="{{ $filters['end_date'] ?? '' }}">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Start::row-1 Metrics -->
    <div class="row">
        <!-- Total Invoices -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-primary"><i class="ti ti-file-invoice fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Total Invoices</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['total_invoices']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Paid Invoices -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-success"><i class="ti ti-badge-check fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Paid Invoices</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['paid_invoices']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Unpaid Invoices -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-warning"><i class="ti ti-clock fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Unpaid Invoices</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['unpaid_invoices']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Invoices -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-danger"><i class="ti ti-alert-circle fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Failed Invoices</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['failed_invoices']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 Revenue/Payouts -->
    <div class="row">
        <!-- Total Revenue -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-info"><i class="ti ti-currency-dollar fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Total Revenue</p>
                            <h4 class="fw-semibold mt-1">${{ number_format($stats['total_revenue'], 2) }}</h4>
                            <span class="text-muted">Commission Rate: {{ number_format($commissionRate * 100, 0) }}%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Commission -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-secondary"><i class="ti ti-percentage fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Total Commission</p>
                            <h4 class="fw-semibold mt-1">${{ number_format($stats['total_commission'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Revenue -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-teal"><i class="ti ti-wallet fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Net Revenue</p>
                            <h4 class="fw-semibold mt-1">${{ number_format($stats['net_revenue'], 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 Payout Metrics -->
    <div class="row">
        <!-- Pending Payouts -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-warning"><i class="ri-time-line fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Pending Payouts</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['pending_payouts']) }} (${{ number_format($stats['payouts_sum_pending'], 2) }})</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Completed Payouts -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-success"><i class="ri-check-line fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Completed Payouts</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['completed_payouts']) }} (${{ number_format($stats['payouts_sum_paid'], 2) }})</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Failed Payouts -->
        <div class="col-xxl-4 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-danger"><i class="ri-close-circle-line fs-16"></i></span>
                        </div>
                        <div class="flex-fill ms-3">
                            <p class="text-muted mb-0">Failed Payouts</p>
                            <h4 class="fw-semibold mt-1">{{ number_format($stats['failed_payouts']) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->

    <!-- Recent Invoices & Payouts -->
    <div class="row">
        <!-- Recent Invoices -->
        <div class="col-xxl-6 col-xl-6 col-lg-12 col-md-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Recent Invoices</h5>
                    <a href="{{ route('admin.billing.invoices') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Trainer</th>
                                    <th>Client</th>
                                    <th>Status</th>
                                    <th>Total</th>
                                    <th>Net</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->id }}</td>
                                        <td>{{ optional($invoice->trainer)->name }}</td>
                                        <td>{{ optional($invoice->client)->name }}</td>
                                        <td><span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($invoice->status) }}</span></td>
                                        <td>${{ number_format($invoice->total_amount, 2) }}</td>
                                        <td>${{ number_format($invoice->net_amount ?? 0, 2) }}</td>
                                        <td>{{ $invoice->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted p-4">No recent invoices found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Payouts -->
        <div class="col-xxl-6 col-xl-6 col-lg-12 col-md-12">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">Recent Payouts</h5>
                    <a href="{{ route('admin.billing.payouts') }}" class="btn btn-sm btn-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Trainer</th>
                                    <th>Status</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentPayouts as $payout)
                                    <tr>
                                        <td>{{ $payout->id }}</td>
                                        <td>{{ optional($payout->trainer)->name }}</td>
                                        <td><span class="badge bg-{{ $payout->payout_status === 'paid' ? 'success' : ($payout->payout_status === 'failed' ? 'danger' : 'warning') }}">{{ ucfirst($payout->payout_status) }}</span></td>
                                        <td>${{ number_format($payout->amount, 2) }}</td>
                                        <td>{{ $payout->created_at->format('d/m/Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted p-4">No recent payouts found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection