@extends('layouts.master')

@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Billing Dashboard</h1>
            <div>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Billing</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">Total Revenue</h6>
                    <h3 class="fw-bold">{{ number_format($totals['revenue'] ?? 0, 2) }}</h3>
                    <p class="text-muted mb-0">Sum of paid invoices</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">Pending Payouts</h6>
                    <h3 class="fw-bold">{{ number_format($totals['pending_payouts'] ?? 0, 2) }}</h3>
                    <p class="text-muted mb-0">Awaiting completion</p>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-body">
                    <h6 class="fw-semibold mb-2">Fees Collected</h6>
                    <h3 class="fw-bold">{{ number_format($totals['fees_collected'] ?? 0, 2) }}</h3>
                    <p class="text-muted mb-0">Application fees (Stripe)</p>
                </div>
            </div>
        </div>
    </div>
@endsection
