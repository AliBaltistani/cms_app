@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Admin Dashboard</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Admin</a></li>
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

<!-- Start::row-1 -->
<div class="row">
    <!-- System Statistics Cards -->
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="fw-semibold mb-1">{{ $stats['total_users'] }}</h3>
                        <span class="d-block text-muted">Total Users</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-primary-transparent">
                            <i class="ri-user-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_trainers'] }}</h3>
                        <span class="d-block text-muted">Total Trainers</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-success-transparent">
                            <i class="ri-user-star-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_clients'] }}</h3>
                        <span class="d-block text-muted">Total Clients</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-info-transparent">
                            <i class="ri-user-heart-line fs-18"></i>
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
                        <span class="d-block text-muted">Total Reviews</span>
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

<!-- Additional Statistics Row -->
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
                        <span class="avatar avatar-md avatar-rounded bg-secondary-transparent">
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_workouts'] }}</h3>
                        <span class="d-block text-muted">Total Workouts</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-danger-transparent">
                            <i class="ri-run-line fs-18"></i>
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
                        <h3 class="fw-semibold mb-1">{{ $stats['total_admins'] }}</h3>
                        <span class="d-block text-muted">Total Admins</span>
                    </div>
                    <div class="ms-2">
                        <span class="avatar avatar-md avatar-rounded bg-dark-transparent">
                            <i class="ri-admin-line fs-18"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xxl-3 col-xl-6 col-lg-6 col-md-6 col-sm-12">
        <div class="card custom-card">
            <div class="card-body">
                <div class="text-center">
                    <a href="{{ route('admin.users') }}" class="btn btn-primary btn-sm">
                        <i class="ri-settings-line me-1"></i>Manage System
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity Section -->
<div class="row">
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Users ({{ $stats['recent_users']->count() }})
                </div>
            </div>
            <div class="card-body">
                @forelse($stats['recent_users'] as $user)
                <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="avatar avatar-sm avatar-rounded">
                            @if($user->profile_image)
                                <img src="{{ asset('storage/' . $user->profile_image) }}" alt="{{ $user->name }}">
                            @else
                                <img src="{{asset('build/assets/images/faces/1.jpg')}}" alt="{{ $user->name }}">
                            @endif
                        </span>
                        <div>
                            <h6 class="fw-semibold mb-0">{{ $user->name }}</h6>
                            <span class="text-muted fs-12">{{ ucfirst($user->role) }} • {{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                    <div>
                        <span class="badge bg-{{ $user->role === 'admin' ? 'dark' : ($user->role === 'trainer' ? 'success' : 'info') }}-transparent">
                            {{ ucfirst($user->role) }}
                        </span>
                    </div>
                </div>
                @empty
                <div class="text-center py-4">
                    <i class="ri-user-line fs-48 text-muted mb-3"></i>
                    <h6 class="fw-semibold mb-2">No Recent Users</h6>
                    <p class="text-muted mb-0">No new users have registered recently.</p>
                </div>
                @endforelse
                
                @if($stats['recent_users']->count() > 0)
                <div class="text-center mt-3">
                    <a href="{{ route('admin.users') }}" class="btn btn-outline-primary btn-sm">
                        View All Users
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Recent Testimonials ({{ $stats['recent_testimonials']->count() }})
                </div>
            </div>
            <div class="card-body">
                @forelse($stats['recent_testimonials'] as $testimonial)
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm avatar-rounded">
                                <img src="{{asset('build/assets/images/faces/2.jpg')}}" alt="{{ $testimonial->name }}">
                            </span>
                            <div>
                                <h6 class="fw-semibold mb-0">{{ $testimonial->name }}</h6>
                                <span class="text-muted fs-12">for {{ $testimonial->trainer->name }}</span>
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
                    <h6 class="fw-semibold mb-2">No Recent Testimonials</h6>
                    <p class="text-muted mb-0">No new testimonials have been submitted recently.</p>
                </div>
                @endforelse
                
                @if($stats['recent_testimonials']->count() > 0)
                <div class="text-center mt-3">
                    <a href="{{ route('admin.reports') }}" class="btn btn-outline-primary btn-sm">
                        View Reports
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Quick Actions
                </div>
            </div>
            <div class="card-body">
                <div class="row gy-3">
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <a href="{{ route('admin.users') }}" class="btn btn-outline-primary w-100">
                            <i class="ri-user-settings-line me-2"></i>Manage Users
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <a href="{{ route('goals.index') }}" class="btn btn-outline-success w-100">
                            <i class="ri-target-line me-2"></i>Manage Goals
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <a href="{{ route('workouts.index') }}" class="btn btn-outline-info w-100">
                            <i class="ri-run-line me-2"></i>Manage Workouts
                        </a>
                    </div>
                    <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline-warning w-100">
                            <i class="ri-bar-chart-line me-2"></i>View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')

@endsection