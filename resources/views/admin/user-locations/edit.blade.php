@extends('layouts.master')

@section('styles')
<!-- Form Validation CSS -->
<style>
.is-invalid {
    border-color: #dc3545;
}
.invalid-feedback {
    display: block;
}
</style>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Edit User Location</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.user-locations.index') }}">User Locations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        {{-- <a href="{{ route('admin.user-locations.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line align-middle me-1"></i>Back to List
        </a> --}}
        <a href="{{ route('admin.user-locations.show', $userLocation->id) }}" class="btn btn-info btn-wave waves-effect waves-light">
            <i class="ri-eye-line align-middle me-1"></i>View Details
        </a>
    </div>
</div>

<!-- Edit Form -->
<div class="row">
    <div class="col-xl-8 col-lg-10 col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-edit-line me-2"></i>Update Location Information
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.user-locations.update', $userLocation->id) }}" method="POST" id="editLocationForm">
                    @csrf
                    @method('PUT')
                    
                    <!-- User Information (Read-only) -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">User Information</label>
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-md me-3">
                                            @if ($userLocation->user->profile_image)
                                            <img src="{{ $userLocation->user->profile_image }}" 
                                                 alt="{{ $userLocation->user->name }}" 
                                                 class="rounded-circle">
                                        @else
                                            <div class="header-link-icon avatar bg-primary-transparent avatar-rounded d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.5rem;">
                                                {{ strtoupper(substr($userLocation->user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        </div>
                                        <div>
                                            <h6 class="mb-1">{{ $userLocation->user->name }}</h6>
                                            <div class="text-muted small">
                                                <span class="badge bg-{{ $userLocation->user->role === 'admin' ? 'danger' : ($userLocation->user->role === 'trainer' ? 'success' : 'primary') }}">
                                                    {{ ucfirst($userLocation->user->role) }}
                                                </span>
                                                <span class="ms-2">{{ $userLocation->user->email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-text">Location information for this user. User cannot be changed during edit.</div>
                        </div>
                    </div>

                    <!-- Country Field -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="country" class="form-label">Country</label>
                            <input type="text" 
                                   class="form-control @error('country') is-invalid @enderror" 
                                   id="country" 
                                   name="country" 
                                   value="{{ old('country', $userLocation->country) }}" 
                                   placeholder="Enter country name"
                                   maxlength="100">
                            @error('country')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 100 characters.</div>
                        </div>
                        
                        <!-- State Field -->
                        <div class="col-md-6">
                            <label for="state" class="form-label">State/Province</label>
                            <input type="text" 
                                   class="form-control @error('state') is-invalid @enderror" 
                                   id="state" 
                                   name="state" 
                                   value="{{ old('state', $userLocation->state) }}" 
                                   placeholder="Enter state or province"
                                   maxlength="100">
                            @error('state')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 100 characters.</div>
                        </div>
                    </div>

                    <!-- City and Zipcode -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="city" class="form-label">City</label>
                            <input type="text" 
                                   class="form-control @error('city') is-invalid @enderror" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city', $userLocation->city) }}" 
                                   placeholder="Enter city name"
                                   maxlength="100">
                            @error('city')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 100 characters.</div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="zipcode" class="form-label">Zipcode/Postal Code</label>
                            <input type="text" 
                                   class="form-control @error('zipcode') is-invalid @enderror" 
                                   id="zipcode" 
                                   name="zipcode" 
                                   value="{{ old('zipcode', $userLocation->zipcode) }}" 
                                   placeholder="Enter zipcode"
                                   maxlength="20">
                            @error('zipcode')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 20 characters.</div>
                        </div>
                    </div>

                    <!-- Address Field -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="address" class="form-label">Street Address</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="3" 
                                      placeholder="Enter complete street address"
                                      maxlength="255">{{ old('address', $userLocation->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 255 characters. Include street number, street name, apartment/unit number if applicable.</div>
                        </div>
                    </div>

                    <!-- Metadata -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body py-2">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Created:</strong> {{ $userLocation->created_at->format('M d, Y \a\t g:i A') }}
                                            </small>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">
                                                <strong>Last Updated:</strong> {{ $userLocation->updated_at->format('M d, Y \a\t g:i A') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between">
                                <a href="{{ route('admin.user-locations.index') }}" class="btn btn-light">
                                    <i class="ri-close-line me-1"></i>Cancel
                                </a>
                                <div>
                                    <button type="reset" class="btn btn-outline-secondary me-2">
                                        <i class="ri-refresh-line me-1"></i>Reset Changes
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i>Update Location
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Help Card -->
    <div class="col-xl-4 col-lg-2 col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-information-line me-2"></i>Update Guidelines
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Editing Tips:</h6>
                    <ul class="mb-0 small">
                        <li>All location fields are optional</li>
                        <li>Use standard country and state names</li>
                        <li>Ensure address accuracy for location features</li>
                        <li>Changes are saved immediately upon submission</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Important Notes:</h6>
                    <ul class="mb-0 small">
                        <li>User cannot be changed during edit</li>
                        <li>Location updates affect trainer-client matching</li>
                        <li>Empty fields will be saved as null values</li>
                    </ul>
                </div>
                
                <!-- Current Address Summary -->
                @if($userLocation->hasCompleteAddress())
                <div class="alert alert-success">
                    <h6 class="alert-heading">Current Address:</h6>
                    <p class="mb-0 small">{{ $userLocation->full_address }}</p>
                </div>
                @else
                <div class="alert alert-secondary">
                    <h6 class="alert-heading">Address Status:</h6>
                    <p class="mb-0 small">Incomplete address information. Consider adding more details for better location accuracy.</p>
                </div>
                @endif
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

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Remove validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // Auto-format zipcode (basic formatting)
    $('#zipcode').on('input', function() {
        var value = $(this).val().replace(/[^0-9a-zA-Z\-\s]/g, '');
        $(this).val(value);
    });
    
    // Capitalize first letter of city, state, country
    $('#city, #state, #country').on('blur', function() {
        var value = $(this).val();
        if (value) {
            $(this).val(value.charAt(0).toUpperCase() + value.slice(1).toLowerCase());
        }
    });
    
    // Reset form to original values
    $('button[type="reset"]').on('click', function(e) {
        e.preventDefault();
        
        // Reset to original values
        $('#country').val('{{ $userLocation->country }}');
        $('#state').val('{{ $userLocation->state }}');
        $('#city').val('{{ $userLocation->city }}');
        $('#zipcode').val('{{ $userLocation->zipcode }}');
        $('#address').val('{{ $userLocation->address }}');
        
        // Remove any validation errors
        $('.is-invalid').removeClass('is-invalid');
    });
    
    // Form submission confirmation for significant changes
    $('#editLocationForm').on('submit', function(e) {
        var hasChanges = false;
        
        // Check if any field has changed
        if ($('#country').val() !== '{{ $userLocation->country ?? "" }}' ||
            $('#state').val() !== '{{ $userLocation->state ?? "" }}' ||
            $('#city').val() !== '{{ $userLocation->city ?? "" }}' ||
            $('#zipcode').val() !== '{{ $userLocation->zipcode ?? "" }}' ||
            $('#address').val() !== '{{ $userLocation->address ?? "" }}') {
            hasChanges = true;
        }
        
        if (hasChanges) {
            // Allow form to submit normally
            return true;
        } else {
            // No changes detected
            alert('No changes detected. Please modify at least one field before saving.');
            e.preventDefault();
            return false;
        }
    });
});
</script>
@endsection