@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'bg-white';
@endphp

@section('styles')
<style>
.otp-input {
    width: 50px;
    height: 50px;
    text-align: center;
    font-size: 18px;
    font-weight: bold;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    margin: 0 5px;
}
.otp-input:focus {
       border-color: var(--primary-color);
    box-shadow: 0 2px 6px 0px var(--primary03);
}
.countdown {
    font-size: 14px;
    color: #dc3545;
    font-weight: 500;
}
</style>
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
                                         <h4 class="mb-1 fw-semibold">Verify OTP</h4>
                                         <p class=" mb-0  text-muted fw-normal">
                                            @if(session('password_reset_type') === 'phone')
                                                Enter the 6-digit code sent to your phone
                                            @else
                                                Enter the 6-digit code sent to your email
                                            @endif
                                         </p>
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

                                <!-- Email/Phone Display -->
                                <div class="text-center mb-4">
                                    <p class="mb-2">OTP sent to:</p>
                                    @if(session('password_reset_type') === 'phone')
                                        <strong class="text-primary">{{ session('password_reset_phone') }}</strong>
                                    @else
                                        <strong class="text-primary">{{ session('password_reset_email') }}</strong>
                                    @endif
                                </div>

                                 <form method="POST" action="{{ route('password.otp.verify') }}" id="otpForm">
                    @csrf
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label class="form-label text-default text-center d-block mb-3">Enter 6-Digit OTP</label>
                                        <div class="d-flex justify-content-center mb-3">
                                            <input type="text" class="otp-input" maxlength="1" name="otp1" id="otp1" required>
                                            <input type="text" class="otp-input" maxlength="1" name="otp2" id="otp2" required>
                                            <input type="text" class="otp-input" maxlength="1" name="otp3" id="otp3" required>
                                            <input type="text" class="otp-input" maxlength="1" name="otp4" id="otp4" required>
                                            <input type="text" class="otp-input" maxlength="1" name="otp5" id="otp5" required>
                                            <input type="text" class="otp-input" maxlength="1" name="otp6" id="otp6" required>
                                        </div>
                                        <input type="hidden" name="otp" id="otpHidden">
                                        
                                        <!-- Countdown Timer -->
                                        <div class="text-center mb-3">
                                            <span class="countdown" id="countdown">Time remaining: 15:00</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary" id="verifyBtn">
                                        <i class="ri-shield-check-line me-2"></i>Verify OTP
                                    </button>
                                </div>
                            </form>

                            <!-- Resend OTP -->
                            <div class="text-center mt-4">
                                <p class="mb-2">Didn't receive the code?</p>
                                <form method="POST" action="{{ route('password.otp.resend') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-link p-0 text-primary fw-medium" id="resendBtn">
                                        <i class="ri-refresh-line me-1"></i>Resend OTP
                                    </button>
                                </form>
                            </div>
                                
                                <div class="text-center mt-4">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <i class="ri-arrow-left-line me-2"></i>
                                        <a href="{{ route('password.request') }}" class="text-primary fw-medium">Back to Forgot Password</a>
                                    </div>
                                </div>

                                <!-- Security Information -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="fw-semibold mb-2"><i class="ri-information-line text-secondary me-2"></i>Important Notes</h6>
                                    <ul class="list-unstyled mb-0 small text-muted">
                                        <li><i class="ri-time-line text-warning me-1"></i> OTP expires in 15 minutes</li>
                                        <li><i class="ri-error-warning-line text-danger me-1"></i> Maximum 3 attempts allowed</li>
                                        <li><i class="ri-mail-line text-secondary me-1"></i> Check your spam folder if not received</li>
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
                        <div class="p-5">
                            <h3 class="fw-semibold lh-base">Secure Verification</h3>
                            <p class="mb-0 text-muted fw-medium">Enter the verification code to proceed with password reset.</p>
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
// OTP Input Handling
document.addEventListener('DOMContentLoaded', function() {
    const otpInputs = document.querySelectorAll('.otp-input');
    const otpHidden = document.getElementById('otpHidden');
    const form = document.getElementById('otpForm');
    
    // Auto-focus next input
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
            updateHiddenOTP();
        });
        
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && e.target.value === '' && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
        
        // Only allow numbers
        input.addEventListener('keypress', function(e) {
            if (!/[0-9]/.test(e.key) && e.key !== 'Backspace') {
                e.preventDefault();
            }
        });
    });
    
    // Update hidden OTP field
    function updateHiddenOTP() {
        let otp = '';
        otpInputs.forEach(input => {
            otp += input.value;
        });
        otpHidden.value = otp;
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        updateHiddenOTP();
        if (otpHidden.value.length !== 6) {
            e.preventDefault();
            alert('Please enter all 6 digits of the OTP.');
            return false;
        }
    });
    
    // Countdown Timer (15 minutes)
    let timeLeft = 15 * 60; // 15 minutes in seconds
    const countdownElement = document.getElementById('countdown');
    const resendBtn = document.getElementById('resendBtn');
    
    function updateCountdown() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        countdownElement.textContent = `Time remaining: ${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            countdownElement.textContent = 'OTP Expired';
            countdownElement.classList.add('text-danger');
            resendBtn.style.display = 'inline-block';
            document.getElementById('verifyBtn').disabled = true;
            return;
        }
        
        if (timeLeft <= 60) {
            countdownElement.classList.add('text-danger');
        } else if (timeLeft <= 300) {
            countdownElement.classList.add('text-warning');
        }
        
        timeLeft--;
    }
    
    // Update countdown every second
    const countdownInterval = setInterval(updateCountdown, 1000);
    updateCountdown(); // Initial call
    
    // Focus first input
    otpInputs[0].focus();
});
</script>
@endsection