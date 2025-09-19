@extends('layouts.master')

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Booking Details</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Booking #{{ $booking->id }}</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-edit-line fw-semibold align-middle me-1"></i> Edit Booking
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to Bookings
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <!-- Booking Information -->
        <div class="col-xl-8">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Booking Information
                    </div>
                    <div class="ms-auto">
                        @if($booking->status == 'pending')
                            <span class="badge bg-warning-transparent fs-12">Pending Approval</span>
                        @elseif($booking->status == 'confirmed')
                            <span class="badge bg-success-transparent fs-12">Confirmed</span>
                        @else
                            <span class="badge bg-danger-transparent fs-12">Cancelled</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="table-responsive">
                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="fw-semibold text-muted">Booking ID:</td>
                                            <td>#{{ $booking->id }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Date:</td>
                                            <td>
                                                <span class="fw-semibold">{{ $booking->date->format('l, F d, Y') }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Time:</td>
                                            <td>
                                                <span class="fw-semibold">{{ $booking->start_time->format('h:i A') }} - {{ $booking->end_time->format('h:i A') }}</span>
                                                <small class="text-muted ms-2">({{ $booking->getDurationInMinutes() }} minutes)</small>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Status:</td>
                                            <td>
                                                @if($booking->status == 'pending')
                                                    <span class="badge bg-warning-transparent">Pending Approval</span>
                                                @elseif($booking->status == 'confirmed')
                                                    <span class="badge bg-success-transparent">Confirmed</span>
                                                @else
                                                    <span class="badge bg-danger-transparent">Cancelled</span>
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Created:</td>
                                            <td>{{ $booking->created_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-semibold text-muted">Last Updated:</td>
                                            <td>{{ $booking->updated_at->format('M d, Y h:i A') }}</td>
                                        </tr>
                                        @if($booking->notes)
                                            <tr>
                                                <td class="fw-semibold text-muted">Notes:</td>
                                                <td>
                                                    <div class="p-3 bg-light rounded">
                                                        {{ $booking->notes }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Quick Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($booking->status == 'pending')
                            <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="trainer_id" value="{{ $booking->trainer_id }}">
                                <input type="hidden" name="client_id" value="{{ $booking->client_id }}">
                                <input type="hidden" name="date" value="{{ $booking->date->format('Y-m-d') }}">
                                <input type="hidden" name="start_time" value="{{ $booking->start_time->format('H:i') }}">
                                <input type="hidden" name="end_time" value="{{ $booking->end_time->format('H:i') }}">
                                <input type="hidden" name="status" value="confirmed">
                                <input type="hidden" name="notes" value="{{ $booking->notes }}">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="ri-check-line me-1"></i> Confirm Booking
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.bookings.update', $booking->id) }}" method="POST" style="display: inline;">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="trainer_id" value="{{ $booking->trainer_id }}">
                                <input type="hidden" name="client_id" value="{{ $booking->client_id }}">
                                <input type="hidden" name="date" value="{{ $booking->date->format('Y-m-d') }}">
                                <input type="hidden" name="start_time" value="{{ $booking->start_time->format('H:i') }}">
                                <input type="hidden" name="end_time" value="{{ $booking->end_time->format('H:i') }}">
                                <input type="hidden" name="status" value="cancelled">
                                <input type="hidden" name="notes" value="{{ $booking->notes }}">
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="ri-close-line me-1"></i> Cancel Booking
                                </button>
                            </form>
                        @endif
                        
                        <a href="{{ route('admin.bookings.edit', $booking->id) }}" class="btn btn-primary w-100">
                            <i class="ri-edit-line me-1"></i> Edit Booking
                        </a>
                        
                        <button class="btn btn-danger w-100" onclick="deleteBooking('{{ $booking->id }}')">
                            <i class="ri-delete-bin-line me-1"></i> Delete Booking
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 -->
    <div class="row">
        <!-- Trainer Information -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Trainer Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-lg me-3">
                            <img src="{{ $booking->trainer->profile_image ? asset('storage/' . $booking->trainer->profile_image) : asset('assets/images/faces/9.jpg') }}" 
                                 alt="trainer" class="avatar-img rounded-circle">
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-1">{{ $booking->trainer->name }}</h6>
                            <p class="text-muted mb-0">{{ $booking->trainer->designation ?? 'Personal Trainer' }}</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-muted">Email:</td>
                                    <td>
                                        <a href="mailto:{{ $booking->trainer->email }}">{{ $booking->trainer->email }}</a>
                                    </td>
                                </tr>
                                @if($booking->trainer->phone)
                                    <tr>
                                        <td class="fw-semibold text-muted">Phone:</td>
                                        <td>
                                            <a href="tel:{{ $booking->trainer->phone }}">{{ $booking->trainer->phone }}</a>
                                        </td>
                                    </tr>
                                @endif
                                @if($booking->trainer->experience)
                                    <tr>
                                        <td class="fw-semibold text-muted">Experience:</td>
                                        <td>{{ $booking->trainer->experience }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Client Information -->
        <div class="col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Client Information
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="avatar avatar-lg me-3">
                            <img src="{{ $booking->client->profile_image ? asset('storage/' . $booking->client->profile_image) : asset('assets/images/faces/9.jpg') }}" 
                                 alt="client" class="avatar-img rounded-circle">
                        </div>
                        <div>
                            <h6 class="fw-semibold mb-1">{{ $booking->client->name }}</h6>
                            <p class="text-muted mb-0">Client</p>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless table-sm">
                            <tbody>
                                <tr>
                                    <td class="fw-semibold text-muted">Email:</td>
                                    <td>
                                        <a href="mailto:{{ $booking->client->email }}">{{ $booking->client->email }}</a>
                                    </td>
                                </tr>
                                @if($booking->client->phone)
                                    <tr>
                                        <td class="fw-semibold text-muted">Phone:</td>
                                        <td>
                                            <a href="tel:{{ $booking->client->phone }}">{{ $booking->client->phone }}</a>
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td class="fw-semibold text-muted">Member Since:</td>
                                    <td>{{ $booking->client->created_at ? $booking->client->created_at->format('M Y') : 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title" id="deleteModalLabel">Delete Booking</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this booking? This action cannot be undone.</p>
                    <div class="alert alert-warning" role="alert">
                        <i class="ri-alert-line me-2"></i>
                        <strong>Warning:</strong> Deleting this booking will permanently remove all associated data.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function deleteBooking(id) {
            $('#deleteForm').attr('action', '/admin/bookings/' + id);
            $('#deleteModal').modal('show');
        }
    </script>
@endsection