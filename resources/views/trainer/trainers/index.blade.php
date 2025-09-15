@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Trainers</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
            <li class="breadcrumb-item active" aria-current="page">Trainers</li>
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

<!-- Start::row-1 -->
<div class="row">
    @forelse($trainers as $trainer)
    <div class="col-xxl-4 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card trainer-card">
            <div class="card-body p-4">
                <div class="text-center">
                    <span class="avatar avatar-xl avatar-rounded mb-3">
                        @if($trainer->profile_image)
                            <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="{{ $trainer->name }}">
                        @else
                            <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="{{ $trainer->name }}">
                        @endif
                    </span>
                    <h5 class="fw-semibold mb-1">{{ $trainer->name }}</h5>
                    @if($trainer->designation)
                        <p class="text-muted mb-2">{{ $trainer->designation }}</p>
                    @endif
                    @if($trainer->experience)
                        <span class="badge bg-primary-transparent mb-3">{{ str_replace('_', ' ', ucfirst($trainer->experience)) }} Experience</span>
                    @endif
                </div>
                
                @if($trainer->about)
                <div class="mb-3">
                    <p class="text-muted fs-12">{{ Str::limit($trainer->about, 100) }}</p>
                </div>
                @endif
                
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="p-2">
                            <h6 class="fw-semibold mb-1">{{ $trainer->certifications_count ?? $trainer->certifications->count() }}</h6>
                            <span class="text-muted fs-11">Certifications</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h6 class="fw-semibold mb-1">{{ $trainer->received_testimonials_count ?? $trainer->receivedTestimonials->count() }}</h6>
                            <span class="text-muted fs-11">Reviews</span>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2">
                            <h6 class="fw-semibold mb-1">
                                @if($trainer->receivedTestimonials->count() > 0)
                                    {{ number_format($trainer->receivedTestimonials->avg('rate'), 1) }}
                                @else
                                    0.0
                                @endif
                            </h6>
                            <span class="text-muted fs-11">Rating</span>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex gap-2 justify-content-center">
                    <a href="{{ route('trainers.show', $trainer->id) }}" class="btn btn-primary btn-sm">
                        <i class="ri-eye-line me-1"></i>View Profile
                    </a>
                    @if(auth()->user()->role === 'client')
                        <a href="{{ route('trainers.testimonials.create', $trainer->id) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-chat-3-line me-1"></i>Review
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card custom-card">
            <div class="card-body text-center py-5">
                <div class="mb-3">
                    <i class="ri-user-search-line fs-48 text-muted"></i>
                </div>
                <h5 class="fw-semibold mb-2">No Trainers Found</h5>
                <p class="text-muted mb-0">There are currently no trainers available in the system.</p>
            </div>
        </div>
    </div>
    @endforelse
</div>
<!--End::row-1 -->

<!-- Pagination -->
@if($trainers->hasPages())
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-center">
            {{ $trainers->links() }}
        </div>
    </div>
</div>
@endif

@endsection

@section('scripts')

@endsection