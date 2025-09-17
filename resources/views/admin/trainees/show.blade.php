@extends('layouts.master')

@section('styles')
<!-- Additional styles for profile view -->
<style>
.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 0.5rem;
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
}

.profile-avatar {
    width: 120px;
    height: 120px;
    border: 4px solid rgba(255, 255, 255, 0.2);
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 1rem;
    text-align: center;
    backdrop-filter: blur(10px);
}

.info-card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.timeline-item {
    border-left: 2px solid #e9ecef;
    padding-left: 1rem;
    margin-bottom: 1rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -6px;
    top: 0.5rem;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #6c757d;
}

.timeline-item.success::before {
    background: #198754;
}

.timeline-item.info::before {
    background: #0dcaf0;
}
</style>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Toggle trainee status
    $('#toggleStatusBtn').on('click', function() {
        const traineeId = {{ $trainee->id }};
        const currentStatus = '{{ $trainee->email_verified_at ? "active" : "inactive" }}';
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        
        if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this trainee?`)) {
            $.ajax({
                url: `{{ route('admin.trainees.index') }}/${traineeId}/toggle-status`,
                type: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to update status';
                    showAlert('error', message);
                }
            });
        }
    });

    // Delete trainee
    $('#deleteTraineeBtn').on('click', function() {
        const traineeId = {{ $trainee->id }};
        
        if (confirm('Are you sure you want to delete this trainee? This action cannot be undone.')) {
            $.ajax({
                url: `{{ route('admin.trainees.index') }}/${traineeId}`,
                type: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', response.message);
                        setTimeout(function() {
                            window.location.href = '{{ route("admin.trainees.index") }}';
                        }, 2000);
                    } else {
                        showAlert('error', response.message);
                    }
                },
                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Failed to delete trainee';
                    showAlert('error', message);
                }
            });
        }
    });
});

// Show alert function
function showAlert(type, message) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    
    // Auto hide after 5 seconds
    setTimeout(function() {
        $('.alert').alert('close');
    }, 5000);
}
</script>
@endsection

@section('content')
<!-- Page Header -->
<div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
    <div>
        <h1 class="page-title fw-semibold fs-18 mb-0">Trainee Details</h1>
        <div class="">
            <nav>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('admin.trainees.index') }}">Trainees</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $trainee->name }}</li>
                </ol>
            </nav>
        </div>
    </div>
    <div class="ms-auto pageheader-btn">
        <div class="btn-group" role="group">
            <a href="{{ route('admin.trainees.index') }}" class="btn btn-outline-primary btn-wave">
                <i class="ri-arrow-left-line fw-semibold align-middle me-1"></i> Back to List
            </a>
            <a href="{{ route('admin.trainees.edit', $trainee->id) }}" class="btn btn-success btn-wave">
                <i class="ri-edit-2-line fw-semibold align-middle me-1"></i> Edit
            </a>
        </div>
    </div>
</div>
<!-- Page Header Close -->

<!-- Alert Container -->
<div id="alertContainer"></div>

<!-- Profile Header -->
<div class="profile-header">
    <div class="row align-items-center">
        <div class="col-auto">
            @if($trainee->profile_image)
                <img src="{{ asset('storage/' . $trainee->profile_image) }}" alt="{{ $trainee->name }}" class="avatar profile-avatar avatar-rounded">
            @else
                <span class="avatar profile-avatar avatar-rounded bg-light text-dark">
                    <i class="ri-user-line fs-1"></i>
                </span>
            @endif
        </div>
        <div class="col">
            <h2 class="mb-1">{{ $trainee->name }}</h2>
            <p class="mb-2 opacity-75">{{ $trainee->email }}</p>
            <div class="d-flex align-items-center gap-3">
                @if($trainee->email_verified_at)
                    <span class="badge bg-success-transparent fs-12">Active Account</span>
                @else
                    <span class="badge bg-danger-transparent fs-12">Inactive Account</span>
                @endif
                <span class="opacity-75">Member since {{ $trainee->created_at->format('M Y') }}</span>
            </div>
        </div>
        <div class="col-auto">
            <div class="row g-3">
                <div class="col-6">
                    <div class="stat-card">
                        <h4 class="mb-0">{{ $trainee->goals->count() }}</h4>
                        <small>Goals</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="stat-card">
                        <h4 class="mb-0">{{ $trainee->receivedTestimonials->count() }}</h4>
                        <small>Reviews</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="row">
    <!-- Personal Information -->
    <div class="col-xl-4">
        <div class="card custom-card info-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-user-line me-2"></i> Personal Information
                </div>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Full Name</div>
                            <small class="text-muted">{{ $trainee->name }}</small>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Email Address</div>
                            <small class="text-muted">{{ $trainee->email }}</small>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Phone Number</div>
                            <small class="text-muted">{{ $trainee->phone ?? 'Not provided' }}</small>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Account Status</div>
                            @if($trainee->email_verified_at)
                                <span class="badge bg-success-transparent">Active</span>
                            @else
                                <span class="badge bg-danger-transparent">Inactive</span>
                            @endif
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Member Since</div>
                            <small class="text-muted">{{ $trainee->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                        <div class="ms-2 me-auto">
                            <div class="fw-semibold">Last Updated</div>
                            <small class="text-muted">{{ $trainee->updated_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card custom-card info-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-settings-line me-2"></i> Quick Actions
                </div>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.trainees.edit', $trainee->id) }}" class="btn btn-success btn-wave">
                        <i class="ri-edit-2-line me-2"></i> Edit Trainee
                    </a>
                    <button type="button" class="btn btn-warning btn-wave" id="toggleStatusBtn">
                        @if($trainee->email_verified_at)
                            <i class="ri-user-unfollow-line me-2"></i> Deactivate Account
                        @else
                            <i class="ri-user-add-line me-2"></i> Activate Account
                        @endif
                    </button>
                    <button type="button" class="btn btn-danger btn-wave" id="deleteTraineeBtn">
                        <i class="ri-delete-bin-5-line me-2"></i> Delete Trainee
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Goals and Activity -->
    <div class="col-xl-8">
        <!-- Goals Section -->
        <div class="card custom-card info-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-target-line me-2"></i> Goals ({{ $trainee->goals->count() }})
                </div>
            </div>
            <div class="card-body">
                @if($trainee->goals->count() > 0)
                    <div class="row">
                        @foreach($trainee->goals as $goal)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3">
                                <h6 class="mb-2">{{ $goal->title }}</h6>
                                <p class="text-muted mb-2 small">{{ Str::limit($goal->description, 100) }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary-transparent">{{ ucfirst($goal->status) }}</span>
                                    <small class="text-muted">{{ $goal->created_at->format('d M Y') }}</small>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="ri-target-line fs-1 text-muted mb-3"></i>
                        <p class="text-muted">No goals set yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Testimonials Section -->
        <div class="card custom-card info-card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="card-title">
                    <i class="ri-chat-quote-line me-2"></i> Testimonials ({{ $trainee->receivedTestimonials->count() }})
                </div>
            </div>
            <div class="card-body">
                @if($trainee->receivedTestimonials->count() > 0)
                    @foreach($trainee->receivedTestimonials as $testimonial)
                    <div class="border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                @if($testimonial->trainer && $testimonial->trainer->profile_image)
                                    <img src="{{ asset('storage/' . $testimonial->trainer->profile_image) }}" alt="{{ $testimonial->trainer->name }}" class="avatar avatar-sm avatar-rounded me-2">
                                @else
                                    <span class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2">
                                        <i class="ri-user-line"></i>
                                    </span>
                                @endif
                                <div>
                                    <h6 class="mb-0">{{ $testimonial->trainer->name ?? 'Unknown Trainer' }}</h6>
                                    <small class="text-muted">{{ $testimonial->created_at->format('d M Y') }}</small>
                                </div>
                            </div>
                            <div class="text-warning">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $testimonial->rating)
                                        <i class="ri-star-fill"></i>
                                    @else
                                        <i class="ri-star-line"></i>
                                    @endif
                                @endfor
                            </div>
                        </div>
                        <p class="mb-0">{{ $testimonial->comment }}</p>
                    </div>
                    @endforeach
                @else
                    <div class="text-center py-4">
                        <i class="ri-chat-quote-line fs-1 text-muted mb-3"></i>
                        <p class="text-muted">No testimonials received yet</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card custom-card info-card">
            <div class="card-header">
                <div class="card-title">
                    <i class="ri-time-line me-2"></i> Recent Activity
                </div>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Account Created</h6>
                                <p class="text-muted mb-0 small">Trainee account was created</p>
                            </div>
                            <small class="text-muted">{{ $trainee->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    
                    @if($trainee->email_verified_at)
                    <div class="timeline-item success">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Account Activated</h6>
                                <p class="text-muted mb-0 small">Email verified and account activated</p>
                            </div>
                            <small class="text-muted">{{ $trainee->email_verified_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    @if($trainee->goals->count() > 0)
                    <div class="timeline-item info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">First Goal Set</h6>
                                <p class="text-muted mb-0 small">Started setting fitness goals</p>
                            </div>
                            <small class="text-muted">{{ $trainee->goals->first()->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    @if($trainee->receivedTestimonials->count() > 0)
                    <div class="timeline-item info">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">First Review Received</h6>
                                <p class="text-muted mb-0 small">Received testimonial from trainer</p>
                            </div>
                            <small class="text-muted">{{ $trainee->receivedTestimonials->first()->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                    @endif
                    
                    <div class="timeline-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">Last Profile Update</h6>
                                <p class="text-muted mb-0 small">Profile information was updated</p>
                            </div>
                            <small class="text-muted">{{ $trainee->updated_at->format('d M Y, H:i') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection