<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\GoalsController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutVideoController;

/**
 * Public Routes
 * Routes accessible without authentication
 */
Route::get('/', function () {
    return view('welcome');
});

/**
 * Authentication Routes
 * Handle user login, registration, and logout
 */
Route::middleware('guest')->group(function () {
    // Login Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    // Registration Routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
    
    // Password Reset Routes
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOTP'])->name('password.email');
    Route::get('/verify-otp', [ForgotPasswordController::class, 'showOTPForm'])->name('password.otp.form');
    Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOTP'])->name('password.otp.verify');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
    Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('password.update');
    Route::post('/resend-otp', [ForgotPasswordController::class, 'resendOTP'])->name('password.otp.resend');
});

// Logout Route (requires authentication)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/**
 * Protected Dashboard Routes
 * Requires user authentication
 */
Route::middleware('auth')->group(function () {
    // Main Dashboard - redirect here after successful login
    Route::get('/dashboard', [DashboardsController::class, 'index'])->name('dashboard');

    // User Profile Routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [UserProfileController::class, 'index'])->name('profile.index');
        Route::get('/edit', [UserProfileController::class, 'edit'])->name('profile.edit');
        Route::post('/update', [UserProfileController::class, 'update'])->name('profile.update');
        Route::get('/change-password', [UserProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
        Route::post('/change-password', [UserProfileController::class, 'changePassword'])->name('profile.password.update');
        Route::post('/delete-image', [UserProfileController::class, 'deleteProfileImage'])->name('profile.delete-image');
        Route::get('/settings', [UserProfileController::class, 'settings'])->name('profile.settings');
        Route::get('/activity-log', [UserProfileController::class, 'activityLog'])->name('profile.activity-log');
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/', [DashboardsController::class, 'index']);

        // Add more admin-specific routes here
          Route::prefix('goals')->group(function () {
             Route::get('/index', [GoalsController::class, 'index'])->name('goals.index');
             Route::get('/create', [GoalsController::class, 'create'])->name('goals.create');
             Route::post('/store', [GoalsController::class, 'store'])->name('goals.store');
             Route::post('/show{id}', [GoalsController::class, 'show'])->name('goals.show');
             Route::get('/edit/{id}', [GoalsController::class, 'edit'])->name('goals.edit');
             Route::post('/update/{id}', [GoalsController::class, 'update'])->name('goals.update');
             Route::delete('/destroy/{id}', [GoalsController::class, 'delete'])->name('goals.destroy');
          });

          // Add more admin-specific routes here
          Route::prefix('workouts')->group(function () {
             Route::get('/index', [WorkoutController::class, 'index'])->name('workouts.index');
             Route::get('/create', [WorkoutController::class, 'create'])->name('workouts.create');
             Route::post('/store', [WorkoutController::class, 'store'])->name('workouts.store');
             Route::post('/show{id}', [WorkoutController::class, 'show'])->name('workouts.show');
             Route::get('/edit/{id}', [WorkoutController::class, 'edit'])->name('workouts.edit');
             Route::post('/update/{id}', [WorkoutController::class, 'update'])->name('workouts.update');
             Route::delete('/destroy/{id}', [WorkoutController::class, 'delete'])->name('workouts.destroy');
          });


          // Web routes for admin panel or frontend
            Route::resource('workouts', WorkoutController::class);
            Route::resource('workouts.videos', WorkoutVideoController::class)
                ->except(['index'])
                ->names([
                    'create' => 'workout-videos.create',
                    'store' => 'workout-videos.store',
                    'show' => 'workout-videos.show',
                    'edit' => 'workout-videos.edit',
                    'update' => 'workout-videos.update',
                    'destroy' => 'workout-videos.destroy',
                ]);
                
            // Additional routes
            Route::post('workouts/{workout}/duplicate', [WorkoutController::class, 'duplicate'])->name('workouts.duplicate');
            Route::patch('workouts/{workout}/toggle-status', [WorkoutController::class, 'toggleStatus'])->name('workouts.toggle-status');
            Route::patch('workouts/{workout}/videos/reorder', [WorkoutVideoController::class, 'reorder'])->name('workout-videos.reorder');
        

    });
});



