<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\GoalsController;

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

    });
});