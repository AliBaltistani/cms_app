@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">User Profile</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Profile</li>
        </ol>
    </div>
</div>
<!-- End::page-header -->

<!-- Start:: row-1 -->
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="row">
            <div class="col-xl-12">
                <div class="card custom-card profile-card">
                    <div class="profile-banner-image" style="background-color: #f3f6fa;">
                        {{-- <img src="{{ asset('build/assets/images/media/media-3.jpg') }}" class="card-img-top" alt="..." onerror="this.style.display='none'; this.parentElement.style.backgroundColor='#e0e7ef';"> --}}
                    </div>
                    <div class="card-body p-4 pb-0 position-relative">
                        <div class="d-flex align-items-end justify-content-between flex-wrap">
                            <div>
                                <span class="avatar avatar-xxl avatar-rounded bg-info online">
                                    @if($user->profile_image)
                                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="">
                                    @else
                                         {{ strtoupper(substr($user->name, 0, 1)) }}
                                    @endif
                                </span>
                                <div class="mt-4 mb-3 d-flex align-items-center flex-wrap gap-3 justify-content-between">
                                    <div>
                                        <h5 class="fw-semibold mb-1">{{ $user->name }}</h5>
                                        <span class="d-block fw-medium text-muted mb-1">{{ ucfirst($user->role) }}</span>
                                        <p class="fs-12 mb-0 fw-medium text-muted"> <span class="me-3"><i class="ri-mail-line me-1 align-middle"></i>{{ $user->email }}</span> <span><i class="ri-phone-line me-1 align-middle"></i>{{ $user->phone }}</span> </p>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="btn-list mb-3">
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-primary btn-sm">
                                        <i class="ri-edit-line me-1"></i>Edit User
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleUserStatus({{ $user->id }})">
                                        @if($user->email_verified_at)
                                            <i class="ri-user-unfollow-line me-1"></i>Deactivate
                                        @else
                                            <i class="ri-user-follow-line me-1"></i>Activate
                                        @endif
                                    </button>
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
                                                <h3 class="fw-semibold mb-1">
                                                    {{ ceil(\Carbon\Carbon::parse($user->created_at)->diffInDays()) }}
                                                </h3>
                                                <span class="d-block text-muted">Days Active</span>
                                            </div>
                                            <div class="vr"></div>
                                            <div class="text-center">
                                                <h3 class="fw-semibold mb-1">
                                                    {{ $user->email_verified_at ? 'Verified' : 'Pending' }}
                                                </h3>
                                                <span class="d-block text-muted">Email Status</span>
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
                                        <p class="text-muted">User profile information and account details.</p>
                                        <div class="text-muted">
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-mail-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Email : </span> {{ $user->email }}
                                            </div>
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-phone-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Phone : </span> {{ $user->phone }}
                                            </div>
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-shield-user-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Role : </span> {{ ucfirst($user->role) }}
                                            </div>
                                            @if($user->role === 'trainer' && $user->designation)
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-user-star-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Designation : </span> {{ $user->designation }}
                                            </div>
                                            @endif
                                            @if($user->role === 'trainer' && $user->experience)
                                            <div class="mb-2 d-flex align-items-center gap-1 flex-wrap">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-time-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Experience : </span> {{ $user->experience }} years
                                            </div>
                                            @endif
                                            <div class="mb-0 d-flex align-items-center gap-1">
                                                <span class="avatar avatar-sm avatar-rounded text-default">
                                                    <i class="ri-calendar-line align-middle fs-15"></i>
                                                </span>
                                                <span class="fw-medium text-default">Member Since : </span> {{ \Carbon\Carbon::parse($user->created_at)->format('M Y') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xxl-8">
                        @if($user->role === 'trainer')
                            <!-- Trainer Profile with Tabs -->
                            <div class="card custom-card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs nav-tabs-header mb-0" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link active" data-bs-toggle="tab" role="tab" aria-current="page" href="#profile-info" aria-selected="true">
                                                <i class="ri-user-line me-1"></i>Profile Information
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#certifications" aria-selected="false" tabindex="-1">
                                                <i class="ri-award-line me-1"></i>Certifications
                                            </a>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link" data-bs-toggle="tab" role="tab" aria-current="page" href="#testimonials" aria-selected="false" tabindex="-1">
                                                <i class="ri-chat-3-line me-1"></i>Client Reviews
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Profile Information Tab -->
                                        <div class="tab-pane show active" id="profile-info" role="tabpanel">
                                            <div class="row gy-3">
                                                <div class="col-xl-6">
                                                    <label class="form-label">Full Name :</label>
                                                    <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                                                </div>
                                                <div class="col-xl-6">
                                                    <label class="form-label">Email :</label>
                                                    <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                                                </div>
                                                <div class="col-xl-6">
                                                    <label class="form-label">Phone No :</label>
                                                    <input type="text" class="form-control" value="{{ $user->phone }}" readonly>
                                                </div>
                                                <div class="col-xl-6">
                                                    <label class="form-label">Role :</label>
                                                    <input type="text" class="form-control" value="{{ ucfirst($user->role) }}" readonly>
                                                </div>
                                                <div class="col-xl-6">
                                                    <label class="form-label">Member Since :</label>
                                                    <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}" readonly>
                                                </div>
                                                <div class="col-xl-6">
                                                    <label class="form-label">Email Status :</label>
                                                    <input type="text" class="form-control" value="{{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}" readonly>
                                                </div>
                                                
                                                @if($user->designation)
                                                <div class="col-xl-6">
                                                    <label class="form-label">Designation :</label>
                                                    <input type="text" class="form-control" value="{{ $user->designation }}" readonly>
                                                </div>
                                                @endif
                                                
                                                @if($user->experience)
                                                <div class="col-xl-6">
                                                    <label class="form-label">Experience :</label>
                                                    <input type="text" class="form-control" value="{{ $user->experience }} years" readonly>
                                                </div>
                                                @endif
                                                
                                                @if($user->about)
                                                <div class="col-xl-12">
                                                    <label class="form-label">About Me :</label>
                                                    <textarea class="form-control" rows="4" readonly>{{ $user->about }}</textarea>
                                                </div>
                                                @endif
                                                
                                                @if($user->training_philosophy)
                                                <div class="col-xl-12">
                                                    <label class="form-label">Training Philosophy :</label>
                                                    <textarea class="form-control" rows="4" readonly>{{ $user->training_philosophy }}</textarea>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <!-- Certifications Tab -->
                                        <div class="tab-pane" id="certifications" role="tabpanel">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h6 class="mb-0">Professional Certifications</h6>
                                                <span class="badge bg-primary-transparent">{{ $user->certifications->count() }} Certifications</span>
                                            </div>
                                            
                                            @if($user->certifications->count() > 0)
                                                <div class="row">
                                                    @foreach($user->certifications as $certification)
                                                    <div class="col-md-4 col-sm-6 mb-3">
                                                        <div class="card border h-100">
                                                            <div class="card-body text-center p-4">
                                                                <div class="mb-3">
                                                                    <span class="avatar avatar-lg avatar-rounded bg-primary-transparent">
                                                                        <i class="ri-award-line fs-24"></i>
                                                                    </span>
                                                                </div>
                                                                <h6 class="fw-semibold mb-2">{{ $certification->certificate_name }}</h6>
                                                                <p class="text-muted fs-12 mb-3">Certified</p>
                                                                <div class="d-flex flex-column gap-2">
                                                                    @if($certification->doc)
                                                                        <a href="{{ asset('storage/' . $certification->doc) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                            <i class="ri-download-line me-1"></i>View Document
                                                                        </a>
                                                                    @else
                                                                        <span class="badge bg-secondary-transparent">No Document</span>
                                                                    @endif
                                                                    <small class="text-muted">Added: {{ $certification->created_at->format('d M, Y') }}</small>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div class="text-center py-4">
                                                    <i class="ri-award-line fs-48 text-muted mb-3"></i>
                                                    <h5 class="fw-semibold mb-2">No Certifications</h5>
                                                    <p class="text-muted mb-0">This trainer hasn't added any certifications yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                        
                                        <!-- Testimonials Tab -->
                                        <div class="tab-pane" id="testimonials" role="tabpanel">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <h6 class="mb-0">Client Reviews & Testimonials</h6>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <span class="badge bg-warning-transparent">
                                                            <i class="ri-star-line me-1"></i>{{ number_format($user->receivedTestimonials->avg('rate') ?: 0, 1) }} Rating
                                                        </span>
                                                        <span class="badge bg-primary-transparent">
                                                            {{ $user->receivedTestimonials->count() }} Reviews
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            @if($user->receivedTestimonials->count() > 0)
                                                <!-- Statistics Row -->
                                                <div class="row mb-4">
                                                    <div class="col-md-3">
                                                        <div class="text-center p-3 border rounded">
                                                            <h4 class="fw-semibold mb-1 text-warning">{{ number_format($user->receivedTestimonials->avg('rate') ?: 0, 1) }}</h4>
                                                            <small class="text-muted">Average Rating</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-3 border rounded">
                                                            <h4 class="fw-semibold mb-1 text-primary">{{ $user->receivedTestimonials->count() }}</h4>
                                                            <small class="text-muted">Total Reviews</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-3 border rounded">
                                                            <h4 class="fw-semibold mb-1 text-success">{{ $user->receivedTestimonials->sum('likes') }}</h4>
                                                            <small class="text-muted">Total Likes</small>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="text-center p-3 border rounded">
                                                            <h4 class="fw-semibold mb-1 text-danger">{{ $user->receivedTestimonials->sum('dislikes') }}</h4>
                                                            <small class="text-muted">Total Dislikes</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Reviews List -->
                                                <div class="row">
                                                    @foreach($user->receivedTestimonials->take(6) as $testimonial)
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card border">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <div class="d-flex align-items-center gap-2">
                                                                        <span class="avatar avatar-sm avatar-rounded bg-info-transparent">
                                                                            <i class="ri-user-line fs-14"></i>
                                                                        </span>
                                                                        <div>
                                                                            <h6 class="mb-1">{{ $testimonial->name }}</h6>
                                                                            <div class="d-flex align-items-center gap-1 mb-1">
                                                                                @for($i = 1; $i <= 5; $i++)
                                                                                    @if($i <= $testimonial->rate)
                                                                                        <i class="ri-star-fill text-warning fs-12"></i>
                                                                                    @else
                                                                                        <i class="ri-star-line text-muted fs-12"></i>
                                                                                    @endif
                                                                                @endfor
                                                                                <span class="ms-1 fw-semibold fs-12">{{ $testimonial->rate }}/5</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <small class="text-muted">{{ $testimonial->created_at->format('d M, Y') }}</small>
                                                                </div>
                                                                <p class="text-muted mb-2 fs-13">{{ Str::limit($testimonial->comments, 120) }}</p>
                                                                <div class="d-flex gap-2">
                                                                    <span class="badge bg-success-transparent fs-11">
                                                                        <i class="ri-thumb-up-line me-1"></i>{{ $testimonial->likes }}
                                                                    </span>
                                                                    <span class="badge bg-danger-transparent fs-11">
                                                                        <i class="ri-thumb-down-line me-1"></i>{{ $testimonial->dislikes }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                
                                                @if($user->receivedTestimonials->count() > 6)
                                                <div class="text-center mt-3">
                                                    <p class="text-muted">Showing 6 of {{ $user->receivedTestimonials->count() }} reviews</p>
                                                </div>
                                                @endif
                                            @else
                                                <div class="text-center py-4">
                                                    <i class="ri-chat-3-line fs-48 text-muted mb-3"></i>
                                                    <h5 class="fw-semibold mb-2">No Reviews Yet</h5>
                                                    <p class="text-muted mb-0">This trainer hasn't received any client reviews yet.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Regular User Profile -->
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        Profile Information
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row gy-3">
                                        <div class="col-xl-6">
                                            <label class="form-label">Full Name :</label>
                                            <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                                        </div>
                                        <div class="col-xl-6">
                                            <label class="form-label">Email :</label>
                                            <input type="email" class="form-control" value="{{ $user->email }}" readonly>
                                        </div>
                                        <div class="col-xl-6">
                                            <label class="form-label">Phone No :</label>
                                            <input type="text" class="form-control" value="{{ $user->phone }}" readonly>
                                        </div>
                                        <div class="col-xl-6">
                                            <label class="form-label">Role :</label>
                                            <input type="text" class="form-control" value="{{ ucfirst($user->role) }}" readonly>
                                        </div>
                                        <div class="col-xl-6">
                                            <label class="form-label">Member Since :</label>
                                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($user->created_at)->format('F d, Y') }}" readonly>
                                        </div>
                                        <div class="col-xl-6">
                                            <label class="form-label">Email Status :</label>
                                            <input type="text" class="form-control" value="{{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
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
// Toggle user status function
function toggleUserStatus(userId) {
    if (confirm('Are you sure you want to toggle this user\'s status?')) {
        $.ajax({
            url: `/admin/users/${userId}/toggle-status`,
            method: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            },
            error: function() {
                alert('Failed to update user status.');
            }
        });
    }
}
</script>
@endsection