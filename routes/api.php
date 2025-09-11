<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ApiAuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::controller(ApiAuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register');
});