<?php

/**
 * API Routes for Go Globe CMS Application
 * 
 * Complete API endpoints organized by user roles (Admin, Trainer, Client)
 * All protected routes require Sanctum authentication token
 * 
 * @package     Laravel CMS App
 * @subpackage  Routes
 * @category    API Routes
 * @author      Go Globe CMS Team
 * @since       1.0.0
 * @version     2.0.0
 * @updated     2025-01-19
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
 * Handle user registration, login, and password reset operations
 */
Route::prefix('auth')->name('api.auth.')->group(function () {
    // User Registration & Login
    Route::post('/register', [ApiAuthController::class, 'register'])->name('register');
    Route::post('/login', [ApiAuthController::class, 'login'])->name('login');
    
    // Password Reset Flow
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/verify-otp', [ApiAuthController::class, 'verifyOTP'])->name('verify-otp');
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword'])->name('reset-password');
    Route::post('/resend-otp', [ApiAuthController::class, 'resendOTP'])->name('resend-otp');
});

/**
 * System Information Routes (Public)
 * Provide system status and configuration information
 */
Route::prefix('system')->name('api.system.')->group(function () {
    Route::get('/status', function () {
        return response()->json([
            'success' => true,
            'data' => [
                'status' => 'online',
                'version' => '2.0.0',
                'timestamp' => now()->toISOString(),
                'laravel_version' => app()->version()
            ],
            'message' => 'System is operational'
        ]);
    })->name('status');
    
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
    })->name('config');
});

/**
 * API Documentation Routes
 * Complete API documentation with detailed request/response examples
 * Organized by user roles and functionality
 */
Route::prefix('docs')->name('api.docs.')->group(function () {
    // Complete API documentation (public access)
    Route::get('/', [App\Http\Controllers\ApiDocumentationController::class, 'index'])->name('index');
    
    // Specific endpoint documentation
    Route::get('/{endpoint}', [App\Http\Controllers\ApiDocumentationController::class, 'getEndpoint'])->name('endpoint');
    
    // OpenAPI/Swagger schema
    Route::get('/schema/openapi', [App\Http\Controllers\ApiDocumentationController::class, 'getSchema'])->name('schema');
});

/**
 * =============================================================================
 * PROTECTED API ROUTES (Authentication Required)
 * =============================================================================
 */

Route::middleware('auth:sanctum')->group(function () {
    
    /**
     * =========================================================================
     * COMMON AUTHENTICATED ROUTES (All Roles)
     * =========================================================================
     */
    
    /**
     * Authentication Management Routes
     * Handle logout, token refresh, and user verification
     */
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [ApiAuthController::class, 'refreshToken'])->name('refresh');
        Route::get('/me', [ApiAuthController::class, 'me'])->name('me');
        Route::post('/verify-token', [ApiAuthController::class, 'verifyToken'])->name('verify-token');
    });
    
    /**
     * User Profile Management Routes (All Authenticated Users)
     * Handle user profile operations and account management
     */
    Route::prefix('user')->name('api.user.')->group(function () {
        Route::get('/profile', [ApiUserController::class, 'profile'])->name('profile');
        Route::put('/profile', [ApiUserController::class, 'updateProfile'])->name('update-profile');
        Route::post('/change-password', [ApiUserController::class, 'changePassword'])->name('change-password');
        Route::post('/upload-avatar', [ApiUserController::class, 'uploadAvatar'])->name('upload-avatar');
        Route::delete('/delete-avatar', [ApiUserController::class, 'deleteAvatar'])->name('delete-avatar');
        Route::get('/activity-log', [ApiUserController::class, 'activityLog'])->name('activity-log');
        Route::delete('/account', [ApiUserController::class, 'deleteAccount'])->name('delete-account');
    });
    
    /**
     * Goals Management Routes (All Authenticated Users)
     * Complete CRUD operations for user goals
     */
    Route::prefix('goals')->name('api.goals.')->group(function () {
        Route::get('/', [ApiGoalController::class, 'index'])->name('index');
        Route::post('/', [ApiGoalController::class, 'store'])->name('store');
        Route::get('/search', [ApiGoalController::class, 'search'])->name('search');
        
        Route::prefix('{goal}')->group(function () {
            Route::get('/', [ApiGoalController::class, 'show'])->name('show');
            Route::put('/', [ApiGoalController::class, 'update'])->name('update');
            Route::delete('/', [ApiGoalController::class, 'destroy'])->name('destroy');
            Route::patch('/toggle-status', [ApiGoalController::class, 'toggleStatus'])->name('toggle-status');
        });
        
        // Bulk operations for goals
        Route::prefix('bulk')->group(function () {
            Route::patch('/', [ApiGoalController::class, 'bulkUpdate'])->name('bulk-update');
            Route::delete('/', [ApiGoalController::class, 'bulkDelete'])->name('bulk-delete');
        });
    });
    
    /**
     * Public Trainer Information Routes (All Authenticated Users)
     * Public access to trainer information for browsing
     */
    Route::prefix('trainers')->name('api.trainers.')->group(function () {
        Route::get('/', [TrainerController::class, 'index'])->name('index');
        Route::get('/{id}', [TrainerController::class, 'show'])->name('show');
        Route::get('/{id}/certifications', [TrainerController::class, 'getTrainerCertifications'])->name('certifications');
        Route::get('/{id}/testimonials', [TrainerController::class, 'getTrainerTestimonials'])->name('testimonials');
        Route::get('/{id}/availability', [TrainerController::class, 'getAvailability'])->name('availability');
    });
    
    /**
     * Specializations Routes (All Authenticated Users)
     * Public access to specializations for filtering trainers
     */
    Route::prefix('specializations')->name('api.specializations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SpecializationController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Api\SpecializationController::class, 'show'])->name('show');
        Route::get('/{id}/trainers', [\App\Http\Controllers\Api\SpecializationController::class, 'getTrainers'])->name('trainers');
    });
    
    /**
     * Testimonial Reaction Routes (All Authenticated Users)
     * Handle likes and dislikes for testimonials
     */
    Route::prefix('testimonials')->name('api.testimonials.')->group(function () {
        Route::post('/{id}/like', [TrainerController::class, 'likeTestimonial'])->name('like');
        Route::post('/{id}/dislike', [TrainerController::class, 'dislikeTestimonial'])->name('dislike');
    });
    
    /**
     * =========================================================================
     * ADMIN ROLE ROUTES (Admin Access Only)
     * =========================================================================
     */
    
    Route::middleware('admin')->prefix('admin')->name('api.admin.')->group(function () {
        
        /**
         * Admin User Management API
         * Complete user management operations for administrators
         */
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AdminUserController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\AdminUserController::class, 'store'])->name('store');
            Route::get('/statistics', [\App\Http\Controllers\Api\AdminUserController::class, 'statistics'])->name('statistics');
            Route::get('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\AdminUserController::class, 'destroy'])->name('destroy');
            Route::get('/role/{role}', [\App\Http\Controllers\Api\AdminUserController::class, 'getUsersByRole'])->name('by-role');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminUserController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{id}/delete-image', [\App\Http\Controllers\Api\AdminUserController::class, 'deleteImage'])->name('delete-image');
        });
        
        /**
         * Admin Trainer Management API
         * Complete trainer oversight and management for administrators
         */
        Route::prefix('trainers')->name('trainers.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\AdminTrainerController::class, 'index'])->name('index');
            Route::get('/{id}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'destroy'])->name('destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminTrainerController::class, 'toggleStatus'])->name('toggle-status');
            Route::get('/{id}/analytics', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getAnalytics'])->name('analytics');
            
            // Admin Trainer Certifications Management
            Route::get('/{id}/certifications', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getCertifications'])->name('certifications.index');
            Route::post('/{id}/certifications', [\App\Http\Controllers\Api\AdminTrainerController::class, 'storeCertification'])->name('certifications.store');
            Route::put('/{trainerId}/certifications/{certificationId}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'updateCertification'])->name('certifications.update');
            Route::post('/{id}/certifications/{certificationId}/approve', [\App\Http\Controllers\Api\AdminTrainerController::class, 'approveCertification'])->name('certifications.approve');
            Route::delete('/{trainerId}/certifications/{certificationId}', [\App\Http\Controllers\Api\AdminTrainerController::class, 'deleteCertification'])->name('certifications.destroy');
            
            // Admin Trainer Testimonials Management
            Route::get('/{id}/testimonials', [\App\Http\Controllers\Api\AdminTrainerController::class, 'getTestimonials'])->name('testimonials.index');
        });
        
        /**
         * Admin Booking Management API
         * Complete booking oversight and management for administrators
         * Note: AdminBookingController needs to be created for admin API access
         */
        Route::prefix('bookings')->name('bookings.')->group(function () {
            // These routes are commented out until AdminBookingController is implemented
            /*
            Route::get('/', [\App\Http\Controllers\Api\AdminBookingController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\AdminBookingController::class, 'store'])->name('store');
            Route::get('/statistics', [\App\Http\Controllers\Api\AdminBookingController::class, 'getStatistics'])->name('statistics');
            Route::get('/{id}', [\App\Http\Controllers\Api\AdminBookingController::class, 'show'])->name('show');
            Route::put('/{id}', [\App\Http\Controllers\Api\AdminBookingController::class, 'update'])->name('update');
            Route::delete('/{id}', [\App\Http\Controllers\Api\AdminBookingController::class, 'destroy'])->name('destroy');
            Route::patch('/bulk-update', [\App\Http\Controllers\Api\AdminBookingController::class, 'bulkUpdate'])->name('bulk-update');
            */
        });
        
        /**
         * Admin Workout Management API
         * Complete workout oversight for administrators
         * Note: AdminWorkoutController needs to be created for admin API access
         */
        // Route::prefix('workouts')->name('workouts.')->group(function () {
            // These routes are commented out until AdminWorkoutController is implemented
            /*
            Route::get('/', [\App\Http\Controllers\Api\AdminWorkoutController::class, 'index'])->name('index');
            Route::get('/statistics', [\App\Http\Controllers\Api\AdminWorkoutController::class, 'getStatistics'])->name('statistics');
            Route::get('/{id}', [\App\Http\Controllers\Api\AdminWorkoutController::class, 'show'])->name('show');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Api\AdminWorkoutController::class, 'toggleStatus'])->name('toggle-status');
            Route::delete('/{id}', [\App\Http\Controllers\Api\AdminWorkoutController::class, 'destroy'])->name('destroy');
            */
        // });
        
        /**
          * Admin Nutrition Management API
          * Complete nutrition plan oversight for administrators
          * Note: AdminNutritionController needs to be created for admin API access
          */
         Route::prefix('nutrition')->name('nutrition.')->group(function () {
             // These routes are commented out until AdminNutritionController is implemented
             /*
             Route::get('/plans', [\App\Http\Controllers\Api\AdminNutritionController::class, 'index'])->name('plans.index');
             Route::get('/plans/statistics', [\App\Http\Controllers\Api\AdminNutritionController::class, 'getStatistics'])->name('plans.statistics');
             Route::get('/plans/{id}', [\App\Http\Controllers\Api\AdminNutritionController::class, 'show'])->name('plans.show');
             Route::delete('/plans/{id}', [\App\Http\Controllers\Api\AdminNutritionController::class, 'destroy'])->name('plans.destroy');
             */
         });
     });
    
    /**
     * =========================================================================
     * TRAINER ROLE ROUTES (Trainer Access Only)
     * =========================================================================
     */
    
    Route::middleware('trainer')->prefix('trainer')->name('api.trainer.')->group(function () {
        
        /**
         * Trainer Profile Management
         * Handle trainer profile operations and personal information
         */
        Route::get('/profile', [TrainerController::class, 'getProfile'])->name('profile');
        Route::put('/profile', [TrainerController::class, 'updateProfile'])->name('update-profile');
        
        /**
         * Trainer Certification Management
         * Complete CRUD operations for trainer certifications
         */
        Route::prefix('certifications')->name('certifications.')->group(function () {
            Route::get('/', [TrainerController::class, 'getCertifications'])->name('index');
            Route::post('/', [TrainerController::class, 'storeCertification'])->name('store');
            Route::get('/{id}', [TrainerController::class, 'showCertification'])->name('show');
            Route::put('/{id}', [TrainerController::class, 'updateCertification'])->name('update');
            Route::delete('/{id}', [TrainerController::class, 'destroyCertification'])->name('destroy');
        });
        
        /**
         * Trainer Testimonials (Read Only)
         * View testimonials received from clients
         */
        Route::get('/testimonials', [TrainerController::class, 'getMyTestimonials'])->name('testimonials.index');
        
        /**
         * Trainer Scheduling & Availability Management
         * Complete scheduling operations for trainers
         */
        Route::prefix('scheduling')->name('scheduling.')->group(function () {
            // Availability Management
            Route::post('/availability', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'setAvailability'])->name('availability.set');
            Route::get('/availability', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'getAvailability'])->name('availability.get');
            
            // Blocked Times Management
            Route::prefix('blocked-times')->name('blocked-times.')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'getBlockedTimes'])->name('index');
                Route::post('/', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'addBlockedTime'])->name('store');
                Route::delete('/{id}', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'deleteBlockedTime'])->name('destroy');
            });
            
            // Session Capacity Management
            Route::post('/session-capacity', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'setSessionCapacity'])->name('session-capacity.set');
            Route::get('/session-capacity', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'getSessionCapacity'])->name('session-capacity.get');
            
            // Booking Settings Management
            Route::post('/booking-settings', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'setBookingSettings'])->name('booking-settings.set');
            Route::get('/booking-settings', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'getBookingSettings'])->name('booking-settings.get');
        });
        
        /**
         * Trainer Booking Management
         * Handle trainer's booking operations and status updates
         */
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'getBookings'])->name('index');
            Route::patch('/{id}/status', [\App\Http\Controllers\Api\TrainerSchedulingController::class, 'updateBookingStatus'])->name('update-status');
        });
        
        /**
         * Trainer Workout Management
         * Complete CRUD operations for trainer's workouts and videos
         */
        // Route::prefix('workouts')->name('workouts.')->group(function () {
        //     Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'index'])->name('index');
        //     Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'store'])->name('store');
            
        //     Route::prefix('{id}')->group(function () {
        //         Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'show'])->name('show');
        //         Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'update'])->name('update');
        //         Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroy'])->name('destroy');
        //         Route::patch('/toggle-status', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'toggleStatus'])->name('toggle-status');
                
        //         /**
        //          * Trainer Workout Videos Management
        //          * Nested routes for managing videos within trainer's workouts
        //          */
        //         Route::prefix('videos')->name('videos.')->group(function () {
        //             Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'getVideos'])->name('index');
        //             Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'storeVideo'])->name('store');
        //             Route::patch('/reorder', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'reorderVideos'])->name('reorder');
                    
        //             Route::prefix('{videoId}')->group(function () {
        //                 Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'updateVideo'])->name('update');
        //                 Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroyVideo'])->name('destroy');
        //             });
        //         });
        //     });
        // });
        
        /**
         * Trainer Nutrition Management
         * Complete CRUD operations for nutrition plans and meal management
         */
        Route::prefix('nutrition')->name('nutrition.')->group(function () {
            Route::get('/plans', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'index'])->name('plans.index');
            Route::post('/plans', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'store'])->name('plans.store');
            Route::get('/plans/{id}', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'show'])->name('plans.show');
            Route::get('/clients', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'getClients'])->name('clients');
            
            // Meal management for nutrition plans
            Route::post('/plans/{planId}/meals', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'addMeal'])->name('plans.meals.store');
            
            // Macros and restrictions management
            Route::put('/plans/{planId}/macros', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'updateMacros'])->name('plans.macros.update');
            Route::put('/plans/{planId}/restrictions', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'updateRestrictions'])->name('plans.restrictions.update');
        });
    });
    
    /**
     * =========================================================================
     * CLIENT ROLE ROUTES (Client Access Only)
     * =========================================================================
     */
    
    Route::middleware('client')->prefix('client')->name('api.client.')->group(function () {
        
        /**
         * Client Dashboard
         * Main dashboard with overview information
         */
        Route::get('/dashboard', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getDashboard'])->name('dashboard');
        
        /**
         * Client Workout Access (Read-Only)
         * Access to active workouts assigned by trainers
         */
        Route::prefix('workouts')->name('workouts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'index'])->name('index');
            Route::get('/search', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'search'])->name('search');
            Route::get('/statistics', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getStatistics'])->name('statistics');
            Route::get('/featured', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getFeatured'])->name('featured');
            
            // Assignment-related routes
            Route::get('/assigned', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getAssignedWorkouts'])->name('assigned');
            
            Route::prefix('{id}')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'show'])->name('show');
                Route::get('/videos', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getVideos'])->name('videos');
                Route::get('/videos/{videoId}', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'showVideo'])->name('videos.show');
                Route::get('/videos/{videoId}/progress', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'showVideoProgress'])->name('videos.progress.show');
                
                // Progress tracking routes
                Route::get('/progress', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getProgress'])->name('progress.show');
                Route::patch('/progress', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'updateProgress'])->name('progress.update');
                Route::patch('/videos/{videoId}/progress', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'updateVideoProgress'])->name('videos.progress.update');
                Route::get('/videos/progress', [\App\Http\Controllers\Api\ClientWorkoutController::class, 'getVideoProgress'])->name('videos.progress');
            
            });
        });
        
        /**
         * Client Booking Management
         * Complete booking operations for clients
         */
        Route::prefix('bookings')->name('bookings.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\ClientBookingController::class, 'getClientBookings'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\ClientBookingController::class, 'requestBooking'])->name('store');
            Route::delete('/{id}', [\App\Http\Controllers\Api\ClientBookingController::class, 'cancelBooking'])->name('cancel');
        });
        
        /**
         * Client Nutrition Management (Read-Only)
         * Access to assigned nutrition plans and meal information
         */
        Route::prefix('nutrition')->name('nutrition.')->group(function () {
            Route::get('/my-plan', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getMyPlan'])->name('my-plan');
            Route::get('/recipes', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getRecipes'])->name('recipes');
            
            // Food diary management
            Route::post('/food-diary', [\App\Http\Controllers\Api\ClientNutritionController::class, 'logFoodDiary'])->name('food-diary.store');
            Route::get('/food-diary', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getFoodDiary'])->name('food-diary.index');
            Route::put('/food-diary/{id}', [\App\Http\Controllers\Api\ClientNutritionController::class, 'updateFoodDiary'])->name('food-diary.update');
            Route::delete('/food-diary/{id}', [\App\Http\Controllers\Api\ClientNutritionController::class, 'deleteFoodDiary'])->name('food-diary.destroy');
            
            // Nutrition recommendations management
            Route::get('/recommendations', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getCurrentRecommendations'])->name('recommendations.current');
            Route::put('/recommendations', [\App\Http\Controllers\Api\ClientNutritionController::class, 'updateCurrentRecommendations'])->name('recommendations.update');
            
            // Nutrition goal types
            Route::get('/goal-types', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getNutritionGoalTypes'])->name('goal-types');
        
        }); 
        
        /**
         * Client Schedule Management
         * Access to scheduled sessions and assigned workouts by date
         */
        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('/date', [\App\Http\Controllers\Api\ClientScheduleController::class, 'getScheduleByDate'])->name('by-date');
            Route::get('/range', [\App\Http\Controllers\Api\ClientScheduleController::class, 'getScheduleRange'])->name('range');
        });
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
            'documentation' => 'Please refer to the API documentation for available endpoints',
            'available_sections' => [
                'auth' => '/api/auth/*',
                'user' => '/api/user/*',
                'goals' => '/api/goals/*',
                'trainers' => '/api/trainers/*',
                'testimonials' => '/api/testimonials/*',
                'specializations' => '/api/specializations/*',
                'admin' => '/api/admin/*',
                'trainer' => '/api/trainer/*',
                'client' => '/api/client/*',
                'system' => '/api/system/*'
            ]
        ]
    ], 404);
});


