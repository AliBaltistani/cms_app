<?php

/**
 * Authentication Language Lines
 * 
 * The following language lines are used during authentication for various
 * messages that we need to display to the user. You are free to modify
 * these language lines according to your application's requirements.
 * 
 * @package     Laravel CMS App
 * @category    Language Files
 * @author      Go Globe CMS Team
 * @since       1.0.0
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /*
    |--------------------------------------------------------------------------
    | Custom Authentication Messages
    |--------------------------------------------------------------------------
    |
    | Custom messages for the Go Globe CMS authentication system
    |
    */

    'login_success' => 'Welcome back! You have been successfully logged in.',
    'logout_success' => 'You have been successfully logged out.',
    'registration_success' => 'Your account has been created successfully.',
    'registration_failed' => 'Registration failed. Please try again.',
    'invalid_credentials' => 'Invalid email or password. Please check your credentials and try again.',
    'account_locked' => 'Your account has been temporarily locked due to too many failed login attempts.',
    'session_expired' => 'Your session has expired. Please log in again.',
    'unauthorized' => 'You are not authorized to access this resource.',
    'email_not_verified' => 'Please verify your email address before continuing.',
    'password_reset_sent' => 'Password reset link has been sent to your email address.',
    'password_reset_success' => 'Your password has been reset successfully.',
    'password_reset_failed' => 'Failed to reset password. Please try again.',

    /*
    |--------------------------------------------------------------------------
    | Validation Messages
    |--------------------------------------------------------------------------
    |
    | Custom validation messages for authentication forms
    |
    */

    'validation' => [
        'email_required' => 'Email address is required.',
        'email_invalid' => 'Please enter a valid email address.',
        'email_unique' => 'This email address is already registered.',
        'password_required' => 'Password is required.',
        'password_min' => 'Password must be at least 8 characters long.',
        'password_confirmed' => 'Password confirmation does not match.',
        'name_required' => 'Full name is required.',
        'name_min' => 'Name must be at least 2 characters long.',
        'terms_required' => 'You must accept the terms and conditions.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Messages
    |--------------------------------------------------------------------------
    |
    | Messages related to security and account protection
    |
    */

    'security' => [
        'login_detected' => 'New login detected from :ip at :time.',
        'suspicious_activity' => 'Suspicious activity detected on your account.',
        'password_changed' => 'Your password has been changed successfully.',
        'two_factor_enabled' => 'Two-factor authentication has been enabled.',
        'two_factor_disabled' => 'Two-factor authentication has been disabled.',
    ],

];