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

    // Unified Password Reset Flow (supports both email and phone)
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword'])->name('forgot-password');
    Route::post('/verify-otp', [ApiAuthController::class, 'verifyOTP'])->name('verify-otp');
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword'])->name('reset-password');
    Route::post('/resend-otp', [ApiAuthController::class, 'resendOTP'])->name('resend-otp');
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
    Route::prefix('auth')->name('api.auth.')->group(function () {
        Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');
        Route::post('/refresh', [ApiAuthController::class, 'refreshToken'])->name('refresh');
        Route::get('/me', [ApiAuthController::class, 'me'])->name('me');
        Route::post('/verify-token', [ApiAuthController::class, 'verifyToken'])->name('verify-token');
    });


    /**
     * =========================================================================
     * TRAINER ROLE ROUTES (Trainer Access Only)
     * =========================================================================
     */

    Route::middleware('trainer')->prefix('trainer')->name('api.trainer.')->group(function () {
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


        // /**
        //  * Trainer Profile Management
        //  * Handle trainer profile operations and personal information
        //  */
        // Route::get('/profile', [TrainerController::class, 'getProfile'])->name('profile');
        // Route::put('/profile', [TrainerController::class, 'updateProfile'])->name('update-profile');

        /**
         * Trainer Certification Management
         * Complete CRUD operations for trainer certifications
         */
        // Route::prefix('certifications')->name('certifications.')->group(function () {
        //     Route::get('/', [TrainerController::class, 'getCertifications'])->name('index');
        //     Route::post('/', [TrainerController::class, 'storeCertification'])->name('store');
        //     Route::get('/{id}', [TrainerController::class, 'showCertification'])->name('show');
        //     Route::put('/{id}', [TrainerController::class, 'updateCertification'])->name('update');
        //     Route::delete('/{id}', [TrainerController::class, 'destroyCertification'])->name('destroy');
        // });

        /**
         * Trainer Testimonials (Read Only)
         * View testimonials received from clients
         */
        Route::get('/testimonials', [TrainerController::class, 'getMyTestimonials'])->name('testimonials.index');



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
        Route::prefix('workouts')->name('workouts.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'store'])->name('store');

            /**
             * Workout Builder APIs
             * For creating and managing workout templates
             */
            Route::get('/builder', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'getWorkoutBuilder'])->name('builder');
            Route::get('/exercises/search', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'searchExercises'])->name('exercises.search');

            Route::prefix('{id}')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'show'])->name('show');
                Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'update'])->name('update');
                Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroy'])->name('destroy');
                Route::patch('/toggle-status', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'toggleStatus'])->name('toggle-status');

                /**
                 * Workout Exercise Management APIs
                 * For adding, configuring, and managing exercises within workouts
                 */
                Route::prefix('exercises')->name('exercises.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'getWorkoutExercises'])->name('index');
                    Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'addExerciseToWorkout'])->name('store');
                    Route::post('/reorder', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'reorderWorkoutExercises'])->name('reorder');

                    Route::prefix('{exerciseId}')->group(function () {
                        Route::put('/configure', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'configureExercise'])->name('configure');
                        Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'removeExerciseFromWorkout'])->name('destroy');
                    });
                });

                /**
                 * Trainer Workout Videos Management
                 * Nested routes for managing videos within trainer's workouts
                 */
                Route::prefix('videos')->name('videos.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'getVideos'])->name('index');
                    Route::post('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'storeVideo'])->name('store');
                    Route::patch('/reorder', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'reorderVideos'])->name('reorder');

                    Route::prefix('{videoId}')->group(function () {
                        Route::put('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'updateVideo'])->name('update');
                        Route::delete('/', [\App\Http\Controllers\Api\TrainerWorkoutController::class, 'destroyVideo'])->name('destroy');
                    });
                });
            });
        });

        /**
         * Trainer Nutrition Management
         * Complete CRUD operations for nutrition plans and meal management
         */
        Route::prefix('nutrition')->name('nutrition.')->group(function () {
            Route::get('/my-plans', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'index'])->name('plans.index');
            Route::post('/my-plans', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'store'])->name('plans.store');
            Route::get('/my-plans/{id}', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'show'])->name('plans.show');
            Route::get('/clients', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'getClients'])->name('clients');

            // Meal management for nutrition plans
            Route::post('/my-plans/{planId}/meals', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'addMeal'])->name('plans.meals.store');

            // Macros and restrictions management
            Route::put('/my-plans/{planId}/macros', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'updateMacros'])->name('plans.macros.update');
            Route::put('/my-plans/{planId}/restrictions', [\App\Http\Controllers\Api\TrainerNutritionController::class, 'updateRestrictions'])->name('plans.restrictions.update');
        });

        /**
         * Google Calendar Integration
         * Handle Google OAuth flow and calendar management for trainers
         */
        Route::prefix('google')->name('google.')->group(function () {
            Route::get('/connect', [\App\Http\Controllers\GoogleController::class, 'redirectToGoogle'])->name('connect');
            Route::get('/status', [\App\Http\Controllers\GoogleController::class, 'getConnectionStatus'])->name('status');
            Route::delete('/disconnect', [\App\Http\Controllers\GoogleController::class, 'disconnectGoogle'])->name('disconnect');
        });

        /**
         * Trainer Client Management
         * Complete client management operations for trainers
         */
        Route::prefix('clients')->name('clients.')->group(function () {
            Route::post('/', [\App\Http\Controllers\Api\TrainerController::class, 'addClient'])->name('store');
            Route::get('/', [\App\Http\Controllers\Api\TrainerController::class, 'getClients'])->name('index');
            Route::get('/{clientId}/details', [\App\Http\Controllers\Api\TrainerController::class, 'getClientDetails'])->name('details');
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
         * Client Trainer Management
         * Access to trainer information and availability
         */
        Route::prefix('trainers')->name('trainers.')->group(function () {
            Route::get('/{trainerId}/availability', [\App\Http\Controllers\Api\ClientBookingController::class, 'getTrainerAvailability'])->name('availability');
        });

        /**
         * Client Nutrition Management (Read-Only)
         * Access to assigned nutrition plans and meal information
         */
        Route::prefix('nutrition')->name('nutrition.')->group(function () {
            Route::get('/my-plan', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getMyPlan'])->name('my-plan');
            Route::get('/recipes', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getRecipes'])->name('recipes');

            // Recipe management
            Route::get('/plans/{planId}/recipes', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getPlanRecipes'])->name('plan-recipes');
            Route::get('/plans/{planId}/recipes/{recipeId}', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getPlanRecipe'])->name('plan-recipe');
            Route::get('/global-recipes', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getGlobalRecipes'])->name('global-recipes');

            // Meal management
            Route::get('/meals', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getMeals'])->name('meals');
            Route::get('/plans/{planId}/meals', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getPlanMeals'])->name('plan-meals');
            Route::get('/plans/{planId}/meals/{mealId}', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getPlanMeal'])->name('plan-meal');
            Route::get('/global-meals', [\App\Http\Controllers\Api\ClientNutritionController::class, 'getGlobalMeals'])->name('global-meals');

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

            // Nutrition Calculator
            Route::post('/calculate', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'calculate'])->name('calculate');
            Route::post('/save-plan', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'savePlan'])->name('save-plan');
            Route::get('/client/{clientId}', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'getClientNutrition'])->name('client-nutrition');
            Route::put('/plans/{planId}/recalculate', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'recalculate'])->name('recalculate');
            Route::get('/activity-levels', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'getActivityLevels'])->name('activity-levels');
            Route::get('/calculator-goal-types', [\App\Http\Controllers\Api\NutritionCalculatorController::class, 'getGoalTypes'])->name('calculator-goal-types');
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
     * PUBLIC TRAINER ROUTES (No Role Restriction)
     * Public access to trainer information for browsing
     */
    Route::prefix('trainers')->group(function () {
        // Public trainer listing and profile viewing
        Route::get('/', [TrainerController::class, 'index'])->name('api.trainers.index');
        Route::get('/{id}', [TrainerController::class, 'show'])->name('api.trainers.show');
        Route::get('/{id}/certifications', [TrainerController::class, 'getTrainerCertifications'])->name('api.trainers.certifications');
        Route::get('/{id}/testimonials', [TrainerController::class, 'getTrainerTestimonials'])->name('api.trainers.testimonials');
        
        // Client can add testimonials for trainers
        Route::post('/{id}/testimonials', [TrainerController::class, 'addTestimonial'])->name('api.trainers.add-testimonial');
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
 * =============================================================================
 * SMS COMMUNICATION ROUTES
 * =============================================================================
 */

/**
 * SMS Routes for Trainer-Client Communication
 * Handles SMS messaging between trainers and clients via Twilio
 */
Route::middleware(['auth:sanctum'])->prefix('sms')->name('sms.')->group(function () {
    // Send SMS message
    Route::post('/send', [\App\Http\Controllers\Api\SmsController::class, 'sendMessage'])->name('send');
    
    // Get conversation with specific user
    Route::get('/conversation', [\App\Http\Controllers\Api\SmsController::class, 'getConversation'])->name('conversation');
    
    // Get all conversations for authenticated user
    Route::get('/conversations', [\App\Http\Controllers\Api\SmsController::class, 'getConversations'])->name('conversations');
    
    // Mark messages as read
    Route::patch('/mark-read', [\App\Http\Controllers\Api\SmsController::class, 'markAsRead'])->name('mark-read');
    
    // Get message status
    Route::get('/status/{messageSid}', [\App\Http\Controllers\Api\SmsController::class, 'getMessageStatus'])->name('status');
    
    // SMS Preferences Routes
    Route::prefix('preferences')->name('preferences.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\UserPreferencesController::class, 'getSmsPreferences'])->name('get');
        Route::put('/', [\App\Http\Controllers\Api\UserPreferencesController::class, 'updateSmsPreferences'])->name('update');
        Route::post('/reset', [\App\Http\Controllers\Api\UserPreferencesController::class, 'resetSmsPreferences'])->name('reset');
        Route::get('/types', [\App\Http\Controllers\Api\UserPreferencesController::class, 'getSmsNotificationTypes'])->name('types');
    });
});

/**
 * SMS Webhook Routes (Public - for Twilio callbacks)
 * These routes handle incoming SMS and status updates from Twilio
 */
Route::prefix('sms/webhook')->name('sms.webhook.')->group(function () {
    // Handle incoming SMS from Twilio
    Route::post('/incoming', [\App\Http\Controllers\Api\SmsController::class, 'handleIncomingSms'])->name('incoming');
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
                'system' => '/api/system/*',
                'sms' => '/api/sms/*'
            ]
        ]
    ], 404);
});