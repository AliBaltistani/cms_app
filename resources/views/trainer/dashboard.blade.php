@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Trainer Dashboard</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Trainer</a></li>
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
                        <p class="text-muted mb-0">Manage your profile, certifications, and client testimonials.</p>
                    </div>
                    <div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('trainers.edit', Auth::id()) }}" class="btn btn-primary">
                                <i class="ri-edit-line me-1"></i>Edit Profile
                            </a>
                            <a href="{{ route('trainers.certifications.create', Auth::id()) }}" class="btn btn-outline-primary">
                                <i class="ri-add-line me-1"></i>Add Certification
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Profile Completion Alert -->
@if($stats['profile_completion'] < 80)
<div class="row">
    <div class="col-xl-12">
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="ri-information-line me-2 fs-18"></i>
                <div class="flex-fill">
                    <strong>Complete Your Profile!</strong> Your profile is {{ $stats['profile_completion'] }}% complete. 
                    <a href="{{ route('trainers.edit', Auth::id()) }}" class="alert-link">Complete your profile</a> to attract more clients.
                </div>
                <div class="ms-auto">
                    <div class="progress" style="width: 100px; height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: {{ $stats['profile_completion'] }}%"></div>
                    </div>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>
@endif

<!-- Performance Statistics -->
<div class="row">
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_certifications'] }}</h3>
                        <span class="d-block text-muted">Certifications</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-award-line fs-18"></i>
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
                        <span class="d-block text-muted">Client Reviews</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-chat-3-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ number_format($stats['average_rating'], 1) }}</h3>
                        <span class="d-block text-muted">Average Rating</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-warning-transparent">
                            <i class="ri-star-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_likes'] }}</h3>
                        <span class="d-block text-muted">Total Likes</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-thumb-up-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    <!-- Recent Testimonials -->
    <div class="col-xl-8">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Client Reviews ({{ $stats['recent_testimonials']->count() }})
                </div>
                <div class="ms-auto">
                    <a href="{{ route('trainer.testimonials') }}" class="btn btn-sm btn-outline-primary">
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
                                <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="{{ $testimonial->name }}">
                            </span>
                            <div>
                                <h6 class="fw-semibold mb-0">{{ $testimonial->name }}</h6>
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
                    <p class="text-muted fs-13 mb-2">{{ Str::limit($testimonial->comments, 120) }}</p>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-success-transparent">
                            <i class="ri-thumb-up-line me-1"></i>{{ $testimonial->likes }}
                        </span>
                        <span class="badge bg-danger-transparent">
                            <i class="ri-thumb-down-line me-1"></i>{{ $testimonial->dislikes }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Reviews Yet</h6>
                    <p class="text-muted mb-3">Complete your profile to start receiving client reviews!</p>
                    <a href="{{ route('trainers.edit', Auth::id()) }}" class="btn btn-primary btn-sm">
                        <i class="ri-edit-line me-1"></i>Complete Profile
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- Profile & Quick Actions -->
    <div class="col-xl-4">
        <!-- Profile Completion -->
        <div class="card custom-card mb-4">
            <div class="card-header">
                <div class="card-title">
                    Profile Completion
                </div>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="progress-circle mx-auto" style="width: 80px; height: 80px; position: relative;">
                        <svg width="80" height="80" viewBox="0 0 80 80">
                            <circle cx="40" cy="40" r="35" fill="none" stroke="#e9ecef" stroke-width="6"></circle>
                            <circle cx="40" cy="40" r="35" fill="none" stroke="#0d6efd" stroke-width="6" 
                                stroke-dasharray="{{ 2 * 3.14159 * 35 }}" 
                                stroke-dashoffset="{{ 2 * 3.14159 * 35 * (1 - $stats['profile_completion'] / 100) }}" 
                                stroke-linecap="round" transform="rotate(-90 40 40)"></circle>
                        </svg>
                        <div class="position-absolute top-50 start-50 translate-middle">
                            <h5 class="fw-bold mb-0">{{ $stats['profile_completion'] }}%</h5>
                        </div>
                    </div>
                </div>
                <p class="text-muted text-center fs-13 mb-3">Complete your profile to attract more clients and build trust.</p>
                <a href="{{ route('trainers.edit', Auth::id()) }}" class="btn btn-outline-primary w-100">
                    <i class="ri-edit-line me-1"></i>Complete Profile
                </a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Quick Actions
                </div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-3">
                    <a href="{{ route('trainers.edit', Auth::id()) }}" class="btn btn-primary">
                        <i class="ri-edit-line me-2"></i>Edit Profile
                    </a>
                    <a href="{{ route('trainers.certifications.create', Auth::id()) }}" class="btn btn-outline-success">
                        <i class="ri-award-line me-2"></i>Add Certification
                    </a>
                    <a href="{{ route('trainer.testimonials') }}" class="btn btn-outline-info">
                        <i class="ri-chat-3-line me-2"></i>View Reviews
                    </a>
                    <a href="{{ route('trainers.show', Auth::id()) }}" class="btn btn-outline-secondary">
                        <i class="ri-eye-line me-2"></i>View Public Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Certifications -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Certifications ({{ $stats['recent_certifications']->count() }})
                </div>
                <div class="ms-auto">
                    <a href="{{ route('trainer.certifications') }}" class="btn btn-sm btn-outline-primary">
                        View All Certifications
                    </a>
                </div>
            </div>
            <div class="card-body">
                @forelse($stats['recent_certifications'] as $certification)
                <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 d-inline-block">
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="flex-fill">
                                <h6 class="fw-semibold mb-1">{{ $certification->certificate_name }}</h6>
                                <p class="text-muted fs-12 mb-2">Added: {{ $certification->created_at->format('M d, Y') }}</p>
                                @if($certification->doc)
                                    <a href="{{ asset('storage/' . $certification->doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="ri-download-line me-1"></i>View Certificate
                                    </a>
                                @else
                                    <span class="badge bg-secondary-transparent">No document</span>
                                @endif
                            </div>
                            <div class="ms-2">
                                <span class="avatar avatar-sm avatar-rounded bg-success-transparent">
                                    <i class="ri-award-line"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center py-5">
                    <i class="ri-award-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Certifications Yet</h6>
                    <p class="text-muted mb-3">Add your certifications to build credibility with clients!</p>
                    <a href="{{ route('trainers.certifications.create', Auth::id()) }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i>Add Your First Certification
                    </a>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')

@endsection