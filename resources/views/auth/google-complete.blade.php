@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'bg-white';
@endphp

@section('styles')
@endsection

@section('content')
    <div class="row authentication authentication-cover-main mx-0">
        <div class="col-xxl-9 col-xl-9">
            <div class="row justify-content-center align-items-center h-100">
                <div class="col-xxl-4 col-xl-5 col-lg-6 col-md-6 col-sm-8 col-12">
                    <div class="card custom-card border-0 shadow-none my-4">
                        <div class="card-body">
                            <div>
                                <div class="d-flex justify-content-center align-items-bottom mb-4">
                                    <img src="{{ asset('build/assets/images/light-logo.svg') }}" alt="Logo" class="img-fluid" width="50">
                                    <div class="ms-3">
                                        <h4 class="mb-1 fw-semibold">Complete Your Profile</h4>
                                        <p class="mb-0 text-muted fw-normal">Add your phone to finish signup</p>
                                    </div>
                                </div>

                                @if(session('status'))
                                    <div class="alert alert-info">{{ session('status') }}</div>
                                @endif
                                @if(session('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif

                                <form method="POST" action="{{ route('auth.google.complete.submit') }}">
                                    @csrf
                                    <div class="row gy-3 gx-3">
                                        <div class="col-xl-12">
                                            <label for="role" class="form-label text-default">Role<span class="text-danger">*</span></label>
                                            <select name="role" id="role" class="form-control @error('role') is-invalid @enderror" required>
                                                <option value="">Select role</option>
                                                <option value="client" {{ old('role') == 'client' ? 'selected' : '' }}>Client</option>
                                                <option value="trainer" {{ old('role') == 'trainer' ? 'selected' : '' }}>Trainer</option>
                                            </select>
                                            @error('role')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-xl-12">
                                            <label class="form-label text-default">Name</label>
                                            <input type="text" class="form-control" value="{{ $pending['name'] ?? '' }}" readonly>
                                        </div>
                                        <div class="col-xl-12">
                                            <label class="form-label text-default">Email</label>
                                            <input type="email" class="form-control" value="{{ $pending['email'] ?? '' }}" readonly>
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="phone" class="form-label text-default">Phone<span class="text-danger">*</span></label>
                                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Enter phone number" value="{{ old('phone') }}" required>
                                            @error('phone')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="d-grid mt-3">
                                        <button type="submit" class="btn btn-primary">Finish Signup</button>
                                    </div>
                                </form>
                                <div class="text-center mt-3 fw-medium">
                                    Already registered? <a href="{{ route('login') }}" class="text-primary">Login Here</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
            <div class="authentication-cover overflow-hidden">
                <div class="authentication-cover-background">
                    <img src="{{ asset('build/assets/images/media/backgrounds/9.png') }}" alt="">
                </div>
                <div class="authentication-cover-content">
                    <div class="p-5">
                        <h3 class="fw-semibold lh-base">Welcome to Dashboard</h3>
                        <p class="mb-0 text-muted fw-medium">Manage your website and content with ease using our powerful admin tools.</p>
                    </div>
                    <div>
                        <img src="{{ asset('build/assets/images/media/main-background.svg') }}" style="width: 100%; height: 250px;" alt="" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection