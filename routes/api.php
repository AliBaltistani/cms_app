<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiGoalController;
use App\Http\Controllers\WorkoutController;
use App\Http\Controllers\WorkoutVideoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    // Goal routes
    Route::get('/goals', [ApiGoalController::class, 'index']);
    Route::post('/goals', [ApiGoalController::class, 'store']);
    
    // Workout routes
    Route::prefix('workouts')->group(function () {
        // Route::get('/', [WorkoutController::class, 'index'])->name('api.workouts.index');
        Route::post('/', [WorkoutController::class, 'store'])->name('api.workouts.store');
        Route::get('/statistics', [WorkoutController::class, 'statistics'])->name('api.workouts.statistics');
        
        Route::prefix('{workout}')->group(function () {
            Route::get('/', [WorkoutController::class, 'show'])->name('api.workouts.show');
            Route::put('/', [WorkoutController::class, 'update'])->name('api.workouts.update');
            Route::delete('/', [WorkoutController::class, 'destroy'])->name('api.workouts.destroy');
            Route::patch('/toggle-status', [WorkoutController::class, 'toggleStatus'])->name('api.workouts.toggle-status');
            Route::post('/duplicate', [WorkoutController::class, 'duplicate'])->name('api.workouts.duplicate');
            
            // Video routes nested under workouts
            Route::prefix('videos')->group(function () {
                Route::get('/', [WorkoutVideoController::class, 'index'])->name('api.workout-videos.index');
                Route::post('/', [WorkoutVideoController::class, 'store'])->name('api.workout-videos.store');
                Route::patch('/reorder', [WorkoutVideoController::class, 'reorder'])->name('api.workout-videos.reorder');
                
                Route::prefix('{video}')->group(function () {
                    Route::get('/', [WorkoutVideoController::class, 'show'])->name('api.workout-videos.show');
                    Route::put('/', [WorkoutVideoController::class, 'update'])->name('api.workout-videos.update');
                    Route::delete('/', [WorkoutVideoController::class, 'destroy'])->name('api.workout-videos.destroy');
                });
            });
        });
    });

    // Bulk operations
    Route::prefix('bulk')->group(function () {
        Route::patch('workouts', [WorkoutController::class, 'bulkUpdate'])->name('api.workouts.bulk-update');
        Route::delete('workouts', [WorkoutController::class, 'bulkDelete'])->name('api.workouts.bulk-delete');
    });
});



Route::controller(ApiAuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
});


