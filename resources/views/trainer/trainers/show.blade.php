@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Trainer Profile</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('trainers.index') }}">Trainers</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $trainer->name }}</li>
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

<!-- Start:: row-1 -->
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card profile-card">
                    <div class="profile-banner-image">
                        <img src="{{asset('build/assets/images/media/media-3.jpg')}}" class="card-img-top" alt="...">
                    </div>
                    <div class="card-body p-4 pb-0 position-relative">
                        <div class="d-flex align-items-end justify-content-between flex-wrap">
                            <div>
                                <span class="avatar avatar-xxl avatar-rounded bg-info online">
                                    @if($trainer->profile_image)
                                        <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                                    @else
                                        <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $trainer->name }}">
                                    @endif
                                </span>
                                <div class="mt-4 mb-3 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                                    <div>
                                        <h5 class="fw-semibold mb-1">{{ $trainer->name }}</h5>
                                        @if($trainer->designation)
                                            <span class="d-block fw-medium text-muted mb-1">{{ $trainer->designation }}</span>
                                        @endif
                                        <p class="fs-12 mb-0 fw-medium text-muted"> 
                                            <span class="me-3"><i class="ri-mail-line me-1 align-middle"></i>{{ $trainer->email }}</span> 
                                            <span><i class="ri-phone-line me-1 align-middle"></i>{{ $trainer->phone }}</span> 
                                        </p>
                                        @if($trainer->experience)
                                            <span class="badge bg-primary-transparent mt-2">{{ str_replace('_', ' ', ucfirst($trainer->experience)) }} Experience</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="btn-list">
                                    @if(auth()->user()->role === 'trainer' && auth()->id() === $trainer->id)
                                        <a href="{{ route('trainers.edit', $trainer->id) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-edit-line me-1"></i>Edit Profile
                                        </a>
                                        <a href="{{ route('trainers.certifications.create', $trainer->id) }}" class="btn btn-outline-primary btn-sm">
                                            <i class="ri-add-line me-1"></i>Add Certification
                                        </a>
                                    @endif
                                    @if(auth()->user()->role === 'client')
                                        <a href="{{ route('trainers.testimonials.create', $trainer->id) }}" class="btn btn-primary btn-sm">
                                            <i class="ri-chat-3-line me-1"></i>Write Review
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-12">
                <div class="row">
                    <div class="col-xxl-4">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-center gap-4">
                                            <div class="text-center">
                                                <h3 class="fw-semibold mb-1">{{ $trainer->certifications->count() }}</h3>
                                                <span class="d-block text-muted">Certifications</span>
                                            </div>
                                            <div class="vr"></div>
                                            <div class="text-center">
                                                <h3 class="fw-semibold mb-1">{{ $trainer->receivedTestimonials->count() }}</h3>
                                                <span class="d-block text-muted">Reviews</span>
                                            </div>
                                            <div class="vr"></div>
                                            <div class="text-center">
                                                <h3 class="fw-semibold mb-1">
                                                    @if($trainer->receivedTestimonials->count() > 0)
                                                        {{ number_format($trainer->receivedTestimonials->avg('rate'), 1) }}
                                                    @else
                                                        0.0
                                                    @endif
                                                </h3>
                                                <span class="d-block text-muted">Rating</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            About 
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if($trainer->about)
                                            <p class="text-muted">{{ $trainer->about }}</p>
                                        @else
                                            <p class="text-muted">No information provided.</p>
                                        @endif
                                        
                                        <div class="text-muted">
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-mail-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Email : </span> {{ $trainer->email }}
                                            </div>
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-phone-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Phone : </span> {{ $trainer->phone }}
                                            </div>
                                            @if($trainer->experience)
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-time-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Experience : </span> {{ str_replace('_', ' ', ucfirst($trainer->experience)) }}
                                            </div>
                                            @endif
                                            <div class="mb-0 d-flex align-items-center gap-1">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-calendar-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Member Since : </span> {{ \Carbon\Carbon::parse($trainer->created_at)->format('M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @if($trainer->training_philosophy)
                            <div class="col-xl-12">
                                <div class="card custom-card">
                                    <div class="card-header">
                                        <div class="card-title">
                                            Training Philosophy
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted">{{ $trainer->training_philosophy }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-xxl-8">
                        <!-- Certifications Section -->
                        @if($trainer->certifications->count() > 0)
                        <div class="card custom-card mb-4">
                            <div class="card-header">
                                <div class="card-title">
                                    Certifications ({{ $trainer->certifications->count() }})
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row gy-3">
                                    @foreach($trainer->certifications as $certification)
                                    <div class="col-xl-6">
                                        <div class="border rounded p-3">
                                            <div class="d-flex align-items-start justify-content-between">
                                                <div class="flex-fill">
                                                    <h6 class="fw-semibold mb-1">{{ $certification->certificate_name }}</h6>
                                                    <p class="text-muted fs-12 mb-2">Issued: {{ \Carbon\Carbon::parse($certification->created_at)->format('M Y') }}</p>
                                                    @if($certification->doc)
                                                        <a href="{{ asset('storage/' . $certification->doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="ri-download-line me-1"></i>View Certificate
                                                        </a>
                                                    @endif
                                                </div>
                                                <div class="ms-2">
                                                    <span class="avatar avatar-sm avatar-rounded bg-primary-transparent">
                                                        <i class="ri-award-line"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Testimonials Section -->
                        <div class="card custom-card">
                            <div class="card-header">
                                <div class="card-title">
                                    Reviews & Testimonials ({{ $trainer->receivedTestimonials->count() }})
                                </div>
                            </div>
                            <div class="card-body">
                                @forelse($trainer->receivedTestimonials as $testimonial)
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
                                    <p class="text-muted mb-2">{{ $testimonial->comments }}</p>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-sm btn-light" onclick="likeTestimonial({{ $testimonial->id }})">
                                            <i class="ri-thumb-up-line me-1"></i>{{ $testimonial->likes }}
                                        </button>
                                        <button class="btn btn-sm btn-light" onclick="dislikeTestimonial({{ $testimonial->id }})">
                                            <i class="ri-thumb-down-line me-1"></i>{{ $testimonial->dislikes }}
                                        </button>
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4">
                                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                                    <h6 class="fw-semibold mb-2">No Reviews Yet</h6>
                                    <p class="text-muted mb-0">Be the first to write a review for this trainer.</p>
                                    @if(auth()->user()->role === 'client')
                                        <a href="{{ route('trainers.testimonials.create', $trainer->id) }}" class="btn btn-primary btn-sm mt-3">
                                            <i class="ri-chat-3-line me-1"></i>Write Review
                                        </a>
                                    @endif
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')
<script>
/**
 * Like a testimonial
 */
function likeTestimonial(testimonialId) {
    fetch(`/api/testimonials/${testimonialId}/like`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
}

/**
 * Dislike a testimonial
 */
function dislikeTestimonial(testimonialId) {
    fetch(`/api/testimonials/${testimonialId}/dislike`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Authorization': 'Bearer ' + localStorage.getItem('auth_token')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
}
</script>
@endsection