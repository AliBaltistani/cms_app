<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\Admin\GoalsController;
use App\Http\Controllers\Admin\WorkoutController;
use App\Http\Controllers\Admin\WorkoutVideoController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerWebController;

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
 * Protected Routes - Requires Authentication
 * Role-based access control with middleware
 */
Route::middleware('auth')->group(function () {
    
    // Main Dashboard Route - Redirects based on user role
    Route::get('/dashboard', function () {
        $user = Auth::user();
        
        switch ($user->role) {
            case 'admin':
                return redirect()->route('admin.dashboard');
            case 'trainer':
                return redirect()->route('trainer.dashboard');
            case 'client':
                // Direct redirect to avoid middleware conflicts
                return redirect('/client/dashboard');
            default:
                return redirect()->route('profile.index');
        }
    })->name('dashboard');


    // Common User Profile Routes (Available to all authenticated users)
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

    /**
     * ADMIN ROUTES - Admin Role Required
     * System administration and management
     */
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Admin Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/users', [AdminDashboardController::class, 'users'])->name('admin.users');
        Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');
        
        
         Route::prefix('profile')->group(function () {
            Route::get('/', [UserProfileController::class, 'index'])->name('admin.profile');
            Route::get('/edit', [UserProfileController::class, 'edit'])->name('admin.profile.edit');
            Route::post('/update', [UserProfileController::class, 'update'])->name('admin.profile.update');
            Route::get('/change-password', [UserProfileController::class, 'showChangePasswordForm'])->name('admin.profile.change-password');
            Route::post('/change-password', [UserProfileController::class, 'changePassword'])->name('admin.profile.password.update');
            Route::post('/delete-image', [UserProfileController::class, 'deleteProfileImage'])->name('admin.profile.delete-image');
            Route::get('/settings', [UserProfileController::class, 'settings'])->name('admin.profile.settings');
            Route::get('/activity-log', [UserProfileController::class, 'activityLog'])->name('admin.profile.activity-log');
        });
    
        // Goals Management
        Route::prefix('goals')->group(function () {
            Route::get('/', [GoalsController::class, 'index'])->name('goals.index');
            Route::get('/create', [GoalsController::class, 'create'])->name('goals.create');
            Route::post('/store', [GoalsController::class, 'store'])->name('goals.store');
            Route::get('/show/{id}', [GoalsController::class, 'show'])->name('goals.show');
            Route::get('/edit/{id}', [GoalsController::class, 'edit'])->name('goals.edit');
            Route::post('/update/{id}', [GoalsController::class, 'update'])->name('goals.update');
            Route::delete('/destroy/{id}', [GoalsController::class, 'delete'])->name('goals.destroy');
        });

        // Workouts Management
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
            
        // Additional Workout Routes
        Route::post('workouts/{workout}/duplicate', [WorkoutController::class, 'duplicate'])->name('workouts.duplicate');
        Route::patch('workouts/{workout}/toggle-status', [WorkoutController::class, 'toggleStatus'])->name('workouts.toggle-status');
        Route::patch('workouts/{workout}/videos/reorder', [WorkoutVideoController::class, 'reorder'])->name('workout-videos.reorder');
    });

    /**
     * CLIENT ROUTES - Client Role Required
     * Client dashboard and personal management
     */
    Route::middleware('client')->prefix('client')->group(function () {
        // Client Dashboard
        Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('client.dashboard');
        Route::get('/goals', [ClientDashboardController::class, 'goals'])->name('client.goals');
        Route::get('/testimonials', [ClientDashboardController::class, 'testimonials'])->name('client.testimonials');
        Route::get('/trainers', [ClientDashboardController::class, 'trainers'])->name('client.trainers');
        
         Route::prefix('profile')->group(function () {
            Route::get('/', [UserProfileController::class, 'index'])->name('client.profile');
            Route::get('/edit', [UserProfileController::class, 'edit'])->name('client.profile.edit');
            Route::post('/update', [UserProfileController::class, 'update'])->name('client.profile.update');
            Route::get('/change-password', [UserProfileController::class, 'showChangePasswordForm'])->name('client.profile.change-password');
            Route::post('/change-password', [UserProfileController::class, 'changePassword'])->name('client.profile.password.update');
            Route::post('/delete-image', [UserProfileController::class, 'deleteProfileImage'])->name('client.profile.delete-image');
            Route::get('/settings', [UserProfileController::class, 'settings'])->name('client.profile.settings');
            Route::get('/activity-log', [UserProfileController::class, 'activityLog'])->name('client.profile.activity-log');
        });

        // Client Goals Management
        Route::prefix('goals')->group(function () {
            Route::get('/create', [GoalsController::class, 'create'])->name('client.goals.create');
            Route::post('/store', [GoalsController::class, 'store'])->name('client.goals.store');
            Route::get('/edit/{id}', [GoalsController::class, 'edit'])->name('client.goals.edit');
            Route::post('/update/{id}', [GoalsController::class, 'update'])->name('client.goals.update');
            Route::delete('/destroy/{id}', [GoalsController::class, 'delete'])->name('client.goals.destroy');
        });
        
        // Client Testimonial Management
        Route::prefix('testimonials')->group(function () {
            Route::post('/store', [ClientDashboardController::class, 'storeTestimonial'])->name('client.testimonials.store');
            Route::get('/{id}', [ClientDashboardController::class, 'showTestimonial'])->name('client.testimonials.show');
            Route::put('/{id}', [ClientDashboardController::class, 'updateTestimonial'])->name('client.testimonials.update');
            Route::delete('/{id}', [ClientDashboardController::class, 'destroyTestimonial'])->name('client.testimonials.destroy');
        });
    });

    /**
     * TRAINER ROUTES - Trainer Role Required
     * Trainer dashboard and profile management
     */
    Route::middleware('trainer')->prefix('trainer')->group(function () {
        // Trainer Dashboard
        Route::get('/dashboard', [TrainerDashboardController::class, 'index'])->name('trainer.dashboard');
        Route::get('/certifications', [TrainerDashboardController::class, 'certifications'])->name('trainer.certifications');
        Route::get('/testimonials', [TrainerDashboardController::class, 'testimonials'])->name('trainer.testimonials');
        Route::get('/profile', [TrainerDashboardController::class, 'profile'])->name('trainer.profile');
        Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('trainer.profile.edit');
        
        // Certification Management Routes for Trainers
        Route::prefix('certifications')->group(function () {
            Route::post('/', [TrainerDashboardController::class, 'storeCertification'])->name('trainer.certifications.store');
            Route::get('/{id}', [TrainerDashboardController::class, 'showCertification'])->name('trainer.certifications.show');
            Route::put('/{id}', [TrainerDashboardController::class, 'updateCertification'])->name('trainer.certifications.update');
            Route::delete('/{id}', [TrainerDashboardController::class, 'destroyCertification'])->name('trainer.certifications.destroy');
        });
        
        // Testimonial Reaction Routes for Trainers
        Route::prefix('testimonials')->group(function () {
            Route::post('/{id}/like', [TrainerDashboardController::class, 'likeTestimonial'])->name('trainer.testimonials.like');
            Route::post('/{id}/dislike', [TrainerDashboardController::class, 'dislikeTestimonial'])->name('trainer.testimonials.dislike');
        });
    });

    /**
     * PUBLIC TRAINER ROUTES - Available to all authenticated users
     * Trainer profiles, certifications, and testimonials
     */
    Route::prefix('trainers')->group(function () {
        // Public trainer listing and profile viewing
        Route::get('/', [TrainerWebController::class, 'index'])->name('trainers.index');
        Route::get('/{id}', [TrainerWebController::class, 'show'])->name('trainers.show');
        
        // Trainer profile management (only trainers can update their own profile)
        Route::get('/{id}/edit', [TrainerWebController::class, 'edit'])->name('trainers.edit');
        Route::put('/{id}', [TrainerWebController::class, 'update'])->name('trainers.update');
        Route::delete('/{id}/image', [TrainerWebController::class, 'deleteImage'])->name('trainers.delete-image');
        
        // Certification management (only trainers can add certifications to their profile)
        Route::get('/{id}/certifications/create', [TrainerWebController::class, 'createCertification'])->name('trainers.certifications.create');
        Route::post('/{id}/certifications', [TrainerWebController::class, 'storeCertification'])->name('trainers.certifications.store');
        Route::delete('/{id}/certifications/{certificationId}', [TrainerWebController::class, 'deleteCertification'])->name('trainers.certifications.destroy');
        
        // Testimonial management (only clients can add testimonials for trainers)
        Route::get('/{id}/testimonials/create', [TrainerWebController::class, 'createTestimonial'])->name('trainers.testimonials.create');
        Route::post('/{id}/testimonials', [TrainerWebController::class, 'storeTestimonial'])->name('trainers.testimonials.store');
    });
});



