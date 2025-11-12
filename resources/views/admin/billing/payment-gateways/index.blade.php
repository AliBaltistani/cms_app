@extends('layouts.master')

@section('content')
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Payment Gateways</h1>
            <div>
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Payment Gateways</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <button class="btn btn-primary btn-wave waves-effect waves-light" data-bs-toggle="modal" data-bs-target="#gatewayModal">
                <i class="ri-add-line fw-semibold align-middle me-1"></i> Add Gateway
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">All Gateways</div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="ri-check-line me-2"></i>{{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered text-nowrap w-100" id="gatewaysTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Default</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($gateways as $gateway)
                                    <tr>
                                        <td>{{ $gateway->id }}</td>
                                        <td class="fw-semibold">{{ $gateway->name }}</td>
                                        <td>
                                            @if($gateway->type === 'stripe')
                                                <span class="badge bg-primary-transparent">Stripe</span>
                                            @else
                                                <span class="badge bg-info-transparent">PayPal</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gateway->enabled)
                                                <span class="badge bg-success-transparent">Enabled</span>
                                            @else
                                                <span class="badge bg-warning-transparent">Disabled</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($gateway->is_default)
                                                <span class="badge bg-success">Default</span>
                                            @else
                                                <span class="badge bg-secondary-transparent">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="hstack gap-2 fs-15">
                                                <form action="{{ route('admin.payment-gateways.enable', $gateway->id) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-icon btn-sm {{ $gateway->enabled ? 'btn-warning-transparent' : 'btn-success-transparent' }} rounded-pill" title="Toggle Enable">
                                                        <i class="{{ $gateway->enabled ? 'ri-pause-line' : 'ri-play-line' }}"></i>
                                                    </button>
                                                    <input type="hidden" name="enabled" value="{{ $gateway->enabled ? 0 : 1 }}">
                                                </form>
                                                <form action="{{ route('admin.payment-gateways.set-default', $gateway->id) }}" method="POST">
                                                    @csrf
                                                    <button class="btn btn-icon btn-sm btn-primary-transparent rounded-pill" title="Set Default">
                                                        <i class="ri-star-line"></i>
                                                    </button>
                                                </form>
                                                <button class="btn btn-icon btn-sm btn-info-transparent rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal-{{ $gateway->id }}" title="Edit">
                                                    <i class="ri-edit-line"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="editModal-{{ $gateway->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h6 class="modal-title">Edit Gateway</h6>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <form method="POST" action="{{ route('admin.payment-gateways.update', $gateway->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Name</label>
                                                            <input type="text" class="form-control" name="name" value="{{ $gateway->name }}" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Public Key</label>
                                                            <input type="text" class="form-control" name="public_key" value="{{ $gateway->public_key }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Secret Key</label>
                                                            <input type="text" class="form-control" name="secret_key" value="{{ $gateway->secret_key }}">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Webhook Secret</label>
                                                            <input type="text" class="form-control" name="webhook_secret" value="{{ $gateway->webhook_secret }}">
                                                        </div>
                                                        @if($gateway->type === 'stripe')
                                                        <div class="mb-3">
                                                            <label class="form-label">Stripe Connect Client ID</label>
                                                            <input type="text" class="form-control" name="connect_client_id" value="{{ $gateway->connect_client_id }}">
                                                        </div>
                                                        @endif
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="ri-bank-card-line fs-1 text-muted mb-2"></i>
                                                <h6 class="fw-semibold mb-1">No Gateways Found</h6>
                                                <p class="text-muted mb-0">Add a payment gateway to begin.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="gatewayModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Add Payment Gateway</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('admin.payment-gateways.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Stripe" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type" required>
                                <option value="stripe">Stripe</option>
                                <option value="paypal">PayPal</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Public Key</label>
                            <input type="text" class="form-control" name="public_key">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Secret Key</label>
                            <input type="text" class="form-control" name="secret_key">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Webhook Secret</label>
                            <input type="text" class="form-control" name="webhook_secret">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stripe Connect Client ID (Stripe)</label>
                            <input type="text" class="form-control" name="connect_client_id">
                        </div>
                        <input type="hidden" name="enabled" value="1">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Gateway</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#gatewaysTable').DataTable({
                responsive: true,
                ordering: false,
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
@endsection
