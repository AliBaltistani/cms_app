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
                                         <p class=" mb-0  text-muted fw-normal">Choose your preferred method to receive OTP</p>
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
                                <!-- Reset Method Selection -->
                                <div class="mb-4">
                                    <div class="btn-group w-100" role="group" aria-label="Reset method selection">
                                        <input type="radio" class="btn-check" name="reset_method" id="email_method" value="email" checked>
                                        <label class="btn btn-outline-primary" for="email_method">
                                            <i class="ri-mail-line me-2"></i>Email
                                        </label>
                                        
                                        <input type="radio" class="btn-check" name="reset_method" id="phone_method" value="phone">
                                        <label class="btn btn-outline-primary" for="phone_method">
                                            <i class="ri-phone-line me-2"></i>Phone
                                        </label>
                                    </div>
                                </div>

                                 <form method="POST" action="{{ route('password.email') }}" id="forgotPasswordForm">
                    @csrf
                                <div class="row gy-3">
                                    <!-- Email Input Section -->
                                    <div class="col-xl-12" id="email_section">
                                        <label for="forgot-email" class="form-label text-default">Email Address</label>
                                        <input type="email" class="form-control form-control @error('email') is-invalid @enderror" id="forgot-email" placeholder="Enter your email address" name="email" value="{{ old('email') }}" autofocus>
                                        @error('email')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                        <div class="form-text">
                                            <i class="ri-information-line"></i> We'll send a 6-digit OTP to your email address
                                        </div>
                                    </div>

                                    <!-- Phone Input Section -->
                                    <div class="col-xl-12" id="phone_section" style="display: none;">
                                        <label for="forgot-phone" class="form-label text-default">Phone Number</label>
                                        <input type="tel" class="form-control form-control @error('phone') is-invalid @enderror" id="forgot-phone" placeholder="Enter your phone number" name="phone" value="{{ old('phone') }}">
                                        @error('phone')
                                            <div class="invalid-feedback">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                        <div class="form-text">
                                            <i class="ri-information-line"></i> We'll send a 6-digit OTP to your phone number
                                        </div>
                                    </div>

                                    <!-- Hidden field to track the selected method -->
                                    <input type="hidden" name="type" id="reset_type" value="email">
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary" id="send_otp_btn">
                                        <i class="ri-mail-send-line me-2" id="send_icon"></i><span id="send_text">Send OTP</span>
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
                    {{-- <div class="authentication-cover-logo">
                        <a href="{{url('index')}}">
                        <img src="{{asset('build/assets/images/brand-logos/toggle-logo.png')}}" alt="logo" class="desktop-dark"> 
                        </a>
                    </div> --}}
                    <div class="authentication-cover-background">
                        <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
                    </div>
                    <div class="authentication-cover-content">
                        <div class="">
                            <h3 class="fw-semibold lh-base">Secure Password Reset</h3>
                            <p class="mb-0 text-muted fw-medium">We'll help you regain access to your account safely and securely.</p>
                        </div>
                        <div>
                            <img src="{{asset('build/assets/images/media/main-background.svg')}}" style="width: 100%; height: 250px;" alt="" class="img-fluid">
                        </div>
                    </div>
                </div>
            </div>
        </div>

@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const emailMethod = document.getElementById('email_method');
    const phoneMethod = document.getElementById('phone_method');
    const emailSection = document.getElementById('email_section');
    const phoneSection = document.getElementById('phone_section');
    const resetType = document.getElementById('reset_type');
    const sendIcon = document.getElementById('send_icon');
    const sendText = document.getElementById('send_text');
    const emailInput = document.getElementById('forgot-email');
    const phoneInput = document.getElementById('forgot-phone');

    // Handle method toggle
    function toggleMethod() {
        if (emailMethod.checked) {
            // Show email section, hide phone section
            emailSection.style.display = 'block';
            phoneSection.style.display = 'none';
            resetType.value = 'email';
            sendIcon.className = 'ri-mail-send-line me-2';
            sendText.textContent = 'Send OTP';
            
            // Set required attributes
            emailInput.setAttribute('required', 'required');
            phoneInput.removeAttribute('required');
            
            // Focus on email input
            emailInput.focus();
        } else if (phoneMethod.checked) {
            // Show phone section, hide email section
            emailSection.style.display = 'none';
            phoneSection.style.display = 'block';
            resetType.value = 'phone';
            sendIcon.className = 'ri-phone-line me-2';
            sendText.textContent = 'Send OTP';
            
            // Set required attributes
            phoneInput.setAttribute('required', 'required');
            emailInput.removeAttribute('required');
            
            // Focus on phone input
            phoneInput.focus();
        }
    }

    // Add event listeners
    emailMethod.addEventListener('change', toggleMethod);
    phoneMethod.addEventListener('change', toggleMethod);

    // Initialize with email method selected
    toggleMethod();

    // Handle form submission validation
    document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
        const currentMethod = resetType.value;
        
        if (currentMethod === 'email') {
            const email = emailInput.value.trim();
            if (!email) {
                e.preventDefault();
                emailInput.focus();
                return false;
            }
        } else if (currentMethod === 'phone') {
            const phone = phoneInput.value.trim();
            if (!phone) {
                e.preventDefault();
                phoneInput.focus();
                return false;
            }
        }
    });
});
</script>
@endsection