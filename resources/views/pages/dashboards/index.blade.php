
@extends('layouts.master')

@section('styles')



@endsection

@section('content')
	
                    <!-- Start::page-header -->
                    <div class="d-flex align-items-center justify-content-between mb-3 page-header-breadcrumb flex-wrap gap-2">
                        <div>
                            <h1 class="page-title fw-medium fs-20 mb-0">Dashboard</h1>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <div class="form-group">
                                <input type="text" class="form-control breadcrumb-input" id="daterange" placeholder="Search By Date Range">
                            </div>
                            <div class="btn-list">
                                <button class="btn btn-icon btn-primary btn-wave">
                                    <i class="ri-refresh-line"></i>
                                </button>
                                <button class="btn btn-icon btn-primary btn-wave me-0">
                                    <i class="ri-filter-3-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- End::page-header -->

                    <!-- Start::app-content -->
                    <div class="row row-sm mt-lg-4">
                        <!-- Welcome Card -->
                        <div class="col-sm-12 col-lg-8 col-xl-8">
                            <div class="card bg-primary custom-card card-box">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h4 class="text-fixed-white mb-2">
                                                <i class="ri-user-smile-line me-2"></i>Welcome back, {{ $user->name }}!
                                            </h4>
                                            <p class="text-fixed-white mb-2 opacity-75">
                                                You have successfully logged into the Go Globe CMS Dashboard.
                                            </p>
                                            <small class="text-fixed-white opacity-50">
                                                <i class="ri-time-line me-1"></i>Last login: {{ now()->format('M d, Y \\a\\t h:i A') }}
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <div class="avatar avatar-xl">
                                                <div class="avatar-initial bg-white text-primary rounded-circle fs-24 fw-bold">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Stats Card -->
                        <div class="col-sm-12 col-lg-4 col-xl-4">
                            <div class="card custom-card">
                                <div class="card-body">
                                    <div class="text-center">
                                        <div class="avatar avatar-xxl mx-auto mb-3">
                                            <div class="avatar-initial bg-primary-gradient rounded-circle fs-28 fw-bold">
                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                            </div>
                                        </div>
                                        <h5 class="mb-1">{{ $user->name }}</h5>
                                        <p class="text-muted mb-3">{{ $user->email }}</p>
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h6 class="mb-1">{{ $total_users }}</h6>
                                                    <small class="text-muted">Total Users</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="text-center">
                                                    <h6 class="mb-1">{{ $user_since }}</h6>
                                                    <small class="text-muted">Member Since</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Row -->
                    <div class="row row-sm mt-4">
                        <div class="col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <i class="ri-dashboard-line me-2"></i>Quick Actions
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-primary btn-lg">
                                                    <i class="ri-user-add-line fs-18 mb-2 d-block"></i>
                                                    Add User
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-success btn-lg">
                                                    <i class="ri-settings-3-line fs-18 mb-2 d-block"></i>
                                                    Settings
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-grid">
                                                <button class="btn btn-outline-info btn-lg">
                                                    <i class="ri-file-text-line fs-18 mb-2 d-block"></i>
                                                    Reports
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="d-grid">
                                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-danger btn-lg w-100" 
                                                            onclick="return confirm('Are you sure you want to logout?')">
                                                        <i class="ri-logout-box-line fs-18 mb-2 d-block"></i>
                                                        Logout
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Information Row -->
                    <div class="row row-sm mt-4">
                        <div class="col-sm-12">
                            <div class="card custom-card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <i class="ri-information-line me-2"></i>System Information
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Authentication Status</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <span class="badge bg-success me-2">Active</span>
                                                <span>User session is active and secure</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ri-shield-check-line text-success me-2"></i>
                                                <span>Authentication system is working properly</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="ri-time-line text-info me-2"></i>
                                                <span>Session started: {{ now()->format('M d, Y \\a\\t h:i A') }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-3">Account Details</h6>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ri-mail-line text-primary me-2"></i>
                                                <span>{{ $user->email }}</span>
                                            </div>
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="ri-calendar-line text-warning me-2"></i>
                                                <span>Joined: {{ $user->created_at->format('M d, Y') }}</span>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <i class="ri-check-line text-success me-2"></i>
                                                <span>Email verified</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End::app-content -->
                     
@endsection

@section('scripts')
        


@endsection
