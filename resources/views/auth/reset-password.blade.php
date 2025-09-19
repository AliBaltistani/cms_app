@extends('layouts.custom-master')

@php
// Passing the bodyClass variable from the view to the layout
$bodyClass = 'bg-white';
@endphp

@section('styles')
<style>
.password-strength {
    height: 4px;
    border-radius: 2px;
    margin-top: 5px;
    transition: all 0.3s ease;
}
.strength-weak { background-color: #dc3545; }
.strength-medium { background-color: #ffc107; }
.strength-strong { background-color: #28a745; }
.password-requirements {
    font-size: 12px;
}
.requirement-met {
    color: #28a745;
}
.requirement-unmet {
    color: #dc3545;
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
                                         <h4 class="mb-1 fw-semibold">Reset Password</h4>
                                         <p class=" mb-0  text-muted fw-normal">Create your new secure password</p>
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

                                <!-- Email Display -->
                                <div class="text-center mb-4">
                                    <div class="alert alert-success d-flex align-items-center">
                                        <i class="ri-shield-check-line me-2"></i>
                                        <span>OTP Verified for: <strong>{{ session('password_reset_email') }}</strong></span>
                                    </div>
                                </div>

                                 <form method="POST" action="{{ route('password.update') }}" id="resetForm">
                    @csrf
                                <div class="row gy-3">
                                    <div class="col-xl-12">
                                        <label for="password" class="form-label text-default">New Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" placeholder="Enter new password" name="password" required>
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('password',this)" id="button-addon2"><i class="ri-eye-off-line align-middle"></i></a>
                                            @error('password')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Password Strength Indicator -->
                                        <div class="password-strength" id="passwordStrength"></div>
                                        <div class="mt-2">
                                            <small class="text-muted" id="strengthText">Password strength: <span id="strengthLevel">None</span></small>
                                        </div>
                                        
                                        <!-- Password Requirements -->
                                        <div class="password-requirements mt-2">
                                            <div class="requirement-unmet" id="req-length">
                                                <i class="ri-close-circle-line"></i> At least 8 characters
                                            </div>
                                            <div class="requirement-unmet" id="req-uppercase">
                                                <i class="ri-close-circle-line"></i> One uppercase letter
                                            </div>
                                            <div class="requirement-unmet" id="req-lowercase">
                                                <i class="ri-close-circle-line"></i> One lowercase letter
                                            </div>
                                            <div class="requirement-unmet" id="req-number">
                                                <i class="ri-close-circle-line"></i> One number
                                            </div>
                                            <div class="requirement-unmet" id="req-special">
                                                <i class="ri-close-circle-line"></i> One special character
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-12">
                                        <label for="password_confirmation" class="form-label text-default">Confirm New Password</label>
                                        <div class="position-relative">
                                            <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" placeholder="Confirm new password" name="password_confirmation" required>
                                            <a href="javascript:void(0);" class="show-password-button text-muted" onclick="createpassword('password_confirmation',this)" id="button-addon3"><i class="ri-eye-off-line align-middle"></i></a>
                                            @error('password_confirmation')
                                                <div class="invalid-feedback">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>
                                        
                                        <!-- Password Match Indicator -->
                                        <div class="mt-2" id="passwordMatch" style="display: none;">
                                            <small class="text-muted">
                                                <span id="matchText"></span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary" id="resetBtn">
                                        <i class="ri-lock-password-line me-2"></i>Reset Password
                                    </button>
                                </div>
                            </form>
                                
                                <div class="text-center mt-4">
                                    <div class="d-flex justify-content-center align-items-center">
                                        <i class="ri-arrow-left-line me-2"></i>
                                        <a href="{{ route('login') }}" class="text-primary fw-medium">Back to Login</a>
                                    </div>
                                </div>

                                <!-- Security Tips -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="fw-semibold mb-2"><i class="ri-shield-check-line text-secondary me-2"></i>Security Tips</h6>
                                    <ul class="list-unstyled mb-0 small text-muted">
                                        <li><i class="ri-check-line text-secondary me-1"></i> Use a unique password you haven't used before</li>
                                        <li><i class="ri-check-line text-secondary me-1"></i> Avoid using personal information</li>
                                        <li><i class="ri-check-line text-secondary me-1"></i> Consider using a password manager</li>
                                        <li><i class="ri-check-line text-secondary me-1"></i> Keep your password secure and private</li>
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
                    </div>--}}
                    <div class="authentication-cover-background">
                        <img src="{{asset('build/assets/images/media/backgrounds/9.png')}}" alt="">
                    </div>
                    <div class="authentication-cover-content">
                        <div class="p-5">
                            <h3 class="fw-semibold lh-base">Secure Your Account</h3>
                            <p class="mb-0 text-muted fw-medium">Create a strong password to protect your account from unauthorized access.</p>
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
	
        <!-- Show Password JS -->
        <script src="{{asset('build/assets/show-password.js')}}"></script>
        
        <script>
        // Password Strength and Validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const strengthIndicator = document.getElementById('passwordStrength');
            const strengthLevel = document.getElementById('strengthLevel');
            const resetBtn = document.getElementById('resetBtn');
            const passwordMatch = document.getElementById('passwordMatch');
            const matchText = document.getElementById('matchText');
            
            // Password requirements elements
            const requirements = {
                length: document.getElementById('req-length'),
                uppercase: document.getElementById('req-uppercase'),
                lowercase: document.getElementById('req-lowercase'),
                number: document.getElementById('req-number'),
                special: document.getElementById('req-special')
            };
            
            function checkPasswordStrength(password) {
                let score = 0;
                const checks = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /[0-9]/.test(password),
                    special: /[^A-Za-z0-9]/.test(password)
                };
                
                // Update requirement indicators
                Object.keys(checks).forEach(key => {
                    const element = requirements[key];
                    if (checks[key]) {
                        element.className = 'requirement-met';
                        element.innerHTML = element.innerHTML.replace('ri-close-circle-line', 'ri-check-circle-line');
                        score++;
                    } else {
                        element.className = 'requirement-unmet';
                        element.innerHTML = element.innerHTML.replace('ri-check-circle-line', 'ri-close-circle-line');
                    }
                });
                
                // Update strength indicator
                strengthIndicator.style.width = (score / 5) * 100 + '%';
                
                if (score < 3) {
                    strengthIndicator.className = 'password-strength strength-weak';
                    strengthLevel.textContent = 'Weak';
                    strengthLevel.className = 'text-danger';
                } else if (score < 5) {
                    strengthIndicator.className = 'password-strength strength-medium';
                    strengthLevel.textContent = 'Medium';
                    strengthLevel.className = 'text-warning';
                } else {
                    strengthIndicator.className = 'password-strength strength-strong';
                    strengthLevel.textContent = 'Strong';
                    strengthLevel.className = 'text-success';
                }
                
                return score >= 4; // Require at least 4 out of 5 criteria
            }
            
            function checkPasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (confirmPassword.length > 0) {
                    passwordMatch.style.display = 'block';
                    if (password === confirmPassword) {
                        matchText.innerHTML = '<i class="ri-check-circle-line text-success"></i> Passwords match';
                        matchText.className = 'text-success';
                        return true;
                    } else {
                        matchText.innerHTML = '<i class="ri-close-circle-line text-danger"></i> Passwords do not match';
                        matchText.className = 'text-danger';
                        return false;
                    }
                } else {
                    passwordMatch.style.display = 'none';
                    return false;
                }
            }
            
            function updateSubmitButton() {
                const isPasswordStrong = checkPasswordStrength(passwordInput.value);
                const doPasswordsMatch = checkPasswordMatch();
                
                resetBtn.disabled = !(isPasswordStrong && doPasswordsMatch && passwordInput.value.length >= 8);
            }
            
            // Event listeners
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
                if (confirmPasswordInput.value.length > 0) {
                    checkPasswordMatch();
                }
                updateSubmitButton();
            });
            
            confirmPasswordInput.addEventListener('input', function() {
                checkPasswordMatch();
                updateSubmitButton();
            });
            
            // Form submission validation
            document.getElementById('resetForm').addEventListener('submit', function(e) {
                const isPasswordStrong = checkPasswordStrength(passwordInput.value);
                const doPasswordsMatch = checkPasswordMatch();
                
                if (!isPasswordStrong) {
                    e.preventDefault();
                    alert('Please create a stronger password that meets all requirements.');
                    return false;
                }
                
                if (!doPasswordsMatch) {
                    e.preventDefault();
                    alert('Passwords do not match. Please check and try again.');
                    return false;
                }
            });
        });
        </script>

@endsection