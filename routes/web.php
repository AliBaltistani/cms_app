<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\DashboardsController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

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
    });
});