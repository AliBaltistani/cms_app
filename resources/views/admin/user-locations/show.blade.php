@extends('layouts.master')

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">User Location Details</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.user-locations.index') }}">User Locations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Details</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.user-locations.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line align-middle me-1"></i>Back to List
        </a>
        <a href="{{ route('admin.user-locations.edit', $userLocation->id) }}" class="btn btn-primary btn-wave waves-effect waves-light">
            <i class="ri-edit-line align-middle me-1"></i>Edit Location
        </a>
        <button type="button" class="btn btn-danger btn-wave waves-effect waves-light" onclick="confirmDelete({{ $userLocation->id }})">
            <i class="ri-delete-bin-line align-middle me-1"></i>Delete
        </button>
    </div>
</div>

<!-- Location Details -->
<div class="row">
    <!-- Main Details Card -->
    <div class="col-xl-8 col-lg-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-map-pin-line me-2"></i>Location Information
                </div>
                <div class="card-options">
                    <span class="badge bg-{{ $userLocation->hasCompleteAddress() ? 'success' : 'warning' }}">
                        {{ $userLocation->hasCompleteAddress() ? 'Complete Address' : 'Incomplete Address' }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- User Information -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <h6 class="mb-3">User Information</h6>
                        <div class="card bg-light">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-lg me-3">
                                        <img src="{{ $userLocation->user->profile_image ?? '/assets/images/faces/default-avatar.png' }}" 
                                             alt="{{ $userLocation->user->name }}" 
                                             class="rounded-circle">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">{{ $userLocation->user->name }}</h5>
                                        <div class="text-muted mb-2">
                                            <span class="badge bg-{{ $userLocation->user->role === 'admin' ? 'danger' : ($userLocation->user->role === 'trainer' ? 'success' : 'primary') }} me-2">
                                                {{ ucfirst($userLocation->user->role) }}
                                            </span>
                                            <span>{{ $userLocation->user->email }}</span>
                                        </div>
                                        @if($userLocation->user->phone)
                                        <div class="text-muted">
                                            <i class="ri-phone-line me-1"></i>{{ $userLocation->user->phone }}
                                        </div>
                                        @endif
                                    </div>
                                    <div>
                                        <a href="{{ route('admin.users.show', $userLocation->user->id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ri-user-line me-1"></i>View Profile
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Address Details -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Country</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->country ?: 'Not specified' }}
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">State/Province</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->state ?: 'Not specified' }}
                        </div>
                    </div>
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-semibold">City</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->city ?: 'Not specified' }}
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-semibold">Zipcode/Postal Code</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->zipcode ?: 'Not specified' }}
                        </div>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-semibold">Street Address</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->address ?: 'Not specified' }}
                        </div>
                    </div>
                </div>

                <!-- Full Address Display -->
                @if($userLocation->hasCompleteAddress())
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="ri-map-pin-2-line me-2"></i>Complete Address
                            </h6>
                            <p class="mb-0">{{ $userLocation->full_address }}</p>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Sidebar Information -->
    <div class="col-xl-4 col-lg-12">
        <!-- Metadata Card -->
        <div class="card custom-card mb-3">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-information-line me-2"></i>Record Information
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 mb-3">
                        <label class="form-label fw-semibold">Location ID</label>
                        <div class="form-control-plaintext">#{{ $userLocation->id }}</div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-semibold">Created Date</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->created_at->format('M d, Y') }}
                            <br><small class="text-muted">{{ $userLocation->created_at->format('g:i A') }}</small>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label fw-semibold">Last Updated</label>
                        <div class="form-control-plaintext">
                            {{ $userLocation->updated_at->format('M d, Y') }}
                            <br><small class="text-muted">{{ $userLocation->updated_at->format('g:i A') }}</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Time Ago</label>
                        <div class="form-control-plaintext">
                            <small class="text-muted">
                                Created {{ $userLocation->created_at->diffForHumans() }}
                                @if($userLocation->created_at != $userLocation->updated_at)
                                <br>Updated {{ $userLocation->updated_at->diffForHumans() }}
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Status Card -->
        <div class="card custom-card mb-3">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-checkbox-circle-line me-2"></i>Address Status
                </div>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Country</span>
                        <span class="badge bg-{{ $userLocation->country ? 'success' : 'secondary' }}">
                            {{ $userLocation->country ? 'Set' : 'Missing' }}
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>State/Province</span>
                        <span class="badge bg-{{ $userLocation->state ? 'success' : 'secondary' }}">
                            {{ $userLocation->state ? 'Set' : 'Missing' }}
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>City</span>
                        <span class="badge bg-{{ $userLocation->city ? 'success' : 'secondary' }}">
                            {{ $userLocation->city ? 'Set' : 'Missing' }}
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Zipcode</span>
                        <span class="badge bg-{{ $userLocation->zipcode ? 'success' : 'secondary' }}">
                            {{ $userLocation->zipcode ? 'Set' : 'Missing' }}
                        </span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                        <span>Street Address</span>
                        <span class="badge bg-{{ $userLocation->address ? 'success' : 'secondary' }}">
                            {{ $userLocation->address ? 'Set' : 'Missing' }}
                        </span>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div class="progress" style="height: 8px;">
                        @php
                            $completeness = 0;
                            if($userLocation->country) $completeness += 20;
                            if($userLocation->state) $completeness += 20;
                            if($userLocation->city) $completeness += 20;
                            if($userLocation->zipcode) $completeness += 20;
                            if($userLocation->address) $completeness += 20;
                        @endphp
                        <div class="progress-bar bg-{{ $completeness >= 80 ? 'success' : ($completeness >= 40 ? 'warning' : 'danger') }}" 
                             role="progressbar" 
                             style="width: {{ $completeness }}%" 
                             aria-valuenow="{{ $completeness }}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                    <small class="text-muted">Address Completeness: {{ $completeness }}%</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-settings-3-line me-2"></i>Quick Actions
                </div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.user-locations.edit', $userLocation->id) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line me-1"></i>Edit Location
                    </a>
                    <a href="{{ route('admin.users.show', $userLocation->user->id) }}" class="btn btn-info btn-sm">
                        <i class="ri-user-line me-1"></i>View User Profile
                    </a>
                    @if($userLocation->user->role === 'trainer')
                    <a href="{{ route('admin.trainers.show', $userLocation->user->id) }}" class="btn btn-success btn-sm">
                        <i class="ri-user-star-line me-1"></i>View Trainer Details
                    </a>
                    @endif
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDelete({{ $userLocation->id }})">
                        <i class="ri-delete-bin-line me-1"></i>Delete Location
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="ri-delete-bin-line me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="ri-alert-line me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone.
                </div>
                <p>Are you sure you want to delete this location record?</p>
                <div class="card bg-light">
                    <div class="card-body py-2">
                        <strong>User:</strong> {{ $userLocation->user->name }}<br>
                        <strong>Location:</strong> {{ $userLocation->short_address ?: 'Incomplete address' }}
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i>Cancel
                </button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="ri-delete-bin-line me-1"></i>Delete Location
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
function confirmDelete(locationId) {
    // Set the form action
    document.getElementById('deleteForm').action = '{{ route("admin.user-locations.destroy", ":id") }}'.replace(':id', locationId);
    
    // Show the modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
});
</script>
@endsection