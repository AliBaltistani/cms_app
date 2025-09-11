<!DOCTYPE html>
<html lang="en" dir="ltr" data-nav-layout="vertical" data-theme-mode="light" data-header-styles="transparent" data-width="fullwidth" data-menu-styles="transparent" data-page-style="flat">

<head>
    <!-- Meta Data -->
    <meta charset="UTF-8">
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="Description" content="Laravel Bootstrap Responsive Admin Web Dashboard Template">
    <meta name="Author" content="Go Globe CMS Team">
    <meta name="keywords" content="laravel, laravel admin panel, laravel dashboard, bootstrap dashboard, bootstrap admin panel, vite laravel, admin dashboard, admin panel in laravel, admin dashboard ui, laravel admin, admin panel template, laravel framework, dashboard, admin dashboard template, laravel template.">

    <!-- Title-->
    <title>Register - Go Globe CMS</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{asset('build/assets/images/brand-logos/favicon.ico')}}" type="image/x-icon">

    <!-- Main Theme Js -->
    <script src="{{asset('build/assets/main.js')}}"></script>

    <!-- ICONS CSS -->
    <link href="{{asset('build/assets/icon-fonts/icons.css')}}" rel="stylesheet">

    @include('layouts.components.styles')
  
    <!-- APP CSS & APP SCSS -->
    @vite(['resources/sass/app.scss'])

    <!-- Authentication Styles -->
    @vite('resources/assets/js/authentication-main.js')

    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px 0;
        }
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            margin: 20px;
        }
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .auth-body {
            padding: 40px 30px;
        }
        .form-control {
            border-radius: 8px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px 30px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .password-strength {
            font-size: 12px;
            margin-top: 5px;
        }
        .strength-weak { color: #dc3545; }
        .strength-medium { color: #ffc107; }
        .strength-strong { color: #28a745; }
    </style>
</head>

<body class="">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Header -->
            <div class="auth-header">
                <h2 class="mb-1">Create Account</h2>
                <p class="mb-0 opacity-75">Join us today and get started</p>
            </div>

            <!-- Body -->
            <div class="auth-body">
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

                <!-- Registration Form -->
                <form method="POST" action="{{ route('register') }}" id="registrationForm">
                    @csrf

                    <!-- Full Name Field -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <input id="name" type="text" 
                               class="form-control @error('name') is-invalid @enderror" 
                               name="name" 
                               value="{{ old('name') }}" 
                               required 
                               autocomplete="name" 
                               autofocus
                               placeholder="Enter your full name">
                        @error('name')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Email Field -->
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input id="email" type="email" 
                               class="form-control @error('email') is-invalid @enderror" 
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autocomplete="email"
                               placeholder="Enter your email address">
                        @error('email')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Password Field -->
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Password</label>
                        <input id="password" type="password" 
                               class="form-control @error('password') is-invalid @enderror" 
                               name="password" 
                               required 
                               autocomplete="new-password"
                               placeholder="Enter your password"
                               onkeyup="checkPasswordStrength()">
                        <div id="passwordStrength" class="password-strength"></div>
                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-4">
                        <label for="password-confirm" class="form-label fw-semibold">Confirm Password</label>
                        <input id="password-confirm" type="password" 
                               class="form-control" 
                               name="password_confirmation" 
                               required 
                               autocomplete="new-password"
                               placeholder="Confirm your password">
                    </div>

                    <!-- Terms and Conditions -->
                    <div class="mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="terms" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the <a href="#" class="text-decoration-none">Terms and Conditions</a>
                            </label>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mb-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="ri-user-add-line me-2"></i>Create Account
                        </button>
                    </div>

                    <!-- Additional Links -->
                    <div class="text-center">
                        <p class="mb-0">
                            Already have an account? 
                            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold">Sign In</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    @include('layouts.components.scripts')

    <!-- App JS-->
    @vite('resources/js/app.js')

    <!-- Password Strength Checker -->
    <script>
        /**
         * Check password strength and provide visual feedback
         */
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.innerHTML = '';
                return;
            }
            
            let strength = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) {
                strength += 1;
            } else {
                feedback.push('At least 8 characters');
            }
            
            // Uppercase check
            if (/[A-Z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('One uppercase letter');
            }
            
            // Lowercase check
            if (/[a-z]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('One lowercase letter');
            }
            
            // Number check
            if (/[0-9]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('One number');
            }
            
            // Special character check
            if (/[^A-Za-z0-9]/.test(password)) {
                strength += 1;
            } else {
                feedback.push('One special character');
            }
            
            // Display strength
            let strengthText = '';
            let strengthClass = '';
            
            if (strength < 3) {
                strengthText = 'Weak';
                strengthClass = 'strength-weak';
            } else if (strength < 5) {
                strengthText = 'Medium';
                strengthClass = 'strength-medium';
            } else {
                strengthText = 'Strong';
                strengthClass = 'strength-strong';
            }
            
            strengthDiv.innerHTML = `<span class="${strengthClass}">Password Strength: ${strengthText}</span>`;
            
            if (feedback.length > 0) {
                strengthDiv.innerHTML += `<br><small class="text-muted">Missing: ${feedback.join(', ')}</small>`;
            }
        }

        /**
         * Form validation before submission
         */
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password-confirm').value;
            const terms = document.getElementById('terms').checked;
            
            // Check if passwords match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            // Check if terms are accepted
            if (!terms) {
                e.preventDefault();
                alert('Please accept the Terms and Conditions!');
                return false;
            }
            
            return true;
        });
    </script>

</body>

</html>