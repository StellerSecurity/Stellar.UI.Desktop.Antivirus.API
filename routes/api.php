<?php

use App\Http\Controllers\V1\LoginController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

Route::prefix('v1/logincontroller')->group(function () {
    Route::post('/login',  [LoginController::class, 'auth'])
        ->middleware('throttle:stellar-login');

    Route::post('/register',  [LoginController::class, 'register'])
        ->middleware('throttle:stellar-login');

    Route::post('/password/forgot', [LoginController::class, 'sendResetLink'])
        ->middleware('throttle:stellar-login');

    Route::post('/password/reset',  [LoginController::class, 'verifyReset'])
        ->middleware('throttle:stellar-login');
});





// Define custom rate limiter for login
RateLimiter::for('stellar-login', function (Request $request) {
    $username = (string) $request->input('username', 'guest');
    $ip = (string) $request->ip();

    return [
        // Max 10 login-forsøg pr. minut pr. bruger+ip
        Limit::perMinute(10)->by($username.'|'.$ip),

        // Ekstra fallback: max 30 requests pr. minut pr. IP
        Limit::perMinute(30)->by($ip),
    ];
});
