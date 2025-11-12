@extends('layouts.master')

@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Invoices</h1>
            <div>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn"></div>
    </div>

    <div class="card custom-card">
        <div class="card-header justify-content-between">
            <div class="card-title">All Invoices</div>
            <form method="GET" class="d-flex">
                <select name="status" class="form-select form-select-sm me-2" style="max-width:180px">
                    <option value="">All Status</option>
                    @foreach(['draft','pending','paid','failed','cancelled'] as $st)
                        <option value="{{ $st }}" {{ request('status')===$st?'selected':'' }}>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm btn-primary">Filter</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered text-nowrap w-100" id="invoicesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Trainer</th>
                            <th>Client</th>
                            <th>Total</th>
                            <th>Currency</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td>{{ $invoice->id }}</td>
                                <td class="fw-semibold">{{ $invoice->trainer->name ?? '#' }}</td>
                                <td class="fw-semibold">{{ $invoice->client->name ?? '#' }}</td>
                                <td>{{ number_format($invoice->total_amount,2) }}</td>
                                <td>{{ strtoupper($invoice->currency) }}</td>
                                <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</td>
                                <td>
                                    @if($invoice->status==='pending')
                                        <span class="badge bg-warning-transparent">Pending</span>
                                    @elseif($invoice->status==='paid')
                                        <span class="badge bg-success-transparent">Paid</span>
                                    @elseif($invoice->status==='failed')
                                        <span class="badge bg-danger-transparent">Failed</span>
                                    @elseif($invoice->status==='cancelled')
                                        <span class="badge bg-secondary-transparent">Cancelled</span>
                                    @else
                                        <span class="badge bg-info-transparent">Draft</span>
                                    @endif
                                </td>
                                <td>{{ $invoice->created_at->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ri-file-text-line fs-1 text-muted mb-2"></i>
                                        <h6 class="fw-semibold mb-1">No Invoices Found</h6>
                                        <p class="text-muted mb-0">No records match your filter.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($invoices->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <p class="text-muted mb-0">Showing {{ $invoices->firstItem() }} to {{ $invoices->lastItem() }} of {{ $invoices->total() }} results</p>
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(function(){
            $('#invoicesTable').DataTable({responsive:true, ordering:false, paging:false, searching:false, info:false});
        });
    </script>
@endsection
