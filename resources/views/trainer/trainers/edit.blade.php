@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Edit Trainer Profile</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainers.index') }}">Trainers</a></li>
            <li class="breadcrumb-item"><a href="{{ route('trainers.show', $trainer->id) }}">{{ $trainer->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

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

<!-- Start::row-1 -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Trainer Profile Settings
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('trainers.update', $trainer->id) }}" enctype="multipart/form-data" id="trainerProfileForm">
                    @csrf
                    @method('PUT')
                    <div class="row gy-3">
                        <div class="col-xl-12">
                            <div class="d-flex align-items-start flex-wrap gap-3">
                                <div>
                                    <span class="avatar avatar-xxl">
                                        @if($trainer->profile_image)
                                            <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                                        @else
                                            <img src="{{asset('build/assets/images/faces/9.jpg')}}" alt="{{ $trainer->name }}">
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <span class="fw-medium d-block mb-2">Profile Picture</span>
                                    <div class="btn-list mb-1">
                                        <input type="file" id="profileImage" name="profile_image" accept="image/*" style="display: none;">
                                        <button type="button" class="btn btn-sm btn-primary btn-wave" onclick="document.getElementById('profileImage').click()"><i class="ri-upload-2-line me-1"></i>Change Image</button>
                                        @if($trainer->profile_image)
                                            <button type="button" class="btn btn-sm btn-light btn-wave" onclick="deleteProfileImage()" id="deleteImageBtn"><i class="ri-delete-bin-line me-1"></i>Remove</button>
                                        @endif
                                    </div>
                                    <span class="d-block fs-12 text-muted">Use JPEG, PNG, or GIF. Best size: 200x200 pixels. Keep it under 5MB</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-name" class="form-label">Full Name :</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="trainer-name" name="name" value="{{ old('name', $trainer->name) }}" placeholder="Enter Full Name">
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-email" class="form-label">Email :</label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" id="trainer-email" name="email" value="{{ old('email', $trainer->email) }}" placeholder="Enter Email">
                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-phone" class="form-label">Phone No :</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="trainer-phone" name="phone" value="{{ old('phone', $trainer->phone) }}" placeholder="Enter Phone Number">
                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-designation" class="form-label">Designation :</label>
                            <input type="text" class="form-control @error('designation') is-invalid @enderror" id="trainer-designation" name="designation" value="{{ old('designation', $trainer->designation) }}" placeholder="e.g., Senior Fitness Trainer">
                            @error('designation')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-experience" class="form-label">Experience Level :</label>
                            <select class="form-select @error('experience') is-invalid @enderror" id="trainer-experience" name="experience">
                                <option value="">Select Experience Level</option>
                                <option value="less_than_1_year" {{ old('experience', $trainer->experience) === 'less_than_1_year' ? 'selected' : '' }}>Less than 1 year</option>
                                <option value="1_year" {{ old('experience', $trainer->experience) === '1_year' ? 'selected' : '' }}>1 year</option>
                                <option value="2_years" {{ old('experience', $trainer->experience) === '2_years' ? 'selected' : '' }}>2 years</option>
                                <option value="3_years" {{ old('experience', $trainer->experience) === '3_years' ? 'selected' : '' }}>3 years</option>
                                <option value="4_years" {{ old('experience', $trainer->experience) === '4_years' ? 'selected' : '' }}>4 years</option>
                                <option value="5_years" {{ old('experience', $trainer->experience) === '5_years' ? 'selected' : '' }}>5 years</option>
                                <option value="6_years" {{ old('experience', $trainer->experience) === '6_years' ? 'selected' : '' }}>6 years</option>
                                <option value="7_years" {{ old('experience', $trainer->experience) === '7_years' ? 'selected' : '' }}>7 years</option>
                                <option value="8_years" {{ old('experience', $trainer->experience) === '8_years' ? 'selected' : '' }}>8 years</option>
                                <option value="9_years" {{ old('experience', $trainer->experience) === '9_years' ? 'selected' : '' }}>9 years</option>
                                <option value="10_years" {{ old('experience', $trainer->experience) === '10_years' ? 'selected' : '' }}>10 years</option>
                                <option value="more_than_10_years" {{ old('experience', $trainer->experience) === 'more_than_10_years' ? 'selected' : '' }}>More than 10 years</option>
                            </select>
                            @error('experience')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-6">
                            <label for="trainer-role" class="form-label">Role :</label>
                            <input type="text" class="form-control" id="trainer-role" value="{{ ucfirst($trainer->role) }}" readonly>
                            <div class="form-text">
                                <small class="text-muted">Contact administrator to change your role</small>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label for="trainer-about" class="form-label">About :</label>
                            <textarea class="form-control @error('about') is-invalid @enderror" id="trainer-about" name="about" rows="4" placeholder="Tell us about yourself, your specializations, and what makes you unique as a trainer...">{{ old('about', $trainer->about) }}</textarea>
                            @error('about')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Maximum 2000 characters</small>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <label for="trainer-philosophy" class="form-label">Training Philosophy :</label>
                            <textarea class="form-control @error('training_philosophy') is-invalid @enderror" id="trainer-philosophy" name="training_philosophy" rows="4" placeholder="Share your training philosophy, approach, and beliefs about fitness...">{{ old('training_philosophy', $trainer->training_philosophy) }}</textarea>
                            @error('training_philosophy')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Maximum 2000 characters</small>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                        <a href="{{ route('trainers.show', $trainer->id) }}" class="btn btn-light ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

<!-- Hidden form for deleting profile image -->
@if($trainer->profile_image)
<form id="deleteImageForm" method="POST" action="{{ route('trainers.delete-image', $trainer->id) }}" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endif

@endsection

@section('scripts')
<script>
/**
 * Handle profile image preview and update avatar display
 */
document.getElementById('profileImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        console.log('File selected:', file.name);
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please select a valid image file (JPEG, PNG, or GIF)');
            this.value = '';
            return;
        }
        
        // Validate file size (5MB)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            alert('File size must be less than 5MB');
            this.value = '';
            return;
        }
        
        // Preview the image
        const reader = new FileReader();
        reader.onload = function(e) {
            const avatarImg = document.querySelector('.avatar.avatar-xxl img');
            if (avatarImg) {
                avatarImg.src = e.target.result;
            }
        };
        reader.readAsDataURL(file);
    }
});

/**
 * Delete profile image
 */
function deleteProfileImage() {
    if (confirm('Are you sure you want to remove your profile image?')) {
        document.getElementById('deleteImageForm').submit();
    }
}

/**
 * Character count for textareas
 */
document.addEventListener('DOMContentLoaded', function() {
    const aboutTextarea = document.getElementById('trainer-about');
    const philosophyTextarea = document.getElementById('trainer-philosophy');
    
    function updateCharCount(textarea, maxLength) {
        const currentLength = textarea.value.length;
        const formText = textarea.parentNode.querySelector('.form-text small');
        if (formText) {
            formText.textContent = `${currentLength}/${maxLength} characters`;
            if (currentLength > maxLength) {
                formText.classList.add('text-danger');
                formText.classList.remove('text-muted');
            } else {
                formText.classList.remove('text-danger');
                formText.classList.add('text-muted');
            }
        }
    }
    
    if (aboutTextarea) {
        aboutTextarea.addEventListener('input', function() {
            updateCharCount(this, 2000);
        });
        updateCharCount(aboutTextarea, 2000);
    }
    
    if (philosophyTextarea) {
        philosophyTextarea.addEventListener('input', function() {
            updateCharCount(this, 2000);
        });
        updateCharCount(philosophyTextarea, 2000);
    }
});
</script>
@endsection