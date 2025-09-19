@extends('layouts.master')

@section('styles')
    <!-- Apex Charts CSS -->
    <link rel="stylesheet" href="{{ asset('assets/libs/apexcharts/apexcharts.css') }}">
@endsection

@section('content')
    <!-- Page Header -->
    <div class="d-md-flex d-block align-items-center justify-content-between my-4 page-header-breadcrumb">
        <div>
            <h1 class="page-title fw-semibold fs-18 mb-0">Booking Dashboard</h1>
            <div class="">
                <nav>
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('admin.bookings.index') }}">Bookings</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="ms-auto pageheader-btn">
            <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary btn-wave waves-effect waves-light me-2">
                <i class="ri-add-line fw-semibold align-middle me-1"></i> Create Booking
            </a>
            <a href="{{ route('admin.bookings.index') }}" class="btn btn-secondary btn-wave waves-effect waves-light">
                <i class="ri-list-check fw-semibold align-middle me-1"></i> All Bookings
            </a>
        </div>
    </div>
    <!-- Page Header Close -->

    <!-- Start::row-1 -->
    <div class="row">
        <!-- Today's Bookings -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-primary">
                                <i class="ti ti-calendar-event fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Today's Bookings</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['today_bookings'] }}</h4>
                                </div>
                                <div id="crm-total-customers"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pending Bookings -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-warning">
                                <i class="ti ti-clock fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Pending Approval</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['pending_bookings'] }}</h4>
                                </div>
                                <div id="crm-total-revenue"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Confirmed Bookings -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-success">
                                <i class="ti ti-check fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Confirmed Bookings</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['confirmed_bookings'] }}</h4>
                                </div>
                                <div id="crm-conversion-ratio"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Total Users -->
        <div class="col-xxl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card custom-card">
                <div class="card-body">
                    <div class="d-flex align-items-top justify-content-between">
                        <div>
                            <span class="avatar avatar-md avatar-rounded bg-info">
                                <i class="ti ti-users fs-16"></i>
                            </span>
                        </div>
                        <div class="flex-fill ms-3">
                            <div class="d-flex align-items-center justify-content-between flex-wrap">
                                <div>
                                    <p class="text-muted mb-0">Total Users</p>
                                    <h4 class="fw-semibold mt-1">{{ $stats['total_trainers'] + $stats['total_clients'] }}</h4>
                                    <small class="text-muted">{{ $stats['total_trainers'] }} Trainers, {{ $stats['total_clients'] }} Clients</small>
                                </div>
                                <div id="crm-total-deals"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-1 -->

    <!-- Start::row-2 -->
    <div class="row">
        <!-- Weekly Stats -->
        <div class="col-xxl-6 col-xl-6">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Weekly Overview
                    </div>
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn btn-light btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="ri-more-2-fill"></i>
                        </a>
                        <ul class="dropdown-menu" role="menu">
                            <li><a class="dropdown-item" href="javascript:void(0);">This Week</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">Last Week</a></li>
                            <li><a class="dropdown-item" href="javascript:void(0);">This Month</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="fw-semibold mb-1">{{ $stats['week_bookings'] }}</h3>
                                <p class="text-muted mb-0">This Week</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <h3 class="fw-semibold mb-1">{{ $stats['month_bookings'] }}</h3>
                                <p class="text-muted mb-0">This Month</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xxl-6 col-xl-6">
            <div class="card custom-card">
                <div class="card-header">
                    <div class="card-title">
                        Quick Actions
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="{{ route('admin.bookings.create') }}" class="btn btn-primary w-100">
                                <i class="ri-add-line me-2"></i>
                                Create Booking
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.bookings.index', ['status' => 'pending']) }}" class="btn btn-warning w-100">
                                <i class="ri-time-line me-2"></i>
                                Pending Approvals
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.bookings.export') }}" class="btn btn-success w-100">
                                <i class="ri-download-line me-2"></i>
                                Export Data
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="{{ route('admin.bookings.index') }}" class="btn btn-info w-100">
                                <i class="ri-list-check me-2"></i>
                                View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-2 -->

    <!-- Start::row-3 -->
    <div class="row">
        <!-- Recent Bookings -->
        <div class="col-xxl-6 col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Recent Bookings
                    </div>
                    <a href="{{ route('admin.bookings.index') }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Trainer</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentBookings as $booking)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <img src="{{ $booking->trainer->profile_image ? asset('storage/' . $booking->trainer->profile_image) : asset('assets/images/faces/9.jpg') }}" alt="trainer" class="avatar-img rounded-circle">
                                                </div>
                                                <span class="fw-semibold">{{ $booking->trainer->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <img src="{{ $booking->client->profile_image ? asset('storage/' . $booking->client->profile_image) : asset('assets/images/faces/9.jpg') }}" alt="client" class="avatar-img rounded-circle">
                                                </div>
                                                <span class="fw-semibold">{{ $booking->client->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $booking->date->format('M d') }}</span>
                                            <br><small class="text-muted">{{ $booking->start_time->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($booking->status == 'pending')
                                                <span class="badge bg-warning-transparent">Pending</span>
                                            @elseif($booking->status == 'confirmed')
                                                <span class="badge bg-success-transparent">Confirmed</span>
                                            @else
                                                <span class="badge bg-danger-transparent">Cancelled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3">
                                            <span class="text-muted">No recent bookings</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Bookings -->
        <div class="col-xxl-6 col-xl-12">
            <div class="card custom-card">
                <div class="card-header justify-content-between">
                    <div class="card-title">
                        Upcoming Bookings
                    </div>
                    <a href="{{ route('admin.bookings.index', ['date_from' => now()->toDateString()]) }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Trainer</th>
                                    <th>Client</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($upcomingBookings as $booking)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <img src="{{ $booking->trainer->profile_image ? asset('storage/' . $booking->trainer->profile_image) : asset('assets/images/faces/9.jpg') }}" alt="trainer" class="avatar-img rounded-circle">
                                                </div>
                                                <span class="fw-semibold">{{ $booking->trainer->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar avatar-xs me-2">
                                                    <img src="{{ $booking->client->profile_image ? asset('storage/' . $booking->client->profile_image) : asset('assets/images/faces/9.jpg') }}" alt="client" class="avatar-img rounded-circle">
                                                </div>
                                                <span class="fw-semibold">{{ $booking->client->name }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $booking->date->format('M d') }}</span>
                                            <br><small class="text-muted">{{ $booking->start_time->format('h:i A') }}</small>
                                        </td>
                                        <td>
                                            @if($booking->status == 'pending')
                                                <span class="badge bg-warning-transparent">Pending</span>
                                            @elseif($booking->status == 'confirmed')
                                                <span class="badge bg-success-transparent">Confirmed</span>
                                            @else
                                                <span class="badge bg-danger-transparent">Cancelled</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-3">
                                            <span class="text-muted">No upcoming bookings</span>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- End::row-3 -->
@endsection

@section('scripts')
    <!-- Apex Charts JS -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    
    <script>
        // You can add charts here if needed
        $(document).ready(function() {
            // Initialize any charts or additional functionality
        });
    </script>
@endsection