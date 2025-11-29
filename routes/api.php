<?php

use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

Route::prefix('v1/logincontroller')->group(function () {
    Route::post('/login',  [LoginController::class, 'auth'])
        ->middleware('throttle:stellar-login');

    Route::post('/register',  [LoginController::class, 'create'])
        ->middleware('throttle:stellar-login');

    Route::post('/password/forgot', [LoginController::class, 'sendResetLink'])
        ->middleware('throttle:stellar-login');

    Route::post('/password/reset',  [LoginController::class, 'verifyReset'])
        ->middleware('throttle:stellar-login');
});

Route::prefix('v1/dashboardcontroller')->group(function () {
    Route::post('/home',  [DashboardController::class, 'home'])->middleware('throttle:10,1');
});


// Define custom rate limiter for login
RateLimiter::for('stellar-login', function (Request $request) {
    $username = (string) $request->input('username', 'guest');
    $ip = (string) $request->ip();

    return [
        Limit::perMinute(10)->by($username.'|'.$ip),
        Limit::perMinute(30)->by($ip),
    ];
});
