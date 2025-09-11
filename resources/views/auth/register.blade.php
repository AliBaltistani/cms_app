
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
                        <div class="card custom-card border-0  shadow-none my-4">
                            <div class="card-body">
                                <div>
                                    <div class="d-flex justify-content-center align-items-bottom mb-4">
                                        <img src="{{ asset('build/assets/images/light-logo.svg') }}" alt="Logo" class="img-fluid" width="50">
                                       <div class="ms-3">
                                        <h4 class="mb-1 fw-semibold">Create your account</h4>
                                        <p class="mb-0 text-muted fw-normal">Please enter valid credentials</p>
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
                                </div>
                                 <!-- Registration Form -->
                <form method="POST" action="{{ route('register') }}" id="registrationForm">
                    @csrf
                                <div class="row gy-3">
                                    <div class="col-xl-12"> 
                                        <label for="name" class="form-label text-default @error('name') is-invalid @enderror">User Name</label> 
                                        <input type="text" class="form-control" id="name" placeholder="Enter User Name" name="name" value="{{ old('name') }}"  autocomplete="off"  required autofocus> 
                                    </div>
                                    <div class="col-xl-12">
                                        <label for="email" class="form-label text-default @error('email') is-invalid @enderror">Email</label>
                                        <input type="text" class="form-control" id="email" placeholder="Enter Email" name="email" value="{{ old('email') }}" required  autocomplete="off">
                                    </div>
                                    <div class="col-xl-12"> 
                                        <label for="phone" class="form-label text-default @error('phone') is-invalid @enderror">Phone Number</label> 
                                        <input type="text" class="form-control" id="phone" placeholder="Enter Phone Number" name="phone" value="{{ old('phone') }}"  autocomplete="off"  required autofocus> 
                                    </div>
                                    <div class="col-xl-12 mb-2">
                                        <label for="password" class="form-label text-default d-block">Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control" id="password" placeholder="Enter Password" name="password" required autocomplete="off">
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('signin-password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                                         <div id="passwordStrength" class="password-strength"></div>
                                            @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                    </div>

                                    
                                </div>
                                <div class="d-grid mt-3">
                                    <button type="submit" class="btn btn-primary">Create Account</button>
                                </div>
                </form>
                                <div class="text-center my-3 authentication-barrier">
                                    <span class="op-4 fs-13">OR</span>
                                </div>
                                <div class="d-grid mb-3">
                                    <button class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill mb-3">
                                        <span class="avatar avatar-xs">
                                            <img src="{{asset('build/assets/images/media/apps/google.png')}}" alt="">
                                        </span>
                                        <span class="lh-1 ms-2 fs-13 text-default fw-medium">Signup with Google</span>
                                    </button>
                                    <button class="btn btn-white btn-w-lg border d-flex align-items-center justify-content-center flex-fill">
                                        <span class="avatar avatar-xs flex-shrink-0">
                                            <img src="{{asset('build/assets/images/media/apps/facebook.png')}}" alt="">
                                        </span>
                                        <span class="lh-1 ms-2 fs-13 text-default fw-medium">Signup with Facebook</span>
                                    </button>
                                </div>
                                <div class="text-center mt-3 fw-medium">
                                    Already have a account? <a  href="{{ route('login') }}"  class="text-primary">Login Here</a>
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
                            <h3 class="fw-semibold lh-base">Welcome to Dashboard</h3>
                            <p class="mb-0 text-muted fw-medium">Manage your website and content with ease using our powerful admin tools.</p>
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
        
        <!-- Show Password JS -->
        <script src="{{asset('build/assets/show-password.js')}}"></script>

@endsection
