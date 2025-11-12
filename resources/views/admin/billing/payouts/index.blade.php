@extends('layouts.master')

@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Payouts</h1>
            <div>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payouts</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.payouts.export') }}" class="btn btn-success btn-wave waves-effect waves-light">
                <i class="ri-download-line fw-semibold align-middle me-1"></i> Export CSV
            </a>
        </div>
    </div>

    <div class="card custom-card">
        <div class="card-header justify-content-between">
            <div class="card-title">All Payouts</div>
            <form method="GET" class="d-flex">
                <select name="status" class="form-select form-select-sm me-2" style="max-width:180px">
                    <option value="">All Status</option>
                    @foreach(['processing','completed','failed'] as $st)
                        <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="payoutsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trainer</th>
                            <th>Amount</th>
                            <th>Currency</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Scheduled</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payouts as $payout)
                            <tr>
                                <td>{{ $payout->id }}</td>
                                <td class="fw-semibold">{{ $payout->trainer->name ?? '#' }}</td>
                                <td>{{ number_format($payout->amount,2) }}</td>
                                <td>{{ strtoupper($payout->currency) }}</td>
                                <td>{{ number_format($payout->fee_amount,2) }}</td>
                                <td>
                                    @if($payout->payout_status==='processing')
                                        <span class="badge bg-warning-transparent">Processing</span>
                                    @elseif($payout->payout_status==='completed')
                                        <span class="badge bg-success-transparent">Completed</span>
                                    @else
                                        <span class="badge bg-danger-transparent">Failed</span>
                                    @endif
                                </td>
                                <td>{{ $payout->scheduled_at ? $payout->scheduled_at->format('M d, Y H:i') : '—' }}</td>
                                <td>{{ $payout->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ri-exchange-dollar-line fs-1 text-muted mb-2"></i>
                                        <h6 class="fw-semibold mb-1">No Payouts Found</h6>
                                        <p class="text-muted mb-0">No records match your filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($payouts->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <p class="text-muted mb-0">Showing {{ $payouts->firstItem() }} to {{ $payouts->lastItem() }} of {{ $payouts->total() }} results</p>
                    {{ $payouts->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function(){
            $('#payoutsTable').DataTable({responsive:true, ordering:false, paging:false, searching:false, info:false});
        });
    </script>
@endsection
