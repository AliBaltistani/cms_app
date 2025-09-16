@extends('layouts.master')

@section('styles')
<style>
.profile-image-preview {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
}
.image-upload-container {
    position: relative;
    display: inline-block;
}
.image-upload-overlay {
    position: absolute;
    bottom: 0;
    right: 0;
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.character-counter {
    font-size: 0.75rem;
    color: #6c757d;
    text-align: right;
    margin-top: 0.25rem;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Show/hide trainer fields based on role selection
    $('#role').change(function() {
        if ($(this).val() === 'trainer') {
            $('#trainerFields').slideDown();
            $('#nonTrainerMessage').slideUp();
            $('.trainer-required').show();
            $('#designation, #experience, #about').attr('required', true);
        } else {
            $('#trainerFields').slideUp();
            $('#nonTrainerMessage').slideDown();
            $('.trainer-required').hide();
            $('#designation, #experience, #about, #training_philosophy').attr('required', false);
        }
    });
    
    // Initialize trainer fields visibility
    if ($('#role').val() === 'trainer') {
        $('#trainerFields').show();
        $('#nonTrainerMessage').hide();
        $('.trainer-required').show();
    } else {
        $('#trainerFields').hide();
        $('#nonTrainerMessage').show();
        $('.trainer-required').hide();
    }
    
    // Character counters for textareas
    $('#about').on('input', function() {
        const length = $(this).val().length;
        $('#aboutCounter').text(length);
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });

    $('#training_philosophy').on('input', function() {
        const length = $(this).val().length;
        $('#philosophyCounter').text(length);
        if (length > 1000) {
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    
    // Profile image preview
    $('#profile_image').change(function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).removeClass('d-none');
                $('#avatarPlaceholder').addClass('d-none');
                $('#deleteImageBtn').removeClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Delete profile image
    $('#deleteImageBtn').click(function() {
        $('#profile_image').val('');
        $('#imagePreview').addClass('d-none');
        $('#avatarPlaceholder').removeClass('d-none');
        $(this).addClass('d-none');
    });
    
    // Initialize character counters
    $('#aboutCounter').text($('#about').val().length);
    $('#philosophyCounter').text($('#training_philosophy').val().length);
});
</script>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Edit User Profile</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit User</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <a href="{{ route('admin.users.index') }}" class="btn btn-primary-light btn-wave waves-effect waves-light">
            <i class="ri-arrow-left-line align-middle me-1"></i>Back to Users
        </a>
    </div>
</div>

<!-- Display Success/Error Messages -->
@if (session('success') || session('error'))
    <div class="row">
        <div class="col-xl-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>Success!</strong> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Error!</strong> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    </div>
@endif

<form method="POST" action="{{ route('admin.users.update', $user->id) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    
    <!-- Start::row-1 -->
    <div class="row">
        <!-- Account Information Card -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Account Information
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="row gy-3">
                        <!-- Profile Image Section -->
                        <div class="col-xl-12">
                            <div class="d-flex align-items-start flex-wrap gap-3">
                                <div class="image-upload-container">
                                    @if($user->profile_image)
                                        <span class="avatar avatar-xxl">
                                            <img src="{{ asset('storage/' . $user->profile_image) }}" alt="Profile Image" id="imagePreview">
                                        </span>
                                        <div class="avatar avatar-xxl avatar-rounded bg-primary-transparent d-none" id="avatarPlaceholder">
                                            <i class="ri-user-line fs-1"></i>
                                        </div>
                                    @else
                                        <img src="" alt="Profile Image" class="profile-image-preview d-none" id="imagePreview">
                                        <div class="avatar avatar-xxl avatar-rounded bg-primary-transparent" id="avatarPlaceholder">
                                            <i class="ri-user-line fs-1"></i>
                                        </div>
                                    @endif
                                    <div class="image-upload-overlay" onclick="document.getElementById('profile_image').click()">
                                        <i class="ri-camera-line text-white fs-6"></i>
                                    </div>
                                </div>
                                <div>
                                    <span class="fw-medium d-block mb-2">Profile Picture</span>
                                    <div class="btn-list mb-1">
                                        <button type="button" class="btn btn-sm btn-primary btn-wave waves-effect waves-light" onclick="document.getElementById('profile_image').click()">
                                            <i class="ri-upload-2-line me-1"></i>Change Image
                                        </button>
                                        <button type="button" class="btn btn-sm btn-light btn-wave waves-effect waves-light {{ !$user->profile_image ? 'd-none' : '' }}" id="deleteImageBtn">
                                            <i class="ri-delete-bin-line me-1"></i>Remove
                                        </button>
                                    </div>
                                    <span class="d-block fs-12 text-muted">Use JPEG, PNG, or GIF. Best size: 200x200 pixels. Keep it under 5MB</span>
                                    <input type="file" class="d-none" id="profile_image" name="profile_image" accept="image/*">
                                    @error('profile_image')
                                        <div class="text-danger fs-12 mt-1">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Basic Information Fields -->
                        <div class="col-xl-6">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" placeholder="Enter Full Name" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Enter Email Address" required>
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Enter Phone Number">
                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="role" class="form-label">User Role <span class="text-danger">*</span></label>
                            <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <option value="client" {{ old('role', $user->role) == 'client' ? 'selected' : '' }}>Client</option>
                                <option value="trainer" {{ old('role', $user->role) == 'trainer' ? 'selected' : '' }}>Trainer</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin</option>
                            </select>
                            @error('role')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Trainer Information Card -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Trainer Information
                    </div>
                </div>
                <div class="card-body">
                    <div id="trainerFields" style="display: none;">
                        <div class="row gy-3">
                            <div class="col-xl-6">
                                <label for="designation" class="form-label">Designation <span class="text-danger trainer-required">*</span></label>
                                <input type="text" class="form-control @error('designation') is-invalid @enderror" id="designation" name="designation" value="{{ old('designation', $user->designation) }}" placeholder="e.g., Certified Personal Trainer">
                                @error('designation')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">Professional title or certification</small>
                            </div>
                            <div class="col-xl-6">
                                <label for="experience" class="form-label">Experience (Years) <span class="text-danger trainer-required">*</span></label>
                                <input type="number" class="form-control @error('experience') is-invalid @enderror" id="experience" name="experience" value="{{ old('experience', $user->experience) }}" placeholder="Enter years of experience" min="0" max="50">
                                @error('experience')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-xl-12">
                                <label for="about" class="form-label">About <span class="text-danger trainer-required">*</span></label>
                                <textarea class="form-control @error('about') is-invalid @enderror" id="about" name="about" rows="4" maxlength="1000" placeholder="Tell us about yourself, your background, and expertise...">{{ old('about', $user->about) }}</textarea>
                                @error('about')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="character-counter">
                                    <span id="aboutCounter">0</span>/1000 characters
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <label for="training_philosophy" class="form-label">Training Philosophy</label>
                                <textarea class="form-control @error('training_philosophy') is-invalid @enderror" id="training_philosophy" name="training_philosophy" rows="4" maxlength="1000" placeholder="Describe your training approach and philosophy...">{{ old('training_philosophy', $user->training_philosophy) }}</textarea>
                                @error('training_philosophy')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="character-counter">
                                    <span id="philosophyCounter">0</span>/1000 characters
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="nonTrainerMessage" style="display: block;">
                        <div class="text-center p-4">
                            <span class="avatar avatar-xl avatar-rounded bg-info-transparent">
                                <i class="ri-information-line fs-1"></i>
                            </span>
                            <h5 class="mt-3">Trainer Information Not Required</h5>
                            <p class="text-muted">This section is only applicable for users with the 'Trainer' role. Select 'Trainer' role above to enable these fields.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Change Password Card -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Change Password
                    </div>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-xl-6">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Enter New Password">
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <small class="text-muted">Leave blank to keep current password. Minimum 8 characters.</small>
                        </div>
                        <div class="col-xl-6">
                            <label for="password_confirmation" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="Confirm New Password">
                            <small class="text-muted">Re-enter the new password to confirm.</small>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn btn-light btn-wave waves-effect waves-light">
                            <i class="ri-close-line me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary btn-wave waves-effect waves-light">
                            <i class="ri-save-line me-1"></i>Update User
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Account Status Card -->
        <div class="col-xl-12">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Account Status
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-sm-flex d-block align-items-top justify-content-between">
                        <div class="w-50">
                            <p class="fs-14 mb-1 fw-medium">Account Status</p>
                            <p class="fs-12 mb-0 text-muted">Control whether this user account is active or inactive. Inactive users cannot log in to the system.</p>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="status" name="status" value="1" {{ old('status', $user->status) == 1 ? 'checked' : '' }}>
                            <label class="form-check-label" for="status">
                                {{ old('status', $user->status) == 1 ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-info btn-wave waves-effect waves-light">
                            <i class="ri-eye-line me-1"></i>View Profile
                        </a>
                        @if($user->role !== 'admin' || auth()->user()->id !== $user->id)
                        <button type="button" class="btn btn-danger btn-wave waves-effect waves-light" onclick="confirmDelete()">
                            <i class="ri-delete-bin-line me-1"></i>Delete Account
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--End::row-1 -->
</form>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this user account? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="ri-warning-line me-2"></i>
                    <strong>Warning:</strong> All data associated with this user will be permanently deleted.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete() {
    $('#deleteModal').modal('show');
}
</script>

@endsection