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
use App\Http\Controllers\Admin\WorkoutController;
use App\Http\Controllers\Admin\WorkoutVideoController;
use App\Http\Controllers\Api\TrainerController;
use App\Http\Controllers\Api\ClientController;

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
     * ADMIN API ROUTES - Admin Role Required
     * Complete user and trainer management via API
     */
    Route::middleware('admin')->prefix('admin')->group(function () {
        
        // Admin User Management API
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AdminUserController::class, 'index'])->name('api.admin.users.index');
            Route::post('/', [\App\Http\Controllers\Api\AdminUserController::class, 'store'])->name('api.admin.users.store');
            Route::get('/statistics', [\App\Http\Controllers\Api\AdminUserController::class, 'statistics'])->name('api.admin.users.statistics');
            Route::get('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'show'])->name('api.admin.users.show');
            Route::put('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'update'])->name('api.admin.users.update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'destroy'])->name('api.admin.users.destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminUserController::class, 'toggleStatus'])->name('api.admin.users.toggle-status');
            Route::delete('/{id}/delete-image', [\App\Http\Controllers\Api\AdminUserController::class, 'deleteImage'])->name('api.admin.users.delete-image');
        });
        
        // Admin Trainer Management API
        // Route::prefix('trainers')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\AdminTrainerController::class, 'index'])->name('api.admin.trainers.index');
        //     Route::get('/{id}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'show'])->name('api.admin.trainers.show');
        //     Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminTrainerController::class, 'toggleStatus'])->name('api.admin.trainers.toggle-status');
        //     Route::get('/{id}/analytics', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getAnalytics'])->name('api.admin.trainers.analytics');
            
        //     // Admin Trainer Certifications Management API
        //     Route::get('/{id}/certifications', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getCertifications'])->name('api.admin.trainers.certifications.index');
        //     Route::post('/{id}/certifications', [\App\Http\Controllers\Api\AdminTrainerController::class, 'storeCertification'])->name('api.admin.trainers.certifications.store');
        //     Route::put('/{trainerId}/certifications/{certificationId}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'updateCertification'])->name('api.admin.trainers.certifications.update');
        //     Route::delete('/{trainerId}/certifications/{certificationId}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'deleteCertification'])->name('api.admin.trainers.certifications.destroy');
            
        //     // Admin Trainer Testimonials Management API
        //     Route::get('/{id}/testimonials', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getTestimonials'])->name('api.admin.trainers.testimonials.index');
        // });
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
     * CLIENT WORKOUT ROUTES - Client Role Required
     * Read-only access to active workouts for clients
     */
    Route::middleware('client')->prefix('client')->group(function () {
        // Client Dashboard
        Route::get('/dashboard', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getDashboard'])->name('api.client.dashboard');
        
        // Client Workout Routes
        Route::prefix('workouts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'index'])->name('api.client.workouts.index');
            Route::get('/search', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'search'])->name('api.client.workouts.search');
            Route::get('/statistics', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getStatistics'])->name('api.client.workouts.statistics');
            Route::get('/featured', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getFeatured'])->name('api.client.workouts.featured');
            
            Route::prefix('{id}')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'show'])->name('api.client.workouts.show');
                Route::get('/videos', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getVideos'])->name('api.client.workouts.videos');
                Route::get('/videos/{videoId}', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'showVideo'])->name('api.client.workouts.videos.show');
            });
        });
    });
    
    /**
     * TRAINER API ROUTES - Trainer Role Required
     * Handle trainer profile management, certifications, and workouts
     */
    Route::middleware('trainer')->prefix('trainer')->group(function () {
        // Trainer Profile Management
        Route::get('/profile', [TrainerController::class, 'getProfile'])->name('api.trainer.profile');
        Route::put('/profile', [TrainerController::class, 'updateProfile'])->name('api.trainer.update-profile');
        
        // Trainer Certification Management
        Route::prefix('certifications')->group(function () {
            Route::get('/', [TrainerController::class, 'getCertifications'])->name('api.trainer.certifications.index');
            Route::post('/', [TrainerController::class, 'storeCertification'])->name('api.trainer.certifications.store');
            Route::get('/{id}', [TrainerController::class, 'showCertification'])->name('api.trainer.certifications.show');
            Route::put('/{id}', [TrainerController::class, 'updateCertification'])->name('api.trainer.certifications.update');
            Route::delete('/{id}', [TrainerController::class, 'destroyCertification'])->name('api.trainer.certifications.destroy');
        });
        
        // Trainer Testimonials (Read Only)
        Route::get('/testimonials', [TrainerController::class, 'getMyTestimonials'])->name('api.trainer.testimonials.index');
        
        /**
         * TRAINER WORKOUT MANAGEMENT - Complete CRUD operations
         * Trainers can manage their own workouts and videos
         */
        Route::prefix('workouts')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'index'])->name('api.trainer.workouts.index');
            Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'store'])->name('api.trainer.workouts.store');
            
            Route::prefix('{id}')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'show'])->name('api.trainer.workouts.show');
                Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'update'])->name('api.trainer.workouts.update');
                Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroy'])->name('api.trainer.workouts.destroy');
                Route::patch('/toggle-status', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'toggleStatus'])->name('api.trainer.workouts.toggle-status');
                
                /**
                 * Trainer Workout Videos Management
                 * Nested routes for managing videos within trainer's workouts
                 */
                Route::prefix('videos')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'getVideos'])->name('api.trainer.workouts.videos.index');
                    Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'storeVideo'])->name('api.trainer.workouts.videos.store');
                    Route::patch('/reorder', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'reorderVideos'])->name('api.trainer.workouts.videos.reorder');
                    
                    Route::prefix('{videoId}')->group(function () {
                        Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'updateVideo'])->name('api.trainer.workouts.videos.update');
                        Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroyVideo'])->name('api.trainer.workouts.videos.destroy');
                    });
                });
            });
        });
    });
    
    /**
     * PUBLIC TRAINER ROUTES (No Role Restriction)
     * Public access to trainer information for browsing
     */
    Route::prefix('trainers')->group(function () {
        // Public trainer listing and profile viewing
        Route::get('/', [TrainerController::class, 'index'])->name('api.trainers.index');
        Route::get('/{id}', [TrainerController::class, 'show'])->name('api.trainers.show');
        // Route::get('/{id}/certifications', [TrainerController::class, 'getTrainerCertifications'])->name('api.trainers.certifications');
        // Route::get('/{id}/testimonials', [TrainerController::class, 'getTrainerTestimonials'])->name('api.trainers.testimonials');
        
        // Client can add testimonials for trainers
        // Route::post('/{id}/testimonials', [TrainerController::class, 'addTestimonial'])->name('api.trainers.add-testimonial');
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
     * CLIENT API ROUTES - Client Role Required
     * Find trainers and view trainer profiles with comprehensive details
     */
    Route::middleware('client')->prefix('client')->group(function () {
        
        // Find Trainers Routes
        Route::prefix('trainers')->group(function () {
            Route::get('/find', [ClientController::class, 'findTrainers'])->name('api.client.trainers.find');
            Route::get('/{trainerId}/profile', [ClientController::class, 'getTrainerProfile'])->name('api.client.trainers.profile');
            Route::get('/{trainerId}/certifications', [ClientController::class, 'getTrainerCertifications'])->name('api.client.trainers.certifications');
            Route::get('/{trainerId}/testimonials', [ClientController::class, 'getTrainerTestimonials'])->name('api.client.trainers.testimonials');
        });
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
                // 'user' => '/api/user/*',
                // 'goals' => '/api/goals/*',
                // 'workouts' => '/api/workouts/*',
                // 'videos' => '/api/videos/*',
                'trainers' => '/api/trainers/*',
                'testimonials' => '/api/testimonials/*',
                // 'system' => '/api/system/*'
            ]
        ]
    ], 404);
});


