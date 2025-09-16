@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Client Dashboard</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Client</a></li>
            <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
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

<!-- Welcome Section -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h4 class="fw-semibold mb-1">Welcome back, {{ Auth::user()->name }}!</h4>
                        <p class="text-muted mb-0">Track your fitness journey and connect with amazing trainers.</p>
                    </div>
                    <!-- <div>
                        <a href="{{ route('client.goals.create') }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Set New Goal
                        </a>
                    </div> -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Progress Statistics -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_goals'] }}</h3>
                        <span class="d-block text-muted">Total Goals</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-target-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['completed_goals'] }}</h3>
                        <span class="d-block text-muted">Completed Goals</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-checkbox-circle-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['active_goals'] }}</h3>
                        <span class="d-block text-muted">Active Goals</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-play-circle-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_testimonials'] }}</h3>
                        <span class="d-block text-muted">Reviews Written</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-chat-3-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Recent Goals -->
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Goals ({{ $stats['recent_goals']->count() }})
                </div>
                <!-- <div class="ms-auto">
                    <a href="{{ route('client.goals') }}" class="btn btn-sm btn-outline-primary">
                        View All Goals
                    </a>
                </div> -->
            </div>
            <div class="card-body">
                @forelse($stats['recent_goals'] as $goal)
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <span class="avatar avatar-sm avatar-rounded bg-{{ $goal->status === 'completed' ? 'success' : ($goal->status === 'active' ? 'primary' : 'secondary') }}-transparent">
                            <i class="ri-{{ $goal->status === 'completed' ? 'checkbox-circle' : ($goal->status === 'active' ? 'play-circle' : 'pause-circle') }}-line"></i>
                        </span>
                        <div>
                            <h6 class="fw-semibold mb-1">{{ $goal->title }}</h6>
                            <p class="text-muted fs-12 mb-0">{{ Str::limit($goal->description, 60) }}</p>
                        </div>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-{{ $goal->status === 'completed' ? 'success' : ($goal->status === 'active' ? 'primary' : 'secondary') }}-transparent">
                            {{ ucfirst($goal->status) }}
                        </span>
                        <div class="text-muted fs-11 mt-1">{{ $goal->created_at->format('M d, Y') }}</div>
                    </div>
                </div>
                @empty
                <!-- <div class="text-center py-5">
                    <i class="ri-target-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Goals Yet</h6>
                    <p class="text-muted mb-3">Start your fitness journey by setting your first goal!</p>
                    <a href="{{ route('client.goals.create') }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i>Create Your First Goal
                    </a>
                </div> -->
                @endforelse
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
                <div class="d-grid gap-3">
                    <!-- <a href="{{ route('client.goals.create') }}" class="btn btn-primary">
                        <i class="ri-target-line me-2"></i>Set New Goal
                    </a> -->
                    <!-- <a href="{{ route('client.trainers') }}" class="btn btn-outline-success">
                        <i class="ri-user-star-line me-2"></i>Find Trainers
                    </a> -->
                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#reviewTrainerModal">
                        <i class="ri-star-line me-2"></i>Review Trainer
                    </button>
                    <a href="{{ route('client.testimonials') }}" class="btn btn-outline-info">
                        <i class="ri-chat-3-line me-2"></i>My Reviews
                    </a>
                    <a href="{{ route('client.profile') }}" class="btn btn-outline-secondary">
                        <i class="ri-user-settings-line me-2"></i>Profile Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recommended Trainers & Recent Testimonials -->
<div class="row">
    <!-- Recommended Trainers -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recommended Trainers
                </div>
                <div class="ms-auto">
                    <a href="{{ route('client.trainers') }}" class="btn btn-sm btn-outline-primary">
                        View All Trainers
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($stats['recommended_trainers'] as $trainer)
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm avatar-rounded">
                            @if($trainer->profile_image)
                                <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                            @else
                                <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $trainer->name }}">
                            @endif
                        </span>
                        <div>
                            <h6 class="fw-semibold mb-0">{{ $trainer->name }}</h6>
                            @if($trainer->designation)
                                <span class="text-muted fs-12">{{ $trainer->designation }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="d-flex align-items-center gap-1 mb-1">
                            <i class="ri-star-fill text-warning fs-12"></i>
                            <span class="fs-12">{{ $trainer->receivedTestimonials->avg('rate') ? number_format($trainer->receivedTestimonials->avg('rate'), 1) : '0.0' }}</span>
                        </div>
                        <span class="text-muted fs-11">{{ $trainer->received_testimonials_count }} reviews</span>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="ri-user-star-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Trainers Available</h6>
                    <p class="text-muted mb-0">Check back later for available trainers.</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Recent Testimonials -->
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    My Recent Reviews
                </div>
                <div class="ms-auto">
                    <a href="{{ route('client.testimonials') }}" class="btn btn-sm btn-outline-primary">
                        View All Reviews
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($stats['recent_testimonials'] as $testimonial)
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm avatar-rounded">
                                @if($testimonial->trainer->profile_image)
                                    <img src="{{ asset('storage/' . $testimonial->trainer->profile_image) }}" alt="{{ $testimonial->trainer->name }}">
                                @else
                                    <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $testimonial->trainer->name }}">
                                @endif
                            </span>
                            <div>
                                <h6 class="fw-semibold mb-0">{{ $testimonial->trainer->name }}</h6>
                                <span class="text-muted fs-12">{{ $testimonial->created_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $testimonial->rate)
                                    <i class="ri-star-fill text-warning fs-12"></i>
                                @else
                                    <i class="ri-star-line text-muted fs-12"></i>
                                @endif
                            @endfor
                        </div>
                    </div>
                    <p class="text-muted fs-13 mb-0">{{ Str::limit($testimonial->comments, 80) }}</p>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Reviews Yet</h6>
                    <p class="text-muted mb-3">Share your experience with trainers by writing reviews!</p>
                    <a href="{{ route('client.trainers') }}" class="btn btn-primary btn-sm">
                        <i class="ri-user-star-line me-1"></i>Find Trainers
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

<!-- Review Trainer Modal -->
<div class="modal fade" id="reviewTrainerModal" tabindex="-1" aria-labelledby="reviewTrainerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewTrainerModalLabel">Review Trainer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewTrainerForm">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Select Trainer <span class="text-danger">*</span></label>
                            <select class="form-select" id="trainer_id" name="trainer_id" required>
                                <option value="">Choose a trainer...</option>
                                @if(isset($stats['recommended_trainers']))
                                    @foreach($stats['recommended_trainers'] as $trainer)
                                        <option value="{{ $trainer->id }}">{{ $trainer->name }} @if($trainer->designation) - {{ $trainer->designation }} @endif</option>
                                    @endforeach
                                @endif
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="client_name" name="name" value="{{ Auth::user()->name }}" placeholder="Enter your name" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rating <span class="text-danger">*</span></label>
                            <div class="rating-stars" id="rating-stars">
                                <i class="ri-star-line star" data-rating="1"></i>
                                <i class="ri-star-line star" data-rating="2"></i>
                                <i class="ri-star-line star" data-rating="3"></i>
                                <i class="ri-star-line star" data-rating="4"></i>
                                <i class="ri-star-line star" data-rating="5"></i>
                            </div>
                            <input type="hidden" id="rate" name="rate" required>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Review Comments <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="comments" name="comments" rows="4" placeholder="Share your experience with this trainer..." required></textarea>
                            <div class="invalid-feedback"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Review Date</label>
                            <input type="date" class="form-control" id="review_date" name="date" value="{{ date('Y-m-d') }}">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="submitReviewBtn">
                        <span class="spinner-border spinner-border-sm me-2" id="submitReviewSpinner" style="display: none;"></span>
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
/**
 * CSRF Token Setup for AJAX requests
 */
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

/**
 * Star Rating Functionality
 */
$(document).ready(function() {
    let selectedRating = 0;
    
    // Star hover effect
    $('.star').on('mouseenter', function() {
        const rating = $(this).data('rating');
        highlightStars(rating);
    });
    
    // Reset stars on mouse leave
    $('#rating-stars').on('mouseleave', function() {
        highlightStars(selectedRating);
    });
    
    // Star click selection
    $('.star').on('click', function() {
        selectedRating = $(this).data('rating');
        $('#rate').val(selectedRating);
        highlightStars(selectedRating);
        
        // Remove validation error if rating is selected
        $('#rate').removeClass('is-invalid');
        $('#rating-stars').next('.invalid-feedback').text('');
    });
    
    /**
     * Highlight stars up to the given rating
     * 
     * @param {number} rating - Rating value (1-5)
     */
    function highlightStars(rating) {
        $('.star').each(function() {
            const starRating = $(this).data('rating');
            if (starRating <= rating) {
                $(this).removeClass('ri-star-line').addClass('ri-star-fill text-warning');
            } else {
                $(this).removeClass('ri-star-fill text-warning').addClass('ri-star-line');
            }
        });
    }
    
    /**
     * Reset form when modal is closed
     */
    $('#reviewTrainerModal').on('hidden.bs.modal', function() {
        resetReviewForm();
    });
    
    /**
     * Handle review form submission
     */
    $('#reviewTrainerForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous validation errors
        clearValidationErrors();
        
        // Validate form
        if (!validateReviewForm()) {
            return;
        }
        
        const formData = new FormData(this);
        const submitBtn = $('#submitReviewBtn');
        const spinner = $('#submitReviewSpinner');
        
        // Disable submit button and show spinner
        submitBtn.prop('disabled', true);
        spinner.show();
        
        $.ajax({
            url: '/client/testimonials/store',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert('Review submitted successfully!', 'success');
                    
                    // Close modal and reset form
                    $('#reviewTrainerModal').modal('hide');
                    resetReviewForm();
                    
                    // Optionally reload the page to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert(response.message || 'Error submitting review', 'danger');
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                
                if (xhr.status === 422 && response.errors) {
                    // Handle validation errors
                    displayValidationErrors(response.errors);
                } else {
                    showAlert(response?.message || 'Error submitting review', 'danger');
                }
            },
            complete: function() {
                // Re-enable submit button and hide spinner
                submitBtn.prop('disabled', false);
                spinner.hide();
            }
        });
    });
    
    /**
     * Validate review form
     * 
     * @return {boolean} - True if form is valid
     */
    function validateReviewForm() {
        let isValid = true;
        
        // Validate trainer selection
        const trainerId = $('#trainer_id').val();
        if (!trainerId) {
            showFieldError('trainer_id', 'Please select a trainer');
            isValid = false;
        }
        
        // Validate client name
        const clientName = $('#client_name').val().trim();
        if (!clientName) {
            showFieldError('client_name', 'Please enter your name');
            isValid = false;
        }
        
        // Validate rating
        const rating = $('#rate').val();
        if (!rating || rating < 1 || rating > 5) {
            showFieldError('rate', 'Please select a rating');
            isValid = false;
        }
        
        // Validate comments
        const comments = $('#comments').val().trim();
        if (!comments) {
            showFieldError('comments', 'Please enter your review comments');
            isValid = false;
        } else if (comments.length < 10) {
            showFieldError('comments', 'Review comments must be at least 10 characters long');
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Show field validation error
     * 
     * @param {string} fieldId - Field ID
     * @param {string} message - Error message
     */
    function showFieldError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        field.next('.invalid-feedback').text(message);
        
        // Special handling for rating stars
        if (fieldId === 'rate') {
            $('#rating-stars').next('.invalid-feedback').text(message);
        }
    }
    
    /**
     * Display validation errors from server response
     * 
     * @param {object} errors - Validation errors object
     */
    function displayValidationErrors(errors) {
        Object.keys(errors).forEach(function(field) {
            const fieldElement = $('#' + field);
            if (fieldElement.length) {
                fieldElement.addClass('is-invalid');
                fieldElement.next('.invalid-feedback').text(errors[field][0]);
            }
        });
    }
    
    /**
     * Clear all validation errors
     */
    function clearValidationErrors() {
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
    }
    
    /**
     * Reset review form to initial state
     */
    function resetReviewForm() {
        $('#reviewTrainerForm')[0].reset();
        selectedRating = 0;
        $('#rate').val('');
        highlightStars(0);
        clearValidationErrors();
        $('#client_name').val('{{ Auth::user()->name }}');
        $('#review_date').val('{{ date("Y-m-d") }}');
    }
});

/**
 * Show alert message
 * 
 * @param {string} message - The message to display
 * @param {string} type - The alert type (success, danger, warning, info)
 */
function showAlert(message, type) {
    // Remove existing alerts
    $('.alert').remove();
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
            <i class="ri-${type === 'success' ? 'check-circle' : 'error-warning'}-line me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    // Auto dismiss after 3 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 3000);
}
</script>

<style>
/* Star Rating Styles */
.rating-stars {
    display: flex;
    gap: 5px;
    margin-bottom: 10px;
}

.rating-stars .star {
    font-size: 24px;
    color: #ddd;
    cursor: pointer;
    transition: color 0.2s ease;
}

.rating-stars .star:hover {
    color: #ffc107;
}

.rating-stars .star.ri-star-fill {
    color: #ffc107;
}

/* Modal Styles */
.modal-lg {
    max-width: 600px;
}

/* Form Validation Styles */
.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}
</style>
@endsection