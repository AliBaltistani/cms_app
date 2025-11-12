<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\Admin\GoalsController;
use App\Http\Controllers\Admin\WorkoutController;
use App\Http\Controllers\Admin\WorkoutVideoController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Client\ClientDashboardController;
use App\Http\Controllers\Trainer\TrainerDashboardController;
use App\Http\Controllers\Trainer\TrainerWebController;
use App\Http\Controllers\Api\TrainerBillingController;

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
    
    // Google OAuth (Web) - initiate login/register via Google
    Route::prefix('auth/google')->name('auth.google.')->group(function () {
        Route::get('/redirect', [GoogleAuthController::class, 'redirectToGoogle'])->name('redirect');
        Route::get('/callback', [GoogleAuthController::class, 'handleGoogleCallback'])->name('callback');
        Route::get('/complete', [GoogleAuthController::class, 'showCompleteProfileForm'])->name('complete.form');
        Route::post('/complete', [GoogleAuthController::class, 'completeRegistration'])->name('complete.submit');
    });
    
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
 * Google OAuth Routes
 * Handle Google Calendar integration OAuth flow
 */
Route::prefix('google')->name('google.')->group(function () {
    Route::get('/callback', [\App\Http\Controllers\GoogleController::class, 'handleGoogleCallback'])
        ->name('callback')
        ->middleware('auth');
});

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
        Route::get('/reports', [AdminDashboardController::class, 'reports'])->name('admin.reports');
        
        // Users Management Routes
        Route::prefix('users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UsersController::class, 'index'])->name('admin.users.index');
            Route::get('/create', [\App\Http\Controllers\Admin\UsersController::class, 'create'])->name('admin.users.create');
            Route::post('/store', [\App\Http\Controllers\Admin\UsersController::class, 'store'])->name('admin.users.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'show'])->name('admin.users.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\UsersController::class, 'edit'])->name('admin.users.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'update'])->name('admin.users.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\UsersController::class, 'destroy'])->name('admin.users.destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Admin\UsersController::class, 'toggleStatus'])->name('admin.users.toggle-status');
            Route::delete('/{id}/delete-image', [\App\Http\Controllers\Admin\UsersController::class, 'deleteImage'])->name('admin.users.delete-image');
        });
        
        // Trainers Management Routes
        Route::prefix('trainers')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TrainersController::class, 'index'])->name('admin.trainers.index');
            Route::get('/create', [\App\Http\Controllers\Admin\TrainersController::class, 'create'])->name('admin.trainers.create');
            Route::post('/store', [\App\Http\Controllers\Admin\TrainersController::class, 'store'])->name('admin.trainers.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\TrainersController::class, 'show'])->name('admin.trainers.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TrainersController::class, 'edit'])->name('admin.trainers.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\TrainersController::class, 'update'])->name('admin.trainers.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\TrainersController::class, 'destroy'])->name('admin.trainers.destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Admin\TrainersController::class, 'toggleStatus'])->name('admin.trainers.toggle-status');
            
            // Trainer Certifications Management
            Route::get('/{id}/certifications', [\App\Http\Controllers\Admin\TrainersController::class, 'certifications'])->name('admin.trainers.certifications');
            Route::post('/{id}/certifications', [\App\Http\Controllers\Admin\TrainersController::class, 'storeCertification'])->name('admin.trainers.certifications.store');
            Route::delete('/{trainerId}/certifications/{certificationId}', [\App\Http\Controllers\Admin\TrainersController::class, 'deleteCertification'])->name('admin.trainers.certifications.destroy');
            
            // Trainer Testimonials Management
            Route::get('/{id}/testimonials', [\App\Http\Controllers\Admin\TrainersController::class, 'testimonials'])->name('admin.trainers.testimonials');
        });

        // Trainers Management Routes
        // Trainees Management Routes
        Route::prefix('trainees')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\TraineesController::class, 'index'])->name('admin.trainees.index');
            Route::get('/create', [\App\Http\Controllers\Admin\TraineesController::class, 'create'])->name('admin.trainees.create');
            Route::post('/store', [\App\Http\Controllers\Admin\TraineesController::class, 'store'])->name('admin.trainees.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\TraineesController::class, 'show'])->name('admin.trainees.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\TraineesController::class, 'edit'])->name('admin.trainees.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\TraineesController::class, 'update'])->name('admin.trainees.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\TraineesController::class, 'destroy'])->name('admin.trainees.destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Admin\TraineesController::class, 'toggleStatus'])->name('admin.trainees.toggle-status');
            Route::delete('/{id}/delete-image', [\App\Http\Controllers\Admin\TraineesController::class, 'deleteImage'])->name('admin.trainees.delete-image');
        });
        
        // Specializations Management Routes
        Route::prefix('specializations')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\SpecializationsController::class, 'index'])->name('admin.specializations.index');
            Route::get('/create', [\App\Http\Controllers\Admin\SpecializationsController::class, 'create'])->name('admin.specializations.create');
            Route::post('/store', [\App\Http\Controllers\Admin\SpecializationsController::class, 'store'])->name('admin.specializations.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\SpecializationsController::class, 'show'])->name('admin.specializations.show');
            Route::get('/{specialization}/edit', [\App\Http\Controllers\Admin\SpecializationsController::class, 'edit'])->name('admin.specializations.edit');
            Route::put('/{specialization}', [\App\Http\Controllers\Admin\SpecializationsController::class, 'update'])->name('admin.specializations.update');
            Route::delete('/{specialization}', [\App\Http\Controllers\Admin\SpecializationsController::class, 'destroy'])->name('admin.specializations.destroy');
            Route::patch('/{specialization}/toggle-status', [\App\Http\Controllers\Admin\SpecializationsController::class, 'toggleStatus'])->name('admin.specializations.toggle-status');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\SpecializationsController::class, 'bulkDelete'])->name('admin.specializations.bulk-delete');
            Route::get('/export', [\App\Http\Controllers\Admin\SpecializationsController::class, 'export'])->name('admin.specializations.export');
        });
        
        // User Locations Management Routes
        Route::prefix('user-locations')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\UserLocationsController::class, 'index'])->name('admin.user-locations.index');
            Route::get('/create', [\App\Http\Controllers\Admin\UserLocationsController::class, 'create'])->name('admin.user-locations.create');
            Route::post('/store', [\App\Http\Controllers\Admin\UserLocationsController::class, 'store'])->name('admin.user-locations.store');
            Route::get('/{userLocation}', [\App\Http\Controllers\Admin\UserLocationsController::class, 'show'])->name('admin.user-locations.show');
            Route::get('/{userLocation}/edit', [\App\Http\Controllers\Admin\UserLocationsController::class, 'edit'])->name('admin.user-locations.edit');
            Route::put('/{userLocation}', [\App\Http\Controllers\Admin\UserLocationsController::class, 'update'])->name('admin.user-locations.update');
            Route::delete('/{userLocation}', [\App\Http\Controllers\Admin\UserLocationsController::class, 'destroy'])->name('admin.user-locations.destroy');
            Route::post('/bulk-delete', [\App\Http\Controllers\Admin\UserLocationsController::class, 'bulkDelete'])->name('admin.user-locations.bulk-delete');
            Route::get('/user/{userId}', [\App\Http\Controllers\Admin\UserLocationsController::class, 'getLocationsByUser'])->name('admin.user-locations.by-user');
        });
        
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

        // Workouts Management - Additional routes MUST come before resource routes
        Route::get('workouts/stats', [WorkoutController::class, 'stats'])->name('workouts.stats');
        Route::get('workouts/{workout}/videos-list', [WorkoutController::class, 'videosList'])->name('workouts.videos-list');
        Route::post('workouts/{workout}/duplicate', [WorkoutController::class, 'duplicate'])->name('workouts.duplicate');
        Route::patch('workouts/{workout}/toggle-status', [WorkoutController::class, 'toggleStatus'])->name('workouts.toggle-status');
        
        // Resource routes (must come after specific routes to avoid conflicts)
        Route::resource('workouts', WorkoutController::class);
        Route::resource('workouts.videos', WorkoutVideoController::class)
            ->names([
                'index' => 'workout-videos.index',
                'create' => 'workout-videos.create',
                'store' => 'workout-videos.store',
                'show' => 'workout-videos.show',
                'edit' => 'workout-videos.edit',
                'update' => 'workout-videos.update',
                'destroy' => 'workout-videos.destroy',
            ]);
        
        // Workout Video Additional Routes
        Route::get('workouts/{workout}/videos/reorder', [WorkoutVideoController::class, 'reorderForm'])->name('workout-videos.reorder-form');
        Route::patch('workouts/{workout}/videos/reorder', [WorkoutVideoController::class, 'reorder'])->name('workout-videos.reorder');
        
        // Workout Assignment Routes
        Route::post('workouts/{workout}/assign', [WorkoutController::class, 'assignWorkout'])->name('workouts.assign');
        Route::get('workouts/users/{type}', [WorkoutController::class, 'getUsersByType'])->name('workouts.users-by-type');
        Route::patch('workout-assignments/{assignment}/status', [\App\Http\Controllers\Admin\WorkoutAssignmentController::class, 'updateStatus'])->name('workout-assignments.update-status');
        Route::delete('workout-assignments/{assignment}', [\App\Http\Controllers\Admin\WorkoutAssignmentController::class, 'destroy'])->name('workout-assignments.destroy');
        
        // Workout Exercises Management Routes
        Route::resource('workouts.exercises', \App\Http\Controllers\Admin\WorkoutExerciseController::class)
            ->names([
                'index' => 'workout-exercises.index',
                'create' => 'workout-exercises.create',
                'store' => 'workout-exercises.store',
                'show' => 'workout-exercises.show',
                'edit' => 'workout-exercises.edit',
                'update' => 'workout-exercises.update',
                'destroy' => 'workout-exercises.destroy',
            ]);

        /**
         * Stripe Connect onboarding callbacks for trainers (web routes)
         * These URLs are used by Stripe Account Links as refresh/return targets.
         */
        Route::prefix('trainer/stripe/connect')->group(function () {
            Route::get('/return', [TrainerBillingController::class, 'stripeConnectReturn'])
                ->name('trainer.stripe.connect.return');
            Route::get('/refresh', [TrainerBillingController::class, 'stripeConnectRefresh'])
                ->name('trainer.stripe.connect.refresh');
        });
        
        // Workout Exercise Additional Routes
        Route::patch('workouts/{workout}/exercises/reorder', [\App\Http\Controllers\Admin\WorkoutExerciseController::class, 'reorder'])->name('workout-exercises.reorder');
        Route::patch('workouts/{workout}/exercises/{exercise}/toggle-status', [\App\Http\Controllers\Admin\WorkoutExerciseController::class, 'toggleStatus'])->name('workout-exercises.toggle-status');
        
        // Workout Exercise Sets Management Routes
        Route::resource('workouts.exercises.sets', \App\Http\Controllers\Admin\WorkoutExerciseSetController::class)
            ->names([
                'index' => 'workout-exercise-sets.index',
                'create' => 'workout-exercise-sets.create',
                'store' => 'workout-exercise-sets.store',
                'show' => 'workout-exercise-sets.show',
                'edit' => 'workout-exercise-sets.edit',
                'update' => 'workout-exercise-sets.update',
                'destroy' => 'workout-exercise-sets.destroy',
            ]);
        
        // Workout Exercise Set Additional Routes
        Route::post('workouts/{workout}/exercises/{exercise}/sets/{set}/toggle-status', [\App\Http\Controllers\Admin\WorkoutExerciseSetController::class, 'toggleStatus'])->name('workout-exercise-sets.toggle-status');
        
        // Programs Management - Additional routes MUST come before resource routes
        Route::get('programs/stats', [\App\Http\Controllers\Admin\ProgramController::class, 'getStats'])->name('programs.stats');
        Route::post('programs/{program}/duplicate', [\App\Http\Controllers\Admin\ProgramController::class, 'duplicate'])->name('programs.duplicate');
        Route::patch('programs/{program}/toggle-status', [\App\Http\Controllers\Admin\ProgramController::class, 'toggleStatus'])->name('programs.toggle-status');
        
        // Resource routes for programs
        Route::resource('programs', \App\Http\Controllers\Admin\ProgramController::class);
        
        // Program Builder Routes
        Route::prefix('program-builder')->group(function () {
            Route::get('/{program}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'show'])->name('program-builder.show');
            // Column Configuration (CRUD via whole-array upserts)
            Route::get('/{program}/columns', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'getColumnConfig'])->name('program-builder.columns.show');
            Route::put('/{program}/columns', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateColumnConfig'])->name('program-builder.columns.update');
            
            // Week management
            Route::post('/{program}/weeks', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'addWeek'])->name('program-builder.weeks.store');
            Route::get('/weeks/{week}/edit', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'editWeek'])->name('program-builder.weeks.edit');
            Route::put('/weeks/{week}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateWeek'])->name('program-builder.weeks.update');
            Route::post('/weeks/{week}/duplicate', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'duplicateWeek'])->name('program-builder.weeks.duplicate');
            Route::delete('/weeks/{week}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'removeWeek'])->name('program-builder.weeks.destroy');
            Route::put('/{program}/weeks/reorder', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'reorderWeeks'])->name('program-builder.weeks.reorder');
            
            // Day management
            Route::post('/weeks/{week}/days', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'addDay'])->name('program-builder.days.store');
            Route::get('/days/{day}/edit', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'editDay'])->name('program-builder.days.edit');
            Route::post('/days/{day}/duplicate', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'duplicateDay'])->name('program-builder.days.duplicate');
            Route::put('/days/{day}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateDay'])->name('program-builder.days.update');
            Route::delete('/days/{day}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'removeDay'])->name('program-builder.days.destroy');
            Route::put('/weeks/{week}/days/reorder', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'reorderDays'])->name('program-builder.days.reorder');
            
            // Circuit management
            Route::post('/days/{day}/circuits', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'addCircuit'])->name('program-builder.circuits.store');
            Route::get('/circuits/{circuit}/edit', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'editCircuit'])->name('program-builder.circuits.edit');
            Route::put('/circuits/{circuit}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateCircuit'])->name('program-builder.circuits.update');
            Route::delete('/circuits/{circuit}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'removeCircuit'])->name('program-builder.circuits.destroy');
            Route::put('/days/{day}/circuits/reorder', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'reorderCircuits'])->name('program-builder.circuits.reorder');
            
            // Exercise management
                Route::post('/circuits/{circuit}/exercises', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'addExercise'])->name('program-builder.exercises.add');
                Route::get('/exercises/{exercise}/edit', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'editExercise'])->name('program-builder.exercises.edit');
                Route::put('/exercises/{programExercise}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateExercise'])->name('program-builder.exercises.update');
                Route::put('/exercises/{programExercise}/workout', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateExerciseWorkout'])->name('program-builder.exercises.update-workout');
                Route::delete('/exercises/{programExercise}', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'removeExercise'])->name('program-builder.exercises.remove');
                Route::post('/circuits/{circuit}/exercises/reorder', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'reorderExercises'])->name('program-builder.exercises.reorder');
            
            // Exercise sets management
            Route::get('/exercises/{programExercise}/sets', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'manageSets'])->name('program-builder.sets.manage');
            Route::put('/exercises/{exercise}/sets', [\App\Http\Controllers\Admin\ProgramBuilderController::class, 'updateExerciseSets'])->name('program-builder.sets.update');
        });
        
        // Nutrition Plans Management
        Route::prefix('nutrition-plans')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'index'])->name('admin.nutrition-plans.index');
            Route::get('/create', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'create'])->name('admin.nutrition-plans.create');
            Route::post('/store', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'store'])->name('admin.nutrition-plans.store');
            Route::get('/{id}', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'show'])->name('admin.nutrition-plans.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'edit'])->name('admin.nutrition-plans.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'update'])->name('admin.nutrition-plans.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'destroy'])->name('admin.nutrition-plans.destroy');
            Route::patch('/{id}/toggle-status', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'toggleStatus'])->name('admin.nutrition-plans.toggle-status');
            Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'duplicate'])->name('admin.nutrition-plans.duplicate');
            Route::delete('/{id}/delete-media', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'deleteMedia'])->name('admin.nutrition-plans.delete-media');
            
            // Enhanced nutrition plan management routes
            Route::get('/{id}/recommendations', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'recommendations'])->name('admin.nutrition-plans.recommendations');
            Route::put('/{id}/recommendations', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'updateRecommendations'])->name('admin.nutrition-plans.update-recommendations');
            Route::get('/{id}/food-diary', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'foodDiary'])->name('admin.nutrition-plans.food-diary');
            Route::get('/categories', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'getCategories'])->name('admin.nutrition-plans.categories');
            
            // Nutrition Calculator routes
            Route::get('/{id}/calculator', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'calculator'])->name('admin.nutrition-plans.calculator');
            Route::post('/calculate-nutrition', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'calculateNutrition'])->name('admin.nutrition-plans.calculate-nutrition');
            Route::post('/{id}/save-calculated-nutrition', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'saveCalculatedNutrition'])->name('admin.nutrition-plans.save-calculated-nutrition');
            Route::get('/{id}/calculator-data', [\App\Http\Controllers\Admin\NutritionPlansController::class, 'getCalculatorData'])->name('admin.nutrition-plans.calculator-data');
            
            // Nutrition Meals Management
            Route::prefix('{planId}/meals')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'index'])->name('admin.nutrition-plans.meals.index');
                Route::get('/create', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'create'])->name('admin.nutrition-plans.meals.create');
                Route::post('/store', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'store'])->name('admin.nutrition-plans.meals.store');
                Route::get('/{id}', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'show'])->name('admin.nutrition-plans.meals.show');
                Route::get('/{id}/edit', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'edit'])->name('admin.nutrition-plans.meals.edit');
                Route::put('/{id}', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'update'])->name('admin.nutrition-plans.meals.update');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'destroy'])->name('admin.nutrition-plans.meals.destroy');
                Route::patch('/reorder', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'reorder'])->name('admin.nutrition-plans.meals.reorder');
                Route::delete('/{id}/delete-image', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'deleteImage'])->name('admin.nutrition-plans.meals.delete-image');
                
                // Enhanced meal management routes
                Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'duplicate'])->name('admin.nutrition-plans.meals.duplicate');
                Route::post('/copy-from-global', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'copyFromGlobal'])->name('admin.nutrition-plans.meals.copy-from-global');
                Route::get('/global-meals', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'getGlobalMeals'])->name('admin.nutrition-plans.meals.global-meals');
                Route::delete('/bulk-delete', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'bulkDelete'])->name('admin.nutrition-plans.meals.bulk-delete');
                Route::put('/{id}/macros', [\App\Http\Controllers\Admin\NutritionMealsController::class, 'updateMacros'])->name('admin.nutrition-plans.meals.update-macros');
            });
            
            // Nutrition Recipes Management
            Route::prefix('{planId}/recipes')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'index'])->name('admin.nutrition-plans.recipes.index');
                Route::get('/create', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'create'])->name('admin.nutrition-plans.recipes.create');
                Route::post('/store', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'store'])->name('admin.nutrition-plans.recipes.store');
                Route::get('/{id}', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'show'])->name('admin.nutrition-plans.recipes.show');
                Route::get('/{id}/edit', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'edit'])->name('admin.nutrition-plans.recipes.edit');
                Route::put('/{id}', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'update'])->name('admin.nutrition-plans.recipes.update');
                Route::delete('/{id}', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'destroy'])->name('admin.nutrition-plans.recipes.destroy');
                Route::patch('/reorder', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'reorder'])->name('admin.nutrition-plans.recipes.reorder');
                Route::delete('/{id}/delete-image', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'deleteImage'])->name('admin.nutrition-plans.recipes.delete-image');
                
                // Enhanced recipe management routes
                Route::post('/{id}/duplicate', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'duplicate'])->name('admin.nutrition-plans.recipes.duplicate');
                Route::delete('/bulk-delete', [\App\Http\Controllers\Admin\NutritionRecipesController::class, 'bulkDelete'])->name('admin.nutrition-plans.recipes.bulk-delete');
            });
        });
        
        /**
         * SCHEDULING & BOOKING MANAGEMENT
         * Complete booking management system for administrators
         */
        Route::prefix('bookings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BookingController::class, 'index'])->name('admin.bookings.index');
            Route::get('/dashboard', [\App\Http\Controllers\Admin\BookingController::class, 'dashboard'])->name('admin.bookings.dashboard');
            Route::get('/create', [\App\Http\Controllers\Admin\BookingController::class, 'create'])->name('admin.bookings.create');
            Route::post('/store', [\App\Http\Controllers\Admin\BookingController::class, 'store'])->name('admin.bookings.store');
            Route::get('/{id}/show', [\App\Http\Controllers\Admin\BookingController::class, 'show'])->name('admin.bookings.show');
            Route::get('/{id}/edit', [\App\Http\Controllers\Admin\BookingController::class, 'edit'])->name('admin.bookings.edit');
            Route::put('/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'update'])->name('admin.bookings.update');
            Route::delete('/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'destroy'])->name('admin.bookings.destroy');
            Route::patch('/bulk-update', [\App\Http\Controllers\Admin\BookingController::class, 'bulkUpdate'])->name('admin.bookings.bulk-update');
            Route::get('/export', [\App\Http\Controllers\Admin\BookingController::class, 'export'])->name('admin.bookings.export');
            
            // Scheduling & Booking Management Routes
            Route::get('/schedule', [\App\Http\Controllers\Admin\BookingController::class, 'schedule'])->name('admin.bookings.schedule');
            
            // Full Calendar API endpoints
            Route::get('/events', [\App\Http\Controllers\Admin\BookingController::class, 'getEvents'])->name('admin.bookings.events');
            Route::post('/events', [\App\Http\Controllers\Admin\BookingController::class, 'createEvent'])->name('admin.bookings.create-event');
            Route::put('/events/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'updateEvent'])->name('admin.bookings.update-event');
            Route::delete('/events/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'deleteEvent'])->name('admin.bookings.delete-event');
            
            Route::get('/scheduling-menu', [\App\Http\Controllers\Admin\BookingController::class, 'schedulingMenu'])->name('admin.bookings.scheduling-menu');
            Route::get('/availability', [\App\Http\Controllers\Admin\BookingController::class, 'availability'])->name('admin.bookings.availability');
            Route::post('/availability', [\App\Http\Controllers\Admin\BookingController::class, 'updateAvailability'])->name('admin.bookings.availability.update');
            Route::get('/blocked-times', [\App\Http\Controllers\Admin\BookingController::class, 'blockedTimes'])->name('admin.bookings.blocked-times');
            Route::post('/blocked-times', [\App\Http\Controllers\Admin\BookingController::class, 'storeBlockedTime'])->name('admin.bookings.blocked-times.store');
            Route::delete('/blocked-times/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'destroyBlockedTime'])->name('admin.bookings.blocked-times.destroy');
            Route::get('/session-capacity', [\App\Http\Controllers\Admin\BookingController::class, 'sessionCapacity'])->name('admin.bookings.session-capacity');
            Route::post('/session-capacity', [\App\Http\Controllers\Admin\BookingController::class, 'updateSessionCapacity'])->name('admin.bookings.session-capacity.update');
            Route::get('/booking-approval', [\App\Http\Controllers\Admin\BookingController::class, 'bookingApproval'])->name('admin.bookings.booking-approval');
            Route::post('/booking-approval', [\App\Http\Controllers\Admin\BookingController::class, 'updateBookingApproval'])->name('admin.bookings.booking-approval.update');
            
            // Google Calendar Booking Routes
            Route::get('/google-calendar', [\App\Http\Controllers\Admin\BookingController::class, 'googleCalendarBooking'])->name('admin.bookings.google-calendar');
            Route::post('/google-calendar', [\App\Http\Controllers\Admin\BookingController::class, 'storeGoogleCalendarBooking'])->name('admin.bookings.google-calendar.store');
            Route::get('/google-calendar/{id}/edit', [\App\Http\Controllers\Admin\BookingController::class, 'editGoogleCalendarBooking'])->name('admin.bookings.google-calendar.edit');
            Route::put('/google-calendar/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'updateGoogleCalendarBooking'])->name('admin.bookings.google-calendar.update');
            Route::delete('/google-calendar/{id}', [\App\Http\Controllers\Admin\BookingController::class, 'destroyGoogleCalendarBooking'])->name('admin.bookings.google-calendar.destroy');
            Route::post('/{id}/sync-google-calendar', [\App\Http\Controllers\Admin\BookingController::class, 'syncWithGoogleCalendar'])->name('admin.bookings.sync-google-calendar');
            Route::get('/trainer/{trainerId}/google-connection', [\App\Http\Controllers\Admin\BookingController::class, 'checkTrainerGoogleConnection'])->name('admin.bookings.trainer.google-connection');
            Route::get('/trainer/available-slots', [\App\Http\Controllers\Admin\BookingController::class, 'getTrainerAvailableSlots'])->name('admin.bookings.trainer.available-slots');
            
            // Admin Google Calendar Authentication Routes
            Route::get('/google/connect/{trainerId}', [\App\Http\Controllers\GoogleController::class, 'adminInitiatedTrainerConnect'])->name('admin.google.connect');
            // Route::get('/google/callback', [\App\Http\Controllers\GoogleController::class, 'adminInitiatedTrainerCallback'])->name('admin.google.callback');
        });

        /**
         * TRAINERS SCHEDULING MANAGEMENT
         * Admin overview of all trainers' scheduling settings
         */
        Route::get('/trainers-scheduling', [\App\Http\Controllers\Admin\BookingController::class, 'trainersScheduling'])->name('admin.trainers-scheduling.index');

        /**
         * BILLING MANAGEMENT
         * Admin billing dashboard, invoices and payouts
         */
        Route::prefix('billing')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\BillingController::class, 'index'])->name('admin.billing.index');
            Route::get('/invoices', [\App\Http\Controllers\Admin\BillingController::class, 'invoices'])->name('admin.billing.invoices');
            Route::get('/payouts', [\App\Http\Controllers\Admin\BillingController::class, 'payouts'])->name('admin.billing.payouts');
        });
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
        
        // Google Calendar Management Routes for Trainers
        Route::prefix('google-calendar')->name('trainer.google.')->group(function () {
            Route::get('/', [TrainerDashboardController::class, 'googleCalendar'])->name('index');
            Route::get('/connect', [\App\Http\Controllers\GoogleController::class, 'trainerConnect'])->name('connect');
            Route::get('/status', [\App\Http\Controllers\GoogleController::class, 'getConnectionStatus'])->name('status');
            Route::delete('/disconnect', [\App\Http\Controllers\GoogleController::class, 'disconnectGoogle'])->name('disconnect');
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
        Route::get('/{id}/certifications', [TrainerWebController::class, 'indexCertifications'])->name('trainers.certifications.index');
        Route::get('/{id}/certifications/create', [TrainerWebController::class, 'createCertification'])->name('trainers.certifications.create');
        // Route::post('/{id}/certifications', [TrainerWebController::class, 'storeCertification'])->name('trainers.certifications.store');
        Route::delete('/{id}/certifications/{certificationId}', [TrainerWebController::class, 'deleteCertification'])->name('trainers.certifications.destroy');
        
        // Testimonial management (only clients can add testimonials for trainers)
        Route::get('/{id}/testimonials/create', [TrainerWebController::class, 'createTestimonial'])->name('trainers.testimonials.create');
        Route::post('/{id}/testimonials', [TrainerWebController::class, 'storeTestimonial'])->name('trainers.testimonials.store');
    });
});



