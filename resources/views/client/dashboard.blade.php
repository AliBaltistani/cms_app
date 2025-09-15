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
                    <div>
                        <a href="{{ route('client.goals.create') }}" class="btn btn-primary">
                            <i class="ri-add-line me-1"></i>Set New Goal
                        </a>
                    </div>
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
                <div class="ms-auto">
                    <a href="{{ route('client.goals') }}" class="btn btn-sm btn-outline-primary">
                        View All Goals
                    </a>
                </div>
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
                <div class="text-center py-5">
                    <i class="ri-target-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Goals Yet</h6>
                    <p class="text-muted mb-3">Start your fitness journey by setting your first goal!</p>
                    <a href="{{ route('client.goals.create') }}" class="btn btn-primary btn-sm">
                        <i class="ri-add-line me-1"></i>Create Your First Goal
                    </a>
                </div>
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
                    <a href="{{ route('client.goals.create') }}" class="btn btn-primary">
                        <i class="ri-target-line me-2"></i>Set New Goal
                    </a>
                    <a href="{{ route('client.trainers') }}" class="btn btn-outline-success">
                        <i class="ri-user-star-line me-2"></i>Find Trainers
                    </a>
                    <a href="{{ route('client.testimonials') }}" class="btn btn-outline-info">
                        <i class="ri-chat-3-line me-2"></i>My Reviews
                    </a>
                    <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
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

@endsection

@section('scripts')

@endsection