@extends('layouts.master')

@section('styles')
<!-- Filepond CSS -->
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond/filepond.min.css')}}">
<link rel="stylesheet" href="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.css')}}">
@endsection

@section('scripts')
<!-- Filepond JS -->
<script src="{{asset('build/assets/libs/filepond/filepond.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-preview/filepond-plugin-image-preview.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-exif-orientation/filepond-plugin-image-exif-orientation.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-size/filepond-plugin-file-validate-size.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-encode/filepond-plugin-file-encode.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-edit/filepond-plugin-image-edit.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-file-validate-type/filepond-plugin-file-validate-type.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-crop/filepond-plugin-image-crop.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-resize/filepond-plugin-image-resize.min.js')}}"></script>
<script src="{{asset('build/assets/libs/filepond-plugin-image-transform/filepond-plugin-image-transform.min.js')}}"></script>

<script>
$(document).ready(function() {
    // Register FilePond plugins
    FilePond.registerPlugin(
        FilePondPluginImagePreview,
        FilePondPluginImageExifOrientation,
        FilePondPluginFileValidateSize,
        FilePondPluginFileEncode,
        FilePondPluginImageEdit,
        FilePondPluginFileValidateType,
        FilePondPluginImageCrop,
        FilePondPluginImageResize,
        FilePondPluginImageTransform
    );

    // Initialize FilePond for profile image
    const profileImageInput = document.querySelector('#profile_image');
    if (profileImageInput) {
        const pond = FilePond.create(profileImageInput, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            maxFileSize: '2MB',
            imagePreviewHeight: 120,
            imageCropAspectRatio: '1:1',
            imageResizeTargetWidth: 200,
            imageResizeTargetHeight: 200,
            stylePanelLayout: 'compact',
            styleLoadIndicatorPosition: 'center bottom',
            styleProgressIndicatorPosition: 'right bottom',
            styleButtonRemoveItemPosition: 'left bottom',
            styleButtonProcessItemPosition: 'right bottom',
            labelIdle: 'Drag & Drop profile image or <span class="filepond--label-action">Browse</span>',
        });
        
        @if($trainer->profile_image)
        // Load existing image
        pond.addFile('{{ asset("storage/" . $trainer->profile_image) }}');
        @endif
    }

    // Initialize FilePond for business logo
    const businessLogoInput = document.querySelector('#business_logo');
    if (businessLogoInput) {
        const pondLogo = FilePond.create(businessLogoInput, {
            acceptedFileTypes: ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'],
            maxFileSize: '2MB',
            imagePreviewHeight: 120,
            imageCropAspectRatio: '1:1',
            imageResizeTargetWidth: 300,
            imageResizeTargetHeight: 300,
            stylePanelLayout: 'compact',
            labelIdle: 'Drag & Drop business logo or <span class="filepond--label-action">Browse</span>',
        });
        @if($trainer->business_logo)
        pondLogo.addFile('{{ asset("storage/" . $trainer->business_logo) }}');
        @endif
    }

    // Password confirmation validation
    $('#password_confirmation').on('keyup', function() {
        const password = $('#password').val();
        const confirmation = $(this).val();
        
        if (confirmation && password !== confirmation) {
            $(this).addClass('is-invalid');
            if (!$(this).next('.invalid-feedback').length) {
                $(this).after('<div class="invalid-feedback">Passwords do not match</div>');
            }
        } else {
            $(this).removeClass('is-invalid');
            $(this).next('.invalid-feedback').remove();
        }
    });
});

// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId + '-icon');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.className = 'ri-eye-off-line';
    } else {
        field.type = 'password';
        icon.className = 'ri-eye-line';
    }
}
</script>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Edit Trainer</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.trainers.index') }}">Trainers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-primary btn-wave">
            <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to List
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Main Content -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Edit Trainer Information
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.trainers.update', $trainer->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    
                    <div class="row">
                        <!-- Current Profile Image -->
                        @if($trainer->profile_image)
                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">Current Profile Image</label>
                                <div class="d-flex justify-content-center align-items-center flex-column">
                                    <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="Current Profile" class="avatar avatar-xxl avatar-rounded mb-2">
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Profile Image Upload -->
                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">{{ $trainer->profile_image ? 'Update Profile Image' : 'Profile Image' }}</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                <small class="text-muted d-block mt-2">Upload a profile image (JPEG, PNG, JPG, GIF). Max size: 2MB</small>
                            </div>
                        </div>

                        @if($trainer->business_logo)
                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">Current Business Logo</label>
                                <div class="d-flex justify-content-center align-items-center flex-column">
                                    <img src="{{ asset('storage/' . $trainer->business_logo) }}" alt="Current Business Logo" class="mb-2" style="max-width: 220px; height: auto; object-fit: contain;">
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Business Logo Upload -->
                        <div class="col-xl-12 mb-4">
                            <div class="text-center">
                                <label class="form-label">{{ $trainer->business_logo ? 'Update Business Logo' : 'Business Logo' }}</label>
                                <input type="file" id="business_logo" name="business_logo" accept="image/*">
                                <small class="text-muted d-block mt-2">Upload a business logo (JPEG, PNG, JPG, GIF). Max size: 2MB</small>
                                @error('business_logo')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Basic Information -->
                        <div class="col-xl-6 mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $trainer->name) }}" placeholder="Enter full name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $trainer->email) }}" placeholder="Enter email address" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $trainer->phone) }}" placeholder="Enter phone number">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation', $trainer->designation) }}" placeholder="Enter designation" required>
                            @error('designation')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label for="experience" class="form-label">Experience (Years) <span class="text-danger">*</span></label>
                            <select class="form-select @error('experience') is-invalid @enderror" id="experience" name="experience" required>
                                <option value="">Select Experience Level</option>
                                <option value="less_than_1_year" {{ old('experience', $trainer->experience) == 'less_than_1_year' ? 'selected' : '' }}>Less than 1 year</option>
                                <option value="1_year" {{ old('experience', $trainer->experience) == '1_year' ? 'selected' : '' }}>1 year</option>
                                <option value="2_years" {{ old('experience', $trainer->experience) == '2_years' ? 'selected' : '' }}>2 years</option>
                                <option value="3_years" {{ old('experience', $trainer->experience) == '3_years' ? 'selected' : '' }}>3 years</option>
                                <option value="4_years" {{ old('experience', $trainer->experience) == '4_years' ? 'selected' : '' }}>4 years</option>
                                <option value="5_years" {{ old('experience', $trainer->experience) == '5_years' ? 'selected' : '' }}>5 years</option>
                                <option value="6_years" {{ old('experience', $trainer->experience) == '6_years' ? 'selected' : '' }}>6 years</option>
                                <option value="7_years" {{ old('experience', $trainer->experience) == '7_years' ? 'selected' : '' }}>7 years</option>
                                <option value="8_years" {{ old('experience', $trainer->experience) == '8_years' ? 'selected' : '' }}>8 years</option>
                                <option value="9_years" {{ old('experience', $trainer->experience) == '9_years' ? 'selected' : '' }}>9 years</option>
                                <option value="10_years" {{ old('experience', $trainer->experience) == '10_years' ? 'selected' : '' }}>10 years</option>
                                <option value="more_than_10_years" {{ old('experience', $trainer->experience) == 'more_than_10_years' ? 'selected' : '' }}>More than 10 years</option>
                            </select>
                            @error('experience')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label class="form-label">Account Status</label>
                            <div class="form-control-plaintext">
                                @if($trainer->email_verified_at)
                                    <span class="badge bg-success-transparent">Active</span>
                                @else
                                    <span class="badge bg-danger-transparent">Inactive</span>
                                @endif
                                <small class="text-muted d-block">Created: {{ $trainer->created_at->format('d-m-Y H:i') }}</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-12 mb-3">
                            <label for="about" class="form-label">About <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="4" placeholder="Tell us about yourself" required>{{ old('about', $trainer->about) }}</textarea>
                            @error('about')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-12 mb-3">
                            <label for="training_philosophy" class="form-label">Training Philosophy</label>
                            <textarea class="form-control @error('training_philosophy') is-invalid @enderror" id="training_philosophy" name="training_philosophy" rows="3" placeholder="Your training philosophy (optional)">{{ old('training_philosophy', $trainer->training_philosophy) }}</textarea>
                            @error('training_philosophy')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <!-- Specializations -->
                        <div class="col-xl-12 mb-3">
                            <label for="specializations" class="form-label">Specializations</label>
                            <select class="form-select @error('specializations') is-invalid @enderror" id="specializations" name="specializations">
                                <option value="" disabled>Select Specialization</option>
                                @php
                                    $specializations = \App\Models\Specialization::where('status', 1)->orderBy('name')->get();
                                    $trainerSpecialization = $trainer->specializations->first();
                                @endphp
                                @foreach($specializations as $specialization)
                                    <option value="{{ $specialization->id }}" 
                                        {{ old('specializations', $trainerSpecialization?->id) == $specialization->id ? 'selected' : '' }}>
                                        {{ $specialization->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('specializations')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Location Management Section -->
                        <div class="col-xl-12 mb-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h6 class="card-title mb-0">
                                        <i class="ri-map-pin-line me-2"></i>Location Information
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($trainer->location)
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge bg-success-transparent me-2">
                                                        <i class="ri-map-pin-fill me-1"></i>Location Set
                                                    </span>
                                                    @if($trainer->location->country && $trainer->location->state && $trainer->location->city)
                                                        <span class="badge bg-info-transparent">Complete</span>
                                                    @else
                                                        <span class="badge bg-warning-transparent">Incomplete</span>
                                                    @endif
                                                </div>
                                                <div class="text-muted">
                                                    <strong>Address:</strong>
                                                    @if($trainer->location->address)
                                                        {{ $trainer->location->address }},
                                                    @endif
                                                    @if($trainer->location->city)
                                                        {{ $trainer->location->city }},
                                                    @endif
                                                    @if($trainer->location->state)
                                                        {{ $trainer->location->state }},
                                                    @endif
                                                    @if($trainer->location->country)
                                                        {{ $trainer->location->country }}
                                                    @endif
                                                    @if($trainer->location->zipcode)
                                                        - {{ $trainer->location->zipcode }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('admin.user-locations.show', $trainer->location->id) }}" 
                                                       class="btn btn-sm btn-outline-info" title="View Location">
                                                        <i class="ri-eye-line"></i>
                                                    </a>
                                                    <a href="{{ route('admin.user-locations.edit', $trainer->location->id) }}" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit Location">
                                                        <i class="ri-edit-line"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="text-center py-3">
                                            <div class="mb-3">
                                                <i class="ri-map-pin-line fs-24 text-muted"></i>
                                            </div>
                                            <p class="text-muted mb-3">No location information available for this trainer.</p>
                                            <a href="{{ route('admin.user-locations.create', ['user_id' => $trainer->id]) }}" 
                                               class="btn btn-primary btn-sm">
                                                <i class="ri-add-line me-1"></i>Add Location
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Password Fields -->
                        <div class="col-6 mb-3">
                            <div class="alert alert-info">
                                <i class="ri-information-line me-2"></i>
                                <strong>Password Update:</strong> Leave password fields empty if you don't want to change the password.
                            </div>
                        </div>
                        
                        <div class="col-6 mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter new password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                    <i class="ri-eye-line" id="password-icon"></i>
                                </button>
                            </div>
                            <small class="text-muted">Minimum 8 characters required</small>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6 mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm new password">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirmation')">
                                    <i class="ri-eye-line" id="password_confirmation-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="d-flex justify-content-end gap-2 mt-4">
                                <a href="{{ route('admin.trainers.index') }}" class="btn btn-light btn-wave">
                                    <i class="ri-close-line fw-semibold align-middle me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-wave">
                                    <i class="ri-save-line fw-semibold align-middle me-1"></i> Update Trainer
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection