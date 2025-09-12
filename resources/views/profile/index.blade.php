@extends('layouts.master')

@section('styles')
<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
}
.profile-avatar {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid white;
    object-fit: cover;
}
.profile-stats {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.stat-item {
    text-align: center;
    padding: 1rem;
}
.stat-number {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
}
.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}
.info-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
}
.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid #f1f3f4;
}
.info-item:last-child {
    border-bottom: none;
}
.info-label {
    font-weight: 600;
    color: #495057;
}
.info-value {
    color: #6c757d;
}
.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
}
.role-admin {
    background-color: #dc3545;
    color: white;
}
.role-trainer {
    background-color: #28a745;
    color: white;
}
.role-client {
    background-color: #007bff;
    color: white;
}
</style>
@endsection

@section('content')

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profile</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">My Profile</h1>
    </div>
    <div class="btn-list">
        <a href="{{ route('profile.edit') }}" class="btn btn-primary">
            <i class="ri-edit-line me-1"></i>Edit Profile
        </a>
        <a href="{{ route('profile.change-password') }}" class="btn btn-outline-secondary">
            <i class="ri-lock-password-line me-1"></i>Change Password
        </a>
    </div>
</div>
<!-- Page Header Close -->

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

<!-- Profile Header -->
<div class="profile-header">
    <div class="row align-items-center">
        <div class="col-md-3 text-center">
            @if($user->profile_image)
                <img src="{{ asset('storage/' . $user->profile_image) }}" alt="Profile Image" class="profile-avatar">
            @else
                <div class="profile-avatar d-flex align-items-center justify-content-center" style="background-color: rgba(255,255,255,0.2);">
                    <i class="ri-user-line" style="font-size: 3rem;"></i>
                </div>
            @endif
        </div>
        <div class="col-md-9">
            <h2 class="mb-2">{{ $user->name }}</h2>
            <p class="mb-2 opacity-75">
                <i class="ri-mail-line me-2"></i>{{ $user->email }}
            </p>
            <p class="mb-2 opacity-75">
                <i class="ri-phone-line me-2"></i>{{ $user->phone }}
            </p>
            <span class="role-badge role-{{ $user->role }}">
                <i class="ri-shield-user-line me-1"></i>{{ ucfirst($user->role) }}
            </span>
        </div>
    </div>
</div>

<div class="row">
    <!-- Profile Statistics -->
    <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12">
        <div class="profile-stats">
            <h5 class="mb-3"><i class="ri-bar-chart-line me-2"></i>Account Statistics</h5>
            <div class="row">
                <div class="col-6">
                    <div class="stat-item">
                        <div class="stat-number">{{ \Carbon\Carbon::parse($user->created_at)->diffInDays() }}</div>
                        <div class="stat-label">Days Active</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="stat-number">{{ $user->email_verified_at ? '✓' : '✗' }}</div>
                        <div class="stat-label">Email Verified</div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-6">
                    <div class="stat-item">
                        <div class="stat-number">{{ \Carbon\Carbon::parse($user->updated_at)->format('M d') }}</div>
                        <div class="stat-label">Last Updated</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-item">
                        <div class="stat-number">{{ \Carbon\Carbon::parse($user->created_at)->format('M Y') }}</div>
                        <div class="stat-label">Member Since</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Information -->
    <div class="col-xl-8 col-lg-6 col-md-6 col-sm-12">
        <div class="info-card">
            <h5 class="mb-3"><i class="ri-information-line me-2"></i>Profile Information</h5>
            
            <div class="info-item">
                <span class="info-label">Full Name</span>
                <span class="info-value">{{ $user->name }}</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Email Address</span>
                <span class="info-value">
                    {{ $user->email }}
                    @if($user->email_verified_at)
                        <i class="ri-verified-badge-line text-success ms-1" title="Verified"></i>
                    @else
                        <i class="ri-error-warning-line text-warning ms-1" title="Not Verified"></i>
                    @endif
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Phone Number</span>
                <span class="info-value">{{ $user->phone }}</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Role</span>
                <span class="info-value">
                    <span class="role-badge role-{{ $user->role }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Account Created</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y \\a\\t g:i A') }}</span>
            </div>
            
            <div class="info-item">
                <span class="info-label">Last Updated</span>
                <span class="info-value">{{ \Carbon\Carbon::parse($user->updated_at)->diffForHumans() }}</span>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="info-card">
            <h5 class="mb-3"><i class="ri-settings-3-line me-2"></i>Quick Actions</h5>
            <div class="row">
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="{{ route('profile.edit') }}" class="btn btn-outline-primary w-100">
                        <i class="ri-edit-line me-2"></i>Edit Profile
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="{{ route('profile.change-password') }}" class="btn btn-outline-secondary w-100">
                        <i class="ri-lock-password-line me-2"></i>Change Password
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="{{ route('profile.settings') }}" class="btn btn-outline-info w-100">
                        <i class="ri-settings-4-line me-2"></i>Settings
                    </a>
                </div>
                <div class="col-md-3 col-sm-6 mb-3">
                    <a href="{{ route('profile.activity-log') }}" class="btn btn-outline-warning w-100">
                        <i class="ri-history-line me-2"></i>Activity Log
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert && alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
@endsection