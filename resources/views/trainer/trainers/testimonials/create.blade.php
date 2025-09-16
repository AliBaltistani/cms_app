@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Write Review</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainers.index') }}">Trainers</a></li>
            <li class="breadcrumb-item"><a href="{{ route('trainers.show', $trainer->id) }}">{{ $trainer->name }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Write Review</li>
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
<div class="row justify-content-center">
    <div class="col-xl-8">
        <!-- Trainer Info Card -->
        <div class="card custom-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-lg avatar-rounded">
                        @if($trainer->profile_image)
                            <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                        @else
                            <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $trainer->name }}">
                        @endif
                    </span>
                    <div>
                        <h5 class="fw-semibold mb-1">{{ $trainer->name }}</h5>
                        @if($trainer->designation)
                            <p class="text-muted mb-1">{{ $trainer->designation }}</p>
                        @endif
                        @if($trainer->experience)
                            <span class="badge bg-primary-transparent">{{ str_replace('_', ' ', ucfirst($trainer->experience)) }} Experience</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Review Form -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Write Your Review
                </div>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="{{ route('trainers.testimonials.store', $trainer->id) }}" id="testimonialForm">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-xl-6">
                            <label for="client-name" class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="client-name" name="name" value="{{ old('name', auth()->user()->name) }}" placeholder="Enter your name" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-6">
                            <label for="review-date" class="form-label">Training Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" id="review-date" name="date" value="{{ old('date', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                            @error('date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">When did you train with this trainer?</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-12">
                            <label class="form-label">Rating <span class="text-danger">*</span></label>
                            <div class="rating-container mb-2">
                                <div class="star-rating" id="starRating">
                                    <i class="ri-star-line star" data-rating="1"></i>
                                    <i class="ri-star-line star" data-rating="2"></i>
                                    <i class="ri-star-line star" data-rating="3"></i>
                                    <i class="ri-star-line star" data-rating="4"></i>
                                    <i class="ri-star-line star" data-rating="5"></i>
                                </div>
                                <span class="rating-text ms-2" id="ratingText">Click to rate</span>
                            </div>
                            <input type="hidden" name="rate" id="ratingValue" value="{{ old('rate') }}">
                            @error('rate')
                                <div class="text-danger fs-12">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        
                        <div class="col-xl-12">
                            <label for="review-comments" class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('comments') is-invalid @enderror" id="review-comments" name="comments" rows="6" placeholder="Share your experience with this trainer. What did you like? How did they help you achieve your goals?" required>{{ old('comments') }}</textarea>
                            @error('comments')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text">
                                <small class="text-muted">Minimum 10 characters, maximum 1000 characters</small>
                            </div>
                        </div>
                        
                        <div class="col-xl-12">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="fw-semibold mb-2"><i class="ri-information-line me-1"></i>Review Guidelines</h6>
                                <ul class="mb-0 text-muted fs-13">
                                    <li>Be honest and constructive in your feedback</li>
                                    <li>Focus on your personal experience with the trainer</li>
                                    <li>Mention specific aspects like communication, expertise, and results</li>
                                    <li>Keep your review respectful and professional</li>
                                    <li>Avoid sharing personal information in your review</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-send-plane-line me-1"></i>Submit Review
                        </button>
                        <a href="{{ route('trainers.show', $trainer->id) }}" class="btn btn-light ms-2">
                            <i class="ri-arrow-left-line me-1"></i>Back to Profile
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Recent Reviews -->
        @if($trainer->receivedTestimonials->count() > 0)
        <div class="card custom-card mt-4">
            <div class="card-header">
                <div class="card-title">
                    Recent Reviews ({{ $trainer->receivedTestimonials->count() }})
                </div>
            </div>
            <div class="card-body">
                @foreach($trainer->receivedTestimonials->take(3) as $testimonial)
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm avatar-rounded">
                                <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="{{ $testimonial->name }}">
                            </span>
                            <div>
                                <h6 class="fw-semibold mb-0">{{ $testimonial->name }}</h6>
                                <span class="text-muted fs-12">{{ \Carbon\Carbon::parse($testimonial->date)->format('M d, Y') }}</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                @if($i <= $testimonial->rate)
                                    <i class="ri-star-fill text-warning"></i>
                                @else
                                    <i class="ri-star-line text-muted"></i>
                                @endif
                            @endfor
                        </div>
                    </div>
                    <p class="text-muted mb-0">{{ Str::limit($testimonial->comments, 150) }}</p>
                </div>
                @endforeach
                
                @if($trainer->receivedTestimonials->count() > 3)
                <div class="text-center">
                    <a href="{{ route('trainers.show', $trainer->id) }}" class="btn btn-outline-primary btn-sm">
                        View All {{ $trainer->receivedTestimonials->count() }} Reviews
                    </a>
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')
<script>
/**
 * Star rating functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star');
    const ratingValue = document.getElementById('ratingValue');
    const ratingText = document.getElementById('ratingText');
    
    const ratingTexts = {
        1: 'Poor',
        2: 'Fair', 
        3: 'Good',
        4: 'Very Good',
        5: 'Excellent'
    };
    
    // Set initial rating if exists
    const initialRating = ratingValue.value;
    if (initialRating) {
        updateStars(parseInt(initialRating));
    }
    
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            ratingValue.value = rating;
            updateStars(rating);
            ratingText.textContent = ratingTexts[rating];
        });
        
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
        });
    });
    
    document.getElementById('starRating').addEventListener('mouseleave', function() {
        const currentRating = parseInt(ratingValue.value) || 0;
        updateStars(currentRating);
    });
    
    function updateStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('ri-star-line');
                star.classList.add('ri-star-fill', 'text-warning');
            } else {
                star.classList.remove('ri-star-fill', 'text-warning');
                star.classList.add('ri-star-line');
            }
        });
    }
    
    function highlightStars(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('ri-star-line');
                star.classList.add('ri-star-fill', 'text-warning');
            } else {
                star.classList.remove('ri-star-fill', 'text-warning');
                star.classList.add('ri-star-line');
            }
        });
    }
});

/**
 * Character count for comments
 */
document.getElementById('review-comments').addEventListener('input', function() {
    const currentLength = this.value.length;
    const formText = this.parentNode.querySelector('.form-text small');
    if (formText) {
        formText.textContent = `${currentLength}/1000 characters (minimum 10)`;
        if (currentLength < 10) {
            formText.classList.add('text-danger');
            formText.classList.remove('text-muted');
        } else if (currentLength > 1000) {
            formText.classList.add('text-danger');
            formText.classList.remove('text-muted');
        } else {
            formText.classList.remove('text-danger');
            formText.classList.add('text-muted');
        }
    }
});

/**
 * Form validation before submit
 */
document.getElementById('testimonialForm').addEventListener('submit', function(e) {
    const rating = document.getElementById('ratingValue').value;
    const comments = document.getElementById('review-comments').value.trim();
    const name = document.getElementById('client-name').value.trim();
    const date = document.getElementById('review-date').value;
    
    if (!rating || rating < 1 || rating > 5) {
        e.preventDefault();
        alert('Please select a rating from 1 to 5 stars.');
        return false;
    }
    
    if (comments.length < 10) {
        e.preventDefault();
        alert('Please write at least 10 characters in your review.');
        document.getElementById('review-comments').focus();
        return false;
    }
    
    if (comments.length > 1000) {
        e.preventDefault();
        alert('Your review is too long. Please keep it under 1000 characters.');
        document.getElementById('review-comments').focus();
        return false;
    }
    
    if (name.length < 2) {
        e.preventDefault();
        alert('Please enter a valid name.');
        document.getElementById('client-name').focus();
        return false;
    }
    
    if (!date) {
        e.preventDefault();
        alert('Please select the training date.');
        document.getElementById('review-date').focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="ri-loader-2-line me-1 spinner-border spinner-border-sm"></i>Submitting...';
    submitBtn.disabled = true;
    
    // Re-enable button after 10 seconds as fallback
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 10000);
});
</script>

<style>
.star {
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #dee2e6;
}

.star:hover {
    transform: scale(1.1);
}

.star.text-warning {
    color: #ffc107 !important;
}

.rating-container {
    display: flex;
    align-items: center;
}

.star-rating {
    display: flex;
    gap: 2px;
}
</style>
@endsection