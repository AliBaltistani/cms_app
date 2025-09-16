@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">My Reviews</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('client.dashboard') }}">Client</a></li>
            <li class="breadcrumb-item active" aria-current="page">My Reviews</li>
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
@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="ri-error-warning-line me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Reviews List -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    My Reviews ({{ $testimonials->total() }})
                </div>
                <div class="ms-auto">
                    <a href="{{ route('client.dashboard') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ri-arrow-left-line me-1"></i>Back to Dashboard
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($testimonials as $testimonial)
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <span class="avatar avatar-md avatar-rounded">
                                    @if($testimonial->trainer->profile_image)
                                        <img src="{{ asset('storage/' . $testimonial->trainer->profile_image) }}" alt="{{ $testimonial->trainer->name }}">
                                    @else
                                        <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $testimonial->trainer->name }}">
                                    @endif
                                </span>
                                <div>
                                    <h6 class="fw-semibold mb-1">Review for {{ $testimonial->trainer->name }}</h6>
                                    @if($testimonial->trainer->designation)
                                        <span class="text-muted fs-13">{{ $testimonial->trainer->designation }}</span>
                                    @endif
                                    <div class="text-muted fs-12 mt-1">
                                        <i class="ri-calendar-line me-1"></i>{{ $testimonial->created_at->format('M d, Y') }}
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="d-flex align-items-center gap-1 mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $testimonial->rate)
                                            <i class="ri-star-fill text-warning fs-16"></i>
                                        @else
                                            <i class="ri-star-line text-muted fs-16"></i>
                                        @endif
                                    @endfor
                                    <span class="ms-2 fw-semibold">{{ $testimonial->rate }}/5</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-success-transparent">
                                        <i class="ri-thumb-up-line me-1"></i>{{ $testimonial->likes }} Likes
                                    </span>
                                    <span class="badge bg-danger-transparent">
                                        <i class="ri-thumb-down-line me-1"></i>{{ $testimonial->dislikes }} Dislikes
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2">Review Comments:</h6>
                            <p class="text-muted mb-0">{{ $testimonial->comments }}</p>
                        </div>
                        
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="text-muted fs-13">
                                <i class="ri-user-line me-1"></i>Reviewed as: {{ $testimonial->name }}
                            </div>
                            <div class="text-muted fs-13">
                                <i class="ri-time-line me-1"></i>Review Date: {{ \Carbon\Carbon::parse($testimonial->date)->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Reviews Yet</h6>
                    <p class="text-muted mb-3">You haven't written any reviews for trainers yet.</p>
                    <a href="{{ route('client.trainers') }}" class="btn btn-primary">
                        <i class="ri-user-star-line me-1"></i>Find Trainers to Review
                    </a>
                </div>
                @endforelse
                
                <!-- Pagination -->
                @if($testimonials->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $testimonials->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')

@endsection