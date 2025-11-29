<?php

use App\Http\Controllers\V1\LoginController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/logincontroller')->group(function () {
    Route::post('/login',  [LoginController::class, 'login'])
        ->middleware('throttle:stellar-login');

    Route::post('/register',  [LoginController::class, 'register'])
        ->middleware('throttle:stellar-register');

    Route::post('/password/forgot', [LoginController::class, 'sendResetLink'])
        ->middleware('throttle:stellar-password');

    Route::post('/password/reset',  [LoginController::class, 'verifyReset'])
        ->middleware('throttle:stellar-password');
});
