@extends('layouts.master')

@section('styles')

@endsection

@section('content')

<!-- Start::page-header -->
<div class="page-header-breadcrumb mb-3">
    <div class="d-flex align-center justify-content-between flex-wrap">
        <h1 class="page-title fw-medium fs-18 mb-0">Change Password</h1>
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="javascript:void(0);">Pages</a></li>
            <li class="breadcrumb-item active" aria-current="page">Change Password</li>
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

<!-- Start::row-1 -->
<div class="row">
    <div class="col-xl-12">
        <div class="card custom-card">
            <div class="card-header">
                <div class="card-title">
                    Change Password
                </div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    <div class="row gy-3">
                        <div class="col-xl-12">
                            <label for="current-password" class="form-label text-default">Current Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control form-control-lg @error('current_password') is-invalid @enderror" id="current-password" name="current_password" placeholder="current password">
                                <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('current-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                            </div>
                            @error('current_password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-12">
                            <label for="new-password" class="form-label text-default">New Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control form-control-lg @error('password') is-invalid @enderror" id="new-password" name="password" placeholder="new password">
                                <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('new-password',this)" id="button-addon21"><i class="ri-eye-off-line align-middle"></i></a>
                            </div>
                            @error('password')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                        <div class="col-xl-12">
                            <label for="confirm-password" class="form-label text-default">Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" class="form-control form-control-lg @error('password_confirmation') is-invalid @enderror" id="confirm-password" name="password_confirmation" placeholder="confirm password">
                                <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('confirm-password',this)" id="button-addon22"><i class="ri-eye-off-line align-middle"></i></a>
                            </div>
                            @error('password_confirmation')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary">Change Password</button>
                    </div>
                </form>
            </div>
            <div class="card-footer">
                <div class="text-center">
                    <a href="{{ route('profile.index') }}" class="text-primary">Back to Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>
<!--End::row-1 -->

@endsection

@section('scripts')

<!-- Show Password JS -->
<script src="{{asset('build/assets/show-password.js')}}"></script>

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