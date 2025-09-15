@extends('layouts.master')

@section('styles')
<style>
.modal-backdrop {
    z-index: 1040;
}
.modal {
    z-index: 1050;
}
.profile-image-preview {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 50%;
    border: 3px solid #e9ecef;
}
.image-upload-container {
    position: relative;
    display: inline-block;
}
.image-upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    cursor: pointer;
}
.image-upload-container:hover .image-upload-overlay {
    opacity: 1;
}
</style>
@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">My Profile</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainer.dashboard') }}">Trainer</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Alert Messages -->
<div id="alert-container"></div>

<!-- Profile Overview -->
<div class="row">
    <div class="col-xl-4">
        <div class="card custom-card">
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($user->profile_image)
                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}" class="avatar avatar-xxl avatar-rounded">
                    @else
                        <span class="avatar avatar-xxl avatar-rounded bg-primary-transparent">
                            <i class="ri-user-line fs-24"></i>
                        </span>
                    @endif
                </div>
                <h5 class="fw-semibold mb-1">{{ $user->name }}</h5>
                <p class="text-muted mb-2">{{ $user->designation ?: 'Fitness Trainer' }}</p>
                <p class="text-muted fs-12 mb-3">
                    <i class="ri-mail-line me-1"></i>{{ $user->email }}
                </p>
                <p class="text-muted fs-12 mb-3">
                    <i class="ri-phone-line me-1"></i>{{ $user->phone }}
                </p>
                
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <button class="btn btn-primary" onclick="openProfileModal()">
                        <i class="ri-edit-line me-1"></i>Edit Profile
                    </button>
                    <button class="btn btn-info" onclick="openImageModal()">
                        <i class="ri-image-line me-1"></i>Change Photo
                    </button>
                    <a href="{{ route('trainers.show', $user->id) }}" class="btn btn-outline-primary" target="_blank">
                        <i class="ri-eye-line me-1"></i>View Public
                    </a>
                </div>
                
                <div class="row text-center">
                    <div class="col-4">
                        <div class="border-end">
                            <h6 class="fw-semibold mb-0">{{ $user->certifications()->count() }}</h6>
                            <span class="text-muted fs-12">Certifications</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border-end">
                            <h6 class="fw-semibold mb-0">{{ $user->receivedTestimonials()->count() }}</h6>
                            <span class="text-muted fs-12">Reviews</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <h6 class="fw-semibold mb-0">{{ number_format($user->receivedTestimonials()->avg('rate') ?: 0, 1) }}</h6>
                        <span class="text-muted fs-12">Rating</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-8">
        <!-- Profile Information -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    Profile Information
                </div>
                <div class="ms-auto">
                    <a href="{{ route('trainers.edit', $user->id) }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-edit-line me-1"></i>Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Full Name</label>
                            <p class="text-muted mb-0">{{ $user->name ?: 'Not provided' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Designation</label>
                            <p class="text-muted mb-0">{{ $user->designation ?: 'Not provided' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email Address</label>
                            <p class="text-muted mb-0">{{ $user->email }}</p>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <p class="text-muted mb-0">{{ $user->phone ?: 'Not provided' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Experience</label>
                            <p class="text-muted mb-0">{{ $user->experience ?: 'Not provided' }}</p>
                        </div>
                    </div>
                    <div class="col-xl-6 col-lg-6 col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Member Since</label>
                            <p class="text-muted mb-0">{{ $user->created_at->format('M d, Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- About Section -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    About Me
                </div>
            </div>
            <div class="card-body">
                @if($user->about)
                    <p class="text-muted mb-0">{{ $user->about }}</p>
                @else
                    <div class="text-center py-3">
                        <i class="ri-information-line fs-24 text-muted mb-2"></i>
                        <p class="text-muted mb-2">No information provided yet.</p>
                        <a href="{{ route('trainers.edit', $user->id) }}" class="btn btn-sm btn-primary">
                            <i class="ri-edit-line me-1"></i>Add About Information
                        </a>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Training Philosophy -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Training Philosophy
                </div>
            </div>
            <div class="card-body">
                @if($user->training_philosophy)
                    <p class="text-muted mb-0">{{ $user->training_philosophy }}</p>
                @else
                    <div class="text-center py-3">
                        <i class="ri-lightbulb-line fs-24 text-muted mb-2"></i>
                        <p class="text-muted mb-2">No training philosophy provided yet.</p>
                        <a href="{{ route('trainers.edit', $user->id) }}" class="btn btn-sm btn-primary">
                            <i class="ri-edit-line me-1"></i>Add Training Philosophy
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Certifications
                </div>
                <div class="ms-auto">
                    <a href="{{ route('trainer.certifications') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($user->certifications()->latest()->take(3)->get() as $certification)
                <div class="d-flex align-items-center gap-3 border-bottom pb-2 mb-2">
                    <span class="avatar avatar-sm avatar-rounded bg-success-transparent">
                        <i class="ri-award-line"></i>
                    </span>
                    <div class="flex-fill">
                        <h6 class="fw-semibold mb-0">{{ $certification->certificate_name }}</h6>
                        <span class="text-muted fs-12">{{ $certification->created_at->format('M d, Y') }}</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-3">
                    <i class="ri-award-line fs-24 text-muted mb-2"></i>
                    <p class="text-muted mb-2">No certifications added yet.</p>
                    <a href="{{ route('trainers.certifications.create', $user->id) }}" class="btn btn-sm btn-primary">
                        <i class="ri-add-line me-1"></i>Add Certification
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Reviews
                </div>
                <div class="ms-auto">
                    <a href="{{ route('trainer.testimonials') }}" class="btn btn-sm btn-outline-primary">
                        View All
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($user->receivedTestimonials()->latest()->take(3)->get() as $testimonial)
                <div class="d-flex align-items-start gap-3 border-bottom pb-2 mb-2">
                    <span class="avatar avatar-sm avatar-rounded">
                        <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="{{ $testimonial->name }}">
                    </span>
                    <div class="flex-fill">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <h6 class="fw-semibold mb-0">{{ $testimonial->name }}</h6>
                            <div class="d-flex align-items-center gap-1">
                                @for($i = 1; $i <= $testimonial->rate; $i++)
                                    <i class="ri-star-fill text-warning fs-10"></i>
                                @endfor
                            </div>
                        </div>
                        <p class="text-muted fs-12 mb-0">{{ Str::limit($testimonial->comments, 60) }}</p>
                    </div>
                </div>
                @empty
                <div class="text-center py-3">
                    <i class="ri-chat-3-line fs-24 text-muted mb-2"></i>
                    <p class="text-muted mb-0">No reviews received yet.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Profile Edit Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="profileForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ $user->name }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Designation</label>
                            <input type="text" class="form-control" id="designation" name="designation" value="{{ $user->designation }}" placeholder="e.g., Certified Personal Trainer">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ $user->email }}" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="{{ $user->phone }}" placeholder="+92 300 1234567">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Experience</label>
                            <input type="text" class="form-control" id="experience" name="experience" value="{{ $user->experience }}" placeholder="e.g., 5+ years in fitness training">
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">About Me</label>
                            <textarea class="form-control" id="about" name="about" rows="4" placeholder="Tell clients about yourself, your background, and what makes you unique...">{{ $user->about }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Training Philosophy</label>
                            <textarea class="form-control" id="training_philosophy" name="training_philosophy" rows="4" placeholder="Describe your approach to fitness training and client success...">{{ $user->training_philosophy }}</textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="profileSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2" id="profileSpinner" style="display: none;"></span>
                        Update Profile
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Profile Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Change Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="imageForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body text-center">
                    <div class="mb-3">
                        <div class="image-upload-container">
                            <img id="imagePreview" src="{{ $user->profile_image ? asset('storage/' . $user->profile_image) : asset('build/assets/images/faces/1.jpg') }}" alt="Profile Preview" class="profile-image-preview">
                            <div class="image-upload-overlay">
                                <i class="ri-camera-line text-white fs-24"></i>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                        <small class="text-muted">Accepted formats: JPG, JPEG, PNG (Max: 2MB)</small>
                        <div class="invalid-feedback"></div>
                    </div>
                    @if($user->profile_image)
                    <div class="mb-3">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeProfileImage()">
                            <i class="ri-delete-bin-line me-1"></i>Remove Current Photo
                        </button>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="imageSubmitBtn">
                        <span class="spinner-border spinner-border-sm me-2" id="imageSpinner" style="display: none;"></span>
                        Upload Photo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// CSRF Token Setup
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Open Profile Edit Modal
function openProfileModal() {
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $('#profileModal').modal('show');
}

// Open Image Upload Modal
function openImageModal() {
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    $('#imageModal').modal('show');
}

// Image Preview Functionality
$('#profile_image').change(function() {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $('#imagePreview').attr('src', e.target.result);
        };
        reader.readAsDataURL(file);
    }
});

// Profile Form Submit
$('#profileForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#profileSubmitBtn');
    const spinner = $('#profileSpinner');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    submitBtn.prop('disabled', true);
    spinner.show();
    
    formData.append('_method', 'PUT');
    
    $.ajax({
        url: `/api/trainer/profile`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#profileModal').modal('hide');
                
                // Update profile information on page
                updateProfileInfo(response.data);
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            
            if (response.errors) {
                // Display validation errors
                Object.keys(response.errors).forEach(function(field) {
                    const input = $(`#${field}`);
                    const feedback = input.siblings('.invalid-feedback');
                    
                    input.addClass('is-invalid');
                    feedback.text(response.errors[field][0]);
                });
            } else {
                showAlert(response?.message || 'Error updating profile', 'danger');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Image Form Submit
$('#imageForm').submit(function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = $('#imageSubmitBtn');
    const spinner = $('#imageSpinner');
    
    // Clear previous errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').text('');
    
    submitBtn.prop('disabled', true);
    spinner.show();
    
    $.ajax({
        url: `/api/trainer/profile/image`,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                showAlert(response.message, 'success');
                $('#imageModal').modal('hide');
                
                // Update profile image on page
                if (response.image_url) {
                    $('.avatar img, .profile-image-preview').attr('src', response.image_url);
                }
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            
            if (response.errors) {
                Object.keys(response.errors).forEach(function(field) {
                    const input = $(`#${field}`);
                    const feedback = input.siblings('.invalid-feedback');
                    
                    input.addClass('is-invalid');
                    feedback.text(response.errors[field][0]);
                });
            } else {
                showAlert(response?.message || 'Error uploading image', 'danger');
            }
        },
        complete: function() {
            submitBtn.prop('disabled', false);
            spinner.hide();
        }
    });
});

// Remove Profile Image
function removeProfileImage() {
    if (confirm('Are you sure you want to remove your profile photo?')) {
        $.ajax({
            url: `/api/trainer/profile/image`,
            method: 'DELETE',
            success: function(response) {
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#imageModal').modal('hide');
                    
                    // Update to default image
                    const defaultImage = '/build/assets/images/faces/1.jpg';
                    $('.avatar img, .profile-image-preview').attr('src', defaultImage);
                    $('#imagePreview').attr('src', defaultImage);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showAlert(response?.message || 'Error removing image', 'danger');
            }
        });
    }
}

// Update Profile Information on Page
function updateProfileInfo(user) {
    // Update profile card information
    $('.card-body h5').first().text(user.name);
    $('.card-body p').eq(0).text(user.designation || 'Fitness Trainer');
    $('.card-body p').eq(1).html(`<i class="ri-mail-line me-1"></i>${user.email}`);
    $('.card-body p').eq(2).html(`<i class="ri-phone-line me-1"></i>${user.phone || 'Not provided'}`);
    
    // Update profile information section
    $('p:contains("Full Name")').next('p').text(user.name || 'Not provided');
    $('p:contains("Designation")').next('p').text(user.designation || 'Not provided');
    $('p:contains("Email Address")').next('p').text(user.email);
    $('p:contains("Phone Number")').next('p').text(user.phone || 'Not provided');
    $('p:contains("Experience")').next('p').text(user.experience || 'Not provided');
    
    // Update about and philosophy sections
    if (user.about) {
        $('.card:has(h5:contains("About Me")) .card-body').html(`<p class="text-muted mb-0">${user.about}</p>`);
    }
    
    if (user.training_philosophy) {
        $('.card:has(h5:contains("Training Philosophy")) .card-body').html(`<p class="text-muted mb-0">${user.training_philosophy}</p>`);
    }
}

// Show Alert
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="ri-${type === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#alert-container').html(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
    
    // Scroll to top to show alert
    $('html, body').animate({ scrollTop: 0 }, 300);
}
</script>
@endsection