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
                            <div class="card-body ">
                                <div>

                                    <div class="d-flex justify-content-center align-items-bottom mb-4">
                                        <img src="{{ asset('build/assets/images/light-logo.svg') }}" alt="Logo" class="img-fluid" width="50">
                                       <div class="ms-3">
                                         <h4 class="mb-1 fw-semibold">Forgot Password?</h4>
                                         <p class=" mb-0  text-muted fw-normal">Enter your email to receive OTP</p>
                                       </div>
                                    </div>
                                    

                                      <!-- Display Validation Errors -->
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Display Success Messages -->
                @if (session('success'))
                    <div class="alert alert-success mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Display Status Messages -->
                @if (session('status'))
                    <div class="alert alert-info mb-4">
                        {{ session('status') }}
                    </div>
                @endif
                                </div>
                                 <form method="POST" action="{{ route('password.email') }}">
                    @csrf
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="forgot-email" class="form-label text-default">Email Address</label>
                                        <input type="email" class="form-control form-control @error('email') is-invalid @enderror" id="forgot-email" placeholder="Enter your email address" name="email" value="{{ old('email') }}" required autofocus>
                                     @error('email')
                                        <div class="invalid-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <div class="form-text">
                                        <i class="ri-information-line"></i> We'll send a 6-digit OTP to your email address
                                    </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-mail-send-line me-2"></i>Send OTP
                                    </button>
                                </div>
                            </form>
                                
                                <div class="text-center mt-4">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <i class="ri-arrow-left-line me-2"></i>
                                        <a href="{{ route('login') }}" class="text-primary fw-medium">Back to Login</a>
                                    </div>
                                </div>

                                <!-- Security Information -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="fw-semibold mb-2"><i class="ri-shield-check-line text-secondary me-2"></i>Security Information</h6>
                                    <ul class="list-unstyled mb-0 small text-muted">
                                        <li><i class="ri-check-line text-secondary me-1"></i> OTP expires in 15 minutes</li>
                                        <li><i class="ri-check-line text-secondary me-1"></i> Maximum 3 attempts allowed</li>
                                        <li><i class="ri-check-line text-secondary me-1"></i> Secure email delivery</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xxl-3 col-xl-3 col-lg-12 d-xl-block d-none px-0">
                <div class="authentication-cover overflow-hidden">
                    <div class="authentication-cover-logo">
                        <a href="{{url('index')}}">
                        <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="desktop-dark"> 
                        </a>
                    </div>
                    <div class="authentication-cover-background">
                        <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
                    </div>
                    <div class="authentication-cover-content">
                        <div class="p-5">
                            <h3 class="fw-semibold lh-base">Secure Password Reset</h3>
                            <p class="mb-0 text-muted fw-medium">We'll help you regain access to your account safely and securely.</p>
                        </div>
                        <div>
                            <img src="{{asset('build/assets/images/media/media-72.png')}}" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
	

@endsection