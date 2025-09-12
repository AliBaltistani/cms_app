@extends('layouts.master')

@section('styles')
<style>
.profile-edit-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.profile-image-upload {
    position: relative;
    display: inline-block;
}
.current-avatar {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #e9ecef;
}
.upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
    cursor: pointer;
}
.profile-image-upload:hover .upload-overlay {
    opacity: 1;
}
.upload-text {
    color: white;
    font-size: 0.9rem;
    text-align: center;
}
.form-floating {
    margin-bottom: 1rem;
}
.image-preview {
    max-width: 200px;
    max-height: 200px;
    border-radius: 10px;
    margin-top: 10px;
}
.delete-image-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
}
</style>
@endsection

@section('content')

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit Profile</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Edit Profile</h1>
    </div>
    <div class="btn-list">
        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back to Profile
        </a>
    </div>
</div>
<!-- Page Header Close -->

<!-- Display Success Messages -->
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="ri-check-circle-line me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Display Error Messages -->
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-10">
        <div class="profile-edit-card">
            <div class="text-center mb-4">
                <h4 class="fw-semibold mb-2"><i class="ri-user-settings-line me-2"></i>Edit Your Profile</h4>
                <p class="text-muted">Update your personal information and profile picture</p>
            </div>

            <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" id="profileForm">
                @csrf

                <!-- Profile Image Section -->
                <div class="text-center mb-4">
                    <div class="profile-image-upload position-relative d-inline-block">
                        @if($user->profile_image)
                            <img src="{{ asset('storage/' . $user->profile_image) }}" alt="Current Profile" class="current-avatar" id="currentImage">
                            <!-- Delete Image Button -->
                            <form method="POST" action="{{ route('profile.delete-image') }}" class="d-inline position-absolute" style="top: 0; right: 0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="delete-image-btn" onclick="return confirm('Are you sure you want to delete your profile image?')" title="Delete Image">
                                    <i class="ri-close-line"></i>
                                </button>
                            </form>
                        @else
                            <div class="current-avatar d-flex align-items-center justify-content-center" style="background-color: #f8f9fa; border: 2px dashed #dee2e6;" id="currentImage">
                                <i class="ri-user-line" style="font-size: 3rem; color: #6c757d;"></i>
                            </div>
                        @endif
                        
                        <div class="upload-overlay" onclick="document.getElementById('profileImage').click()">
                            <div class="upload-text">
                                <i class="ri-camera-line d-block mb-1" style="font-size: 1.5rem;"></i>
                                Change Photo
                            </div>
                        </div>
                    </div>
                    
                    <input type="file" id="profileImage" name="profile_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                    
                    <div class="mt-2">
                        <small class="text-muted">Click on the image to change your profile picture</small><br>
                        <small class="text-muted">Supported formats: JPEG, PNG, JPG, GIF (Max: 2MB)</small>
                    </div>
                    
                    <!-- Image Preview -->
                    <div id="imagePreview" style="display: none;" class="mt-3">
                        <img id="previewImg" class="image-preview">
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removePreview()">Remove</button>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" placeholder="Full Name" value="{{ old('name', $user->name) }}" required>
                            <label for="name"><i class="ri-user-line me-2"></i>Full Name</label>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="Email Address" value="{{ old('email', $user->email) }}" required>
                            <label for="email"><i class="ri-mail-line me-2"></i>Email Address</label>
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" placeholder="Phone Number" value="{{ old('phone', $user->phone) }}" required>
                            <label for="phone"><i class="ri-phone-line me-2"></i>Phone Number</label>
                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <select class="form-select" id="role" name="role" disabled>
                                <option value="{{ $user->role }}" selected>{{ ucfirst($user->role) }}</option>
                            </select>
                            <label for="role"><i class="ri-shield-user-line me-2"></i>Role (Cannot be changed)</label>
                            <div class="form-text">
                                <small class="text-muted">Contact administrator to change your role</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Information -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}" readonly>
                            <label><i class="ri-calendar-line me-2"></i>Member Since</label>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}" readonly>
                            <label><i class="ri-verified-badge-line me-2"></i>Email Status</label>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-close-line me-2"></i>Cancel
                    </a>
                    
                    <div>
                        <button type="reset" class="btn btn-outline-warning me-2">
                            <i class="ri-refresh-line me-2"></i>Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-2"></i>Update Profile
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Image Preview Function
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Remove Preview Function
function removePreview() {
    document.getElementById('profileImage').value = '';
    document.getElementById('imagePreview').style.display = 'none';
}

// Form Validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('profileForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const phoneInput = document.getElementById('phone');
    
    // Real-time validation
    nameInput.addEventListener('input', function() {
        validateName(this);
    });
    
    emailInput.addEventListener('input', function() {
        validateEmail(this);
    });
    
    phoneInput.addEventListener('input', function() {
        validatePhone(this);
    });
    
    // Form submission validation
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        if (!validateName(nameInput)) isValid = false;
        if (!validateEmail(emailInput)) isValid = false;
        if (!validatePhone(phoneInput)) isValid = false;
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fix the validation errors before submitting.');
        }
    });
    
    function validateName(input) {
        const value = input.value.trim();
        if (value.length < 2) {
            setFieldError(input, 'Name must be at least 2 characters long.');
            return false;
        }
        clearFieldError(input);
        return true;
    }
    
    function validateEmail(input) {
        const value = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            setFieldError(input, 'Please enter a valid email address.');
            return false;
        }
        clearFieldError(input);
        return true;
    }
    
    function validatePhone(input) {
        const value = input.value.trim();
        if (value.length < 10) {
            setFieldError(input, 'Phone number must be at least 10 digits.');
            return false;
        }
        clearFieldError(input);
        return true;
    }
    
    function setFieldError(input, message) {
        input.classList.add('is-invalid');
        let feedback = input.parentNode.querySelector('.invalid-feedback');
        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback';
            input.parentNode.appendChild(feedback);
        }
        feedback.textContent = message;
    }
    
    function clearFieldError(input) {
        input.classList.remove('is-invalid');
        const feedback = input.parentNode.querySelector('.invalid-feedback');
        if (feedback) {
            feedback.remove();
        }
    }
    
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
@endsection