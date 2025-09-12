@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Profile</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
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
                    <div class="profile-banner-image">
                        <img src="{{asset('build/assets/images/media/media-3.jpg')}}" class="card-img-top" alt="...">
                    </div>
                    <div class="card-body p-4 pb-0 position-relative">
                        <div class="d-flex align-items-end justify-content-between flex-wrap">
                            <div>
                                <span class="avatar avatar-xxl avatar-rounded bg-info online">
                                    @if($user->profile_image)
                                        <img src="{{ asset('storage/' . $user->profile_image) }}" alt="">
                                    @else
                                        <img src="{{asset('build/assets/images/faces/12.jpg')}}" alt="">
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
                                <div class="btn-list">
                                    <a href="{{ route('profile.edit') }}" class="btn btn-primary btn-sm">
                                        <i class="ri-edit-line me-1"></i>Edit Profile
                                    </a>
                                    <a href="{{ route('profile.change-password') }}" class="btn btn-outline-secondary btn-sm">
                                        <i class="ri-lock-password-line me-1"></i>Change Password
                                    </a>
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