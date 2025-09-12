@extends('layouts.master')

@section('styles')
<style>
.password-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}
.password-strength {
    height: 6px;
    border-radius: 3px;
    margin-top: 8px;
    transition: all 0.3s ease;
    background-color: #e9ecef;
}
.strength-weak { 
    background: linear-gradient(90deg, #dc3545 0%, #dc3545 33%, #e9ecef 33%);
}
.strength-medium { 
    background: linear-gradient(90deg, #ffc107 0%, #ffc107 66%, #e9ecef 66%);
}
.strength-strong { 
    background: linear-gradient(90deg, #28a745 0%, #28a745 100%);
}
.password-requirements {
    font-size: 13px;
    margin-top: 10px;
}
.requirement-met {
    color: #28a745;
}
.requirement-unmet {
    color: #dc3545;
}
.security-tips {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
}
.form-floating {
    margin-bottom: 1rem;
}
.password-match {
    font-size: 13px;
    margin-top: 5px;
}
</style>
@endsection

@section('content')

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between page-header-breadcrumb flex-wrap gap-2">
    <div>
        <nav>
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('profile.index') }}">Profile</a></li>
                <li class="breadcrumb-item active" aria-current="page">Change Password</li>
            </ol>
        </nav>
        <h1 class="page-title fw-medium fs-18 mb-0">Change Password</h1>
    </div>
    <div class="btn-list">
        <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
            <i class="ri-arrow-left-line me-1"></i>Back to Profile
        </a>
    </div>
</div>
<!-- Page Header Close -->

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

<div class="row justify-content-center">
    <div class="col-xl-6 col-lg-8 col-md-10">
        <div class="password-card">
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i class="ri-lock-password-line" style="font-size: 3rem; color: #667eea;"></i>
                </div>
                <h4 class="fw-semibold mb-2">Change Your Password</h4>
                <p class="text-muted">Create a strong, secure password to protect your account</p>
            </div>

            <form method="POST" action="{{ route('profile.password.update') }}" id="passwordForm">
                @csrf

                <!-- Current Password -->
                <div class="form-floating">
                    <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="currentPassword" name="current_password" placeholder="Current Password" required>
                    <label for="currentPassword"><i class="ri-lock-line me-2"></i>Current Password</label>
                    <div class="position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%);">
                        <button type="button" class="btn btn-link p-0 text-muted" onclick="togglePassword('currentPassword', this)">
                            <i class="ri-eye-off-line"></i>
                        </button>
                    </div>
                    @error('current_password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- New Password -->
                <div class="form-floating">
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="newPassword" name="password" placeholder="New Password" required>
                    <label for="newPassword"><i class="ri-lock-2-line me-2"></i>New Password</label>
                    <div class="position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%);">
                        <button type="button" class="btn btn-link p-0 text-muted" onclick="togglePassword('newPassword', this)">
                            <i class="ri-eye-off-line"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    
                    <!-- Password Strength Indicator -->
                    <div class="password-strength" id="passwordStrength"></div>
                    <div class="mt-2">
                        <small class="text-muted">Password strength: <span id="strengthLevel" class="fw-medium">None</span></small>
                    </div>
                    
                    <!-- Password Requirements -->
                    <div class="password-requirements">
                        <div class="requirement-unmet" id="req-length">
                            <i class="ri-close-circle-line"></i> At least 8 characters
                        </div>
                        <div class="requirement-unmet" id="req-uppercase">
                            <i class="ri-close-circle-line"></i> One uppercase letter (A-Z)
                        </div>
                        <div class="requirement-unmet" id="req-lowercase">
                            <i class="ri-close-circle-line"></i> One lowercase letter (a-z)
                        </div>
                        <div class="requirement-unmet" id="req-number">
                            <i class="ri-close-circle-line"></i> One number (0-9)
                        </div>
                        <div class="requirement-unmet" id="req-special">
                            <i class="ri-close-circle-line"></i> One special character (!@#$%^&*)
                        </div>
                    </div>
                </div>

                <!-- Confirm New Password -->
                <div class="form-floating">
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" id="confirmPassword" name="password_confirmation" placeholder="Confirm New Password" required>
                    <label for="confirmPassword"><i class="ri-lock-2-line me-2"></i>Confirm New Password</label>
                    <div class="position-absolute" style="right: 15px; top: 50%; transform: translateY(-50%);">
                        <button type="button" class="btn btn-link p-0 text-muted" onclick="togglePassword('confirmPassword', this)">
                            <i class="ri-eye-off-line"></i>
                        </button>
                    </div>
                    @error('password_confirmation')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror
                    
                    <!-- Password Match Indicator -->
                    <div class="password-match" id="passwordMatch" style="display: none;">
                        <span id="matchText"></span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                        <i class="ri-close-line me-2"></i>Cancel
                    </a>
                    
                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                        <i class="ri-save-line me-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Security Tips -->
    <div class="col-xl-6 col-lg-8 col-md-10">
        <div class="security-tips">
            <h5 class="mb-3"><i class="ri-shield-check-line me-2"></i>Password Security Tips</h5>
            <ul class="list-unstyled mb-0">
                <li class="mb-2"><i class="ri-check-line me-2"></i>Use a unique password you haven't used before</li>
                <li class="mb-2"><i class="ri-check-line me-2"></i>Avoid using personal information like names or birthdays</li>
                <li class="mb-2"><i class="ri-check-line me-2"></i>Consider using a password manager</li>
                <li class="mb-2"><i class="ri-check-line me-2"></i>Don't share your password with anyone</li>
                <li class="mb-2"><i class="ri-check-line me-2"></i>Change your password regularly</li>
                <li class="mb-0"><i class="ri-check-line me-2"></i>Use two-factor authentication when available</li>
            </ul>
        </div>
        
        <!-- Password History Note -->
        <div class="alert alert-info mt-3">
            <h6 class="alert-heading"><i class="ri-information-line me-2"></i>Important Note</h6>
            <p class="mb-0">Your new password must be different from your current password. For security reasons, you cannot reuse recent passwords.</p>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Toggle Password Visibility
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'ri-eye-line';
    } else {
        input.type = 'password';
        icon.className = 'ri-eye-off-line';
    }
}

// Password Strength and Validation
document.addEventListener('DOMContentLoaded', function() {
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    const strengthIndicator = document.getElementById('passwordStrength');
    const strengthLevel = document.getElementById('strengthLevel');
    const submitBtn = document.getElementById('submitBtn');
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
        if (score < 3) {
            strengthIndicator.className = 'password-strength strength-weak';
            strengthLevel.textContent = 'Weak';
            strengthLevel.className = 'fw-medium text-danger';
        } else if (score < 5) {
            strengthIndicator.className = 'password-strength strength-medium';
            strengthLevel.textContent = 'Medium';
            strengthLevel.className = 'fw-medium text-warning';
        } else {
            strengthIndicator.className = 'password-strength strength-strong';
            strengthLevel.textContent = 'Strong';
            strengthLevel.className = 'fw-medium text-success';
        }
        
        return score >= 4; // Require at least 4 out of 5 criteria
    }
    
    function checkPasswordMatch() {
        const password = newPasswordInput.value;
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
        const currentPassword = document.getElementById('currentPassword').value;
        const isPasswordStrong = checkPasswordStrength(newPasswordInput.value);
        const doPasswordsMatch = checkPasswordMatch();
        
        submitBtn.disabled = !(currentPassword.length > 0 && isPasswordStrong && doPasswordsMatch && newPasswordInput.value.length >= 8);
    }
    
    // Event listeners
    newPasswordInput.addEventListener('input', function() {
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
    
    document.getElementById('currentPassword').addEventListener('input', function() {
        updateSubmitButton();
    });
    
    // Form submission validation
    document.getElementById('passwordForm').addEventListener('submit', function(e) {
        const currentPassword = document.getElementById('currentPassword').value;
        const isPasswordStrong = checkPasswordStrength(newPasswordInput.value);
        const doPasswordsMatch = checkPasswordMatch();
        
        if (!currentPassword) {
            e.preventDefault();
            alert('Please enter your current password.');
            return false;
        }
        
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
        
        // Check if new password is same as current (basic client-side check)
        if (currentPassword === newPasswordInput.value) {
            e.preventDefault();
            alert('New password must be different from your current password.');
            return false;
        }
    });
    
    // Auto-hide alerts after 5 seconds
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