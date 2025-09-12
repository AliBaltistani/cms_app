<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiAuthController;
use App\Http\Controllers\ApiGoalController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/goals', [ApiGoalController::class, 'index']);
    Route::post('/goals', [ApiGoalController::class, 'store']);
});



Route::controller(ApiAuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
});