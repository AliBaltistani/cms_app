<?php

/**
 * API Routes for Go Globe CMS Application
 * 
 * Complete API endpoints for authentication, user management, goals, workouts, and workout videos
 * All protected routes require Sanctum authentication token
 * 
 * @package     Laravel CMS App
 * @subpackage  Routes
 * @category    API Routes
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     1.0.0
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Import API Controllers
use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\ApiGoalController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutVideoController;
use App\Http\Controllers\Api\TrainerController;

/**
 * =============================================================================
 * PUBLIC API ROUTES (No Authentication Required)
 * =============================================================================
 */

/**
 * Authentication Routes
 * Handle user registration, login, and password reset
 */
Route::prefix('auth')->group(function () {
    Route::post('/register', [ApiAuthController::class, 'register'])->name('api.auth.register');
    Route::post('/login', [ApiAuthController::class, 'login'])->name('api.auth.login');
    
    // Password Reset Routes
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
    Route::post('/verify-otp', [ApiAuthController::class, 'verifyOTP'])->name('api.auth.verify-otp');
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword'])->name('api.auth.reset-password');
    Route::post('/resend-otp', [ApiAuthController::class, 'resendOTP'])->name('api.auth.resend-otp');
});

/**
 * =============================================================================
 * PROTECTED API ROUTES (Authentication Required)
 * =============================================================================
 */

Route::middleware('auth:sanctum')->group(function () {
    
    /**
     * Authentication Management Routes
     * Handle logout, token refresh, and user verification
     */
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout'])->name('api.auth.logout');
        Route::post('/refresh', [ApiAuthController::class, 'refreshToken'])->name('api.auth.refresh');
        Route::get('/me', [ApiAuthController::class, 'me'])->name('api.auth.me');
        Route::post('/verify-token', [ApiAuthController::class, 'verifyToken'])->name('api.auth.verify-token');
    });
    
    /**
     * User Management Routes
     * Handle user profile operations and account management
     */
    Route::prefix('user')->group(function () {
        Route::get('/profile', [ApiUserController::class, 'profile'])->name('api.user.profile');
        Route::put('/profile', [ApiUserController::class, 'updateProfile'])->name('api.user.update-profile');
        Route::post('/change-password', [ApiUserController::class, 'changePassword'])->name('api.user.change-password');
        Route::post('/upload-avatar', [ApiUserController::class, 'uploadAvatar'])->name('api.user.upload-avatar');
        Route::delete('/delete-avatar', [ApiUserController::class, 'deleteAvatar'])->name('api.user.delete-avatar');
        Route::get('/activity-log', [ApiUserController::class, 'activityLog'])->name('api.user.activity-log');
        Route::delete('/account', [ApiUserController::class, 'deleteAccount'])->name('api.user.delete-account');
    });
    
    /**
     * Goals Management Routes
     * Complete CRUD operations for user goals
     */
    Route::prefix('goals')->group(function () {
        Route::get('/', [ApiGoalController::class, 'index'])->name('api.goals.index');
        Route::post('/', [ApiGoalController::class, 'store'])->name('api.goals.store');
        Route::get('/search', [ApiGoalController::class, 'search'])->name('api.goals.search');
        
        Route::prefix('{goal}')->group(function () {
            Route::get('/', [ApiGoalController::class, 'show'])->name('api.goals.show');
            Route::put('/', [ApiGoalController::class, 'update'])->name('api.goals.update');
            Route::delete('/', [ApiGoalController::class, 'destroy'])->name('api.goals.destroy');
            Route::patch('/toggle-status', [ApiGoalController::class, 'toggleStatus'])->name('api.goals.toggle-status');
        });
        
        // Bulk operations for goals
        Route::prefix('bulk')->group(function () {
            Route::patch('/', [ApiGoalController::class, 'bulkUpdate'])->name('api.goals.bulk-update');
            Route::delete('/', [ApiGoalController::class, 'bulkDelete'])->name('api.goals.bulk-delete');
        });
    });
    
    /**
     * Workouts Management Routes
     * Complete CRUD operations for workout management
     */
    Route::prefix('workouts')->group(function () {
        Route::get('/', [WorkoutController::class, 'index'])->name('api.workouts.index');
        Route::post('/', [WorkoutController::class, 'store'])->name('api.workouts.store');
        Route::get('/search', [WorkoutController::class, 'search'])->name('api.workouts.search');
        Route::get('/statistics', [WorkoutController::class, 'statistics'])->name('api.workouts.statistics');
        Route::get('/categories', [WorkoutController::class, 'categories'])->name('api.workouts.categories');
        
        Route::prefix('{workout}')->group(function () {
            Route::get('/', [WorkoutController::class, 'show'])->name('api.workouts.show');
            Route::put('/', [WorkoutController::class, 'update'])->name('api.workouts.update');
            Route::delete('/', [WorkoutController::class, 'destroy'])->name('api.workouts.destroy');
            Route::patch('/toggle-status', [WorkoutController::class, 'toggleStatus'])->name('api.workouts.toggle-status');
            Route::post('/duplicate', [WorkoutController::class, 'duplicate'])->name('api.workouts.duplicate');
            Route::post('/favorite', [WorkoutController::class, 'addToFavorites'])->name('api.workouts.add-favorite');
            Route::delete('/favorite', [WorkoutController::class, 'removeFromFavorites'])->name('api.workouts.remove-favorite');
            
            /**
             * Workout Videos Management Routes
             * Nested routes for managing videos within workouts
             */
            Route::prefix('videos')->group(function () {
                Route::get('/', [WorkoutVideoController::class, 'index'])->name('api.workout-videos.index');
                Route::post('/', [WorkoutVideoController::class, 'store'])->name('api.workout-videos.store');
                Route::patch('/reorder', [WorkoutVideoController::class, 'reorder'])->name('api.workout-videos.reorder');
                
                Route::prefix('{video}')->group(function () {
                    Route::get('/', [WorkoutVideoController::class, 'show'])->name('api.workout-videos.show');
                    Route::put('/', [WorkoutVideoController::class, 'update'])->name('api.workout-videos.update');
                    Route::delete('/', [WorkoutVideoController::class, 'destroy'])->name('api.workout-videos.destroy');
                    Route::patch('/toggle-status', [WorkoutVideoController::class, 'toggleStatus'])->name('api.workout-videos.toggle-status');
                });
            });
        });
        
        // Bulk operations for workouts
        Route::prefix('bulk')->group(function () {
            Route::patch('/', [WorkoutController::class, 'bulkUpdate'])->name('api.workouts.bulk-update');
            Route::delete('/', [WorkoutController::class, 'bulkDelete'])->name('api.workouts.bulk-delete');
        });
    });
    
    /**
     * Standalone Workout Videos Routes
     * For managing videos independently of workouts
     */
    Route::prefix('videos')->group(function () {
        Route::get('/', [WorkoutVideoController::class, 'getAllVideos'])->name('api.videos.index');
        Route::get('/search', [WorkoutVideoController::class, 'search'])->name('api.videos.search');
        Route::get('/categories', [WorkoutVideoController::class, 'categories'])->name('api.videos.categories');
        
        Route::prefix('{video}')->group(function () {
            Route::get('/', [WorkoutVideoController::class, 'showVideo'])->name('api.videos.show');
            Route::post('/favorite', [WorkoutVideoController::class, 'addToFavorites'])->name('api.videos.add-favorite');
            Route::delete('/favorite', [WorkoutVideoController::class, 'removeFromFavorites'])->name('api.videos.remove-favorite');
        });
    });
    
    /**
     * Trainer Profile Management Routes
     * Handle trainer profiles, certifications, and testimonials
     */
    Route::prefix('trainers')->group(function () {
        // Public trainer listing and profile viewing
        Route::get('/', [TrainerController::class, 'index'])->name('api.trainers.index');
        Route::get('/{id}', [TrainerController::class, 'show'])->name('api.trainers.show');
        
        // Trainer profile management (only trainers can update their own profile)
        Route::put('/{id}', [TrainerController::class, 'update'])->name('api.trainers.update');
        
        // Certification management (only trainers can add certifications to their profile)
        Route::post('/{id}/certifications', [TrainerController::class, 'addCertification'])->name('api.trainers.add-certification');
        
        // Testimonial management (only clients can add testimonials for trainers)
        Route::post('/{id}/testimonials', [TrainerController::class, 'addTestimonial'])->name('api.trainers.add-testimonial');
    });
    
    /**
     * Testimonial Reaction Routes
     * Handle likes and dislikes for testimonials
     */
    Route::prefix('testimonials')->group(function () {
        Route::post('/{id}/like', [TrainerController::class, 'likeTestimonial'])->name('api.testimonials.like');
        Route::post('/{id}/dislike', [TrainerController::class, 'dislikeTestimonial'])->name('api.testimonials.dislike');
    });
    
    /**
     * System Information Routes
     * Provide system status and configuration information
     */
    Route::prefix('system')->group(function () {
        Route::get('/status', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => 'online',
                    'version' => '1.0.0',
                    'timestamp' => now()->toISOString(),
                    'laravel_version' => app()->version()
                ],
                'message' => 'System is operational'
            ]);
        })->name('api.system.status');
        
        Route::get('/config', function () {
            return response()->json([
                'success' => true,
                'data' => [
                    'app_name' => config('app.name'),
                    'app_env' => config('app.env'),
                    'timezone' => config('app.timezone'),
                    'locale' => config('app.locale')
                ],
                'message' => 'System configuration retrieved'
            ]);
        })->name('api.system.config');
    });
});

/**
 * =============================================================================
 * API FALLBACK ROUTES
 * =============================================================================
 */

// Handle undefined API routes
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'data' => [
            'error' => 'The requested API endpoint does not exist',
            'available_endpoints' => [
                'auth' => '/api/auth/*',
                'user' => '/api/user/*',
                'goals' => '/api/goals/*',
                'workouts' => '/api/workouts/*',
                'videos' => '/api/videos/*',
                'trainers' => '/api/trainers/*',
                'testimonials' => '/api/testimonials/*',
                'system' => '/api/system/*'
            ]
        ]
    ], 404);
});


