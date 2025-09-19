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
        <h1 class="page-title fw-semibold fs-18 mb-0">Add New User Location</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.user-locations.index') }}">User Locations</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.user-locations.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line align-middle me-1"></i>Back to List
        </a>
    </div>
</div>

<!-- Create Form -->
<div class="row">
    <div class="col-xl-8 col-lg-10 col-md-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-map-pin-add-line me-2"></i>Location Information
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.user-locations.store') }}" method="POST" id="createLocationForm">
                    @csrf
                    
                    <!-- User Selection -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="user_id" class="form-label">Select User <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" 
                                    id="user_id" 
                                    name="user_id" 
                                    required>
                                <option value="">Choose a user...</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                            {{ old('user_id') == $user->id ? 'selected' : '' }}
                                            data-role="{{ $user->role }}">
                                        {{ $user->name }} ({{ ucfirst($user->role) }}) - {{ $user->email }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Select the user for whom you want to add location information.</div>
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
                                   value="{{ old('country') }}" 
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
                                   value="{{ old('state') }}" 
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
                                   value="{{ old('city') }}" 
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
                                   value="{{ old('zipcode') }}" 
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
                                      maxlength="255">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum 255 characters. Include street number, street name, apartment/unit number if applicable.</div>
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
                                        <i class="ri-refresh-line me-1"></i>Reset
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line me-1"></i>Save Location
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
                    <i class="ri-information-line me-2"></i>Location Guidelines
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <h6 class="alert-heading">Tips for Adding Locations:</h6>
                    <ul class="mb-0 small">
                        <li>All fields are optional except user selection</li>
                        <li>Use standard country and state names</li>
                        <li>Include complete street address for better accuracy</li>
                        <li>Zipcode helps with location-based searches</li>
                        <li>Users can have only one location record</li>
                    </ul>
                </div>
                
                <div class="alert alert-warning">
                    <h6 class="alert-heading">Important Notes:</h6>
                    <ul class="mb-0 small">
                        <li>If a user already has a location, this will update their existing record</li>
                        <li>Location information is used for trainer-client matching</li>
                        <li>Ensure accuracy for location-based features</li>
                    </ul>
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

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Form validation
    $('#createLocationForm').on('submit', function(e) {
        var isValid = true;
        
        // Check if user is selected
        if (!$('#user_id').val()) {
            $('#user_id').addClass('is-invalid');
            isValid = false;
        } else {
            $('#user_id').removeClass('is-invalid');
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
    
    // Remove validation errors on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
    });
    
    // User selection change handler
    $('#user_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var role = selectedOption.data('role');
        
        if (role) {
            // You can add role-specific logic here if needed
            console.log('Selected user role:', role);
        }
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
});
</script>
@endsection