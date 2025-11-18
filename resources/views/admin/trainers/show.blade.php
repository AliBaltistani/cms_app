@extends('layouts.master')

@section('title', 'Trainer Details - ' . $trainer->name)

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Trainer Details</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.trainers.index') }}">Trainers</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $trainer->name }}</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="d-flex">
            <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-primary btn-wave me-2">
                <i class="ri-arrow-left-line me-1"></i> Back to Trainers
            </a>
            <a href="{{ route('admin.trainers.edit', $trainer->id) }}" class="btn btn-primary btn-wave">
                <i class="ri-edit-line me-1"></i> Edit Trainer
            </a>
        </div>
    </div>

    <!-- Trainer Profile Card -->
    <div class="row">
        <div class="col-xl-4">
            <div class="card custom-card">
                <div class="card-body text-center">
                    <!-- Profile Image -->
                    <div class="mb-4">
                        @if($trainer->profile_image)
                            <img src="{{ asset('storage/' . $trainer->profile_image) }}" alt="Profile" class="img-fluid rounded-circle mb-3" style="max-width: 200px; height: 200px; object-fit: cover;">
                        @else
                            <div class="avatar avatar-xxl avatar-rounded bg-success-transparent mb-3 mx-auto" style="width: 200px; height: 200px; display: flex; align-items: center; justify-content: center;">
                                <i class="ri-user-star-line" style="font-size: 4rem;"></i>
                            </div>
                        @endif
                    </div>

                    @if($trainer->business_logo)
                    <div class="mb-4">
                        <img src="{{ asset('storage/' . $trainer->business_logo) }}" alt="Business Logo" class="img-fluid mb-2" style="max-width: 220px; height: auto; object-fit: contain;">
                        <div class="text-muted">Business Logo</div>
                    </div>
                    @endif

                    <!-- Basic Info -->
                    <h4 class="fw-semibold mb-1">{{ $trainer->name }}</h4>
                    <p class="text-muted mb-2">{{ $trainer->email }}</p>
                    <span class="badge bg-success-transparent fs-12 mb-3">{{ $trainer->designation ?? 'Personal Trainer' }}</span>

                    <!-- Status Badge -->
                    <div class="mb-3">
                        @if($trainer->email_verified_at)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>

                    <!-- Quick Stats -->
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="p-2">
                                <h5 class="fw-semibold mb-1">{{ $stats['total_certifications'] }}</h5>
                                <p class="text-muted mb-0 fs-12">Certifications</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <h5 class="fw-semibold mb-1">{{ $stats['total_testimonials'] }}</h5>
                                <p class="text-muted mb-0 fs-12">Testimonials</p>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2">
                                <h5 class="fw-semibold mb-1">{{ $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '0.0' }}</h5>
                                <p class="text-muted mb-0 fs-12">Rating ⭐</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Card -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Contact Information</div>
                </div>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="ri-phone-line text-muted"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Phone:</span>
                                    <span class="text-muted ms-2">{{ $trainer->phone ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="ri-mail-line text-muted"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Email:</span>
                                    <span class="text-muted ms-2">{{ $trainer->email }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="ri-calendar-line text-muted"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Joined:</span>
                                    <span class="text-muted ms-2">{{ $trainer->created_at->format('d M Y') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex align-items-center">
                                <div class="me-2">
                                    <i class="ri-award-line text-muted"></i>
                                </div>
                                <div>
                                    <span class="fw-semibold">Experience:</span>
                                    <span class="text-muted ms-2">{{ str_replace('_', ' ', ucwords($trainer->experience)) ?? 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Location Information Card -->
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title">
                        <i class="ri-map-pin-line me-2"></i>Location Information
                    </div>
                    <div class="card-options">
                        @if($trainer->location)
                            @if($trainer->location->hasCompleteAddress())
                                <span class="badge bg-success">Complete</span>
                            @else
                                <span class="badge bg-warning">Incomplete</span>
                            @endif
                        @else
                            <span class="badge bg-secondary">Not Set</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    @if($trainer->location)
                        <div class="row gy-3">
                            @if($trainer->location->country)
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="ri-global-line text-muted"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">Country:</span>
                                        <span class="text-muted ms-2">{{ $trainer->location->country }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($trainer->location->state)
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="ri-map-2-line text-muted"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">State/Province:</span>
                                        <span class="text-muted ms-2">{{ $trainer->location->state }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($trainer->location->city)
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="ri-building-line text-muted"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">City:</span>
                                        <span class="text-muted ms-2">{{ $trainer->location->city }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($trainer->location->zipcode)
                            <div class="col-12">
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        <i class="ri-mail-send-line text-muted"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">Zipcode:</span>
                                        <span class="text-muted ms-2">{{ $trainer->location->zipcode }}</span>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($trainer->location->address)
                            <div class="col-12">
                                <div class="d-flex align-items-start">
                                    <div class="me-2 mt-1">
                                        <i class="ri-road-map-line text-muted"></i>
                                    </div>
                                    <div>
                                        <span class="fw-semibold">Address:</span>
                                        <div class="text-muted ms-0 mt-1">{{ $trainer->location->address }}</div>
                                    </div>
                                </div>
                            </div>
                            @endif
                            
                            @if($trainer->location->hasCompleteAddress())
                            <div class="col-12">
                                <div class="alert alert-info mb-0">
                                    <div class="d-flex align-items-start">
                                        <i class="ri-map-pin-2-line me-2 mt-1"></i>
                                        <div>
                                            <strong>Complete Address:</strong>
                                            <div class="mt-1">{{ $trainer->location->full_address }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Location Management Actions -->
                        <div class="mt-3 pt-3 border-top">
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.user-locations.show', $trainer->location->id) }}" 
                                   class="btn btn-sm btn-outline-info">
                                    <i class="ri-eye-line me-1"></i>View Details
                                </a>
                                <a href="{{ route('admin.user-locations.edit', $trainer->location->id) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="ri-edit-line me-1"></i>Edit Location
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- No Location Set -->
                        <div class="text-center py-4">
                            <div class="avatar avatar-lg avatar-rounded bg-light mb-3 mx-auto">
                                <i class="ri-map-pin-line text-muted" style="font-size: 2rem;"></i>
                            </div>
                            <h6 class="text-muted mb-2">No Location Information</h6>
                            <p class="text-muted small mb-3">This trainer hasn't set their location information yet.</p>
                            <a href="{{ route('admin.user-locations.create', ['user_id' => $trainer->id]) }}" 
                               class="btn btn-sm btn-primary">
                                <i class="ri-add-line me-1"></i>Add Location
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <!-- About Section -->
            @if($trainer->about)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">About</div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">{{ $trainer->about }}</p>
                </div>
            </div>
            @endif

            <!-- Training Philosophy -->
            @if($trainer->training_philosophy)
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Training Philosophy</div>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-0">{{ $trainer->training_philosophy }}</p>
                </div>
            </div>
            @endif

            <!-- Statistics Card -->
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">Performance Statistics</div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="text-center p-3 border rounded">
                                <h4 class="fw-semibold text-primary mb-1">{{ $stats['total_certifications'] }}</h4>
                                <p class="text-muted mb-0 fs-12">Total Certifications</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center p-3 border rounded">
                                <h4 class="fw-semibold text-success mb-1">{{ $stats['total_testimonials'] }}</h4>
                                <p class="text-muted mb-0 fs-12">Total Testimonials</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center p-3 border rounded">
                                <h4 class="fw-semibold text-warning mb-1">{{ $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '0.0' }}</h4>
                                <p class="text-muted mb-0 fs-12">Average Rating</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="text-center p-3 border rounded">
                                <h4 class="fw-semibold text-info mb-1">{{ $stats['total_likes'] }}</h4>
                                <p class="text-muted mb-0 fs-12">Total Likes</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Certifications -->
            @if(isset($stats['recent_certifications']) && $stats['recent_certifications']->count() > 0)
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title">Recent Certifications</div>
                    <!-- <a href="#" class="btn btn-sm btn-outline-primary">View All</a> -->
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        @foreach($stats['recent_certifications'] as $certification)
                        <div class="list-group-item d-flex justify-content-between align-items-start border-0 px-0">
                            <div class="ms-2 me-auto">
                                <div class="fw-semibold">{{ $certification->name ?? $certification->certificate_name }}</div>
                                @if($certification->issuing_organization)
                                    <small class="text-muted">{{ $certification->issuing_organization }}</small>
                                @endif
                                @if($certification->issue_date)
                                    <br><small class="text-muted">Issued: {{ \Carbon\Carbon::parse($certification->issue_date)->format('M Y') }}</small>
                                @endif
                            </div>
                            <span class="badge bg-primary rounded-pill">{{ \Carbon\Carbon::parse($certification->created_at)->format('Y') }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Testimonials -->
            @if(isset($stats['recent_testimonials']) && $stats['recent_testimonials']->count() > 0)
            <div class="card custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title">Recent Testimonials</div>
                    <a href="#" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($stats['recent_testimonials'] as $testimonial)
                        <div class="col-md-6 mb-3">
                            <div class="border rounded p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <div class="avatar avatar-sm avatar-rounded bg-primary-transparent me-2">
                                        <i class="ri-user-line"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $testimonial->client->name ?? 'Anonymous' }}</h6>
                                        <small class="text-muted">{{ $testimonial->created_at->format('M d, Y') }}</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge bg-warning">{{ $testimonial->rate ?? 0 }} ⭐</span>
                                    </div>
                                </div>
                                @if($testimonial->comment)
                                    <p class="text-muted mb-0 fs-12">{{ Str::limit($testimonial->comment, 100) }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Add any specific JavaScript for trainer details page if needed
    $(document).ready(function() {
        // Initialize any tooltips or additional functionality
        $('[data-bs-toggle="tooltip"]').tooltip();
    });
</script>
@endsection