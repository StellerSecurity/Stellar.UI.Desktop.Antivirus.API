<?php

use App\Http\Controllers\V1\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::prefix('v1/logincontroller')->group(function () {
    Route::post('/login',  [LoginController::class, 'login']);
    Route::post('/register',  [LoginController::class, 'register']);
    Route::post('/password/forgot', [LoginController::class, 'sendResetLink']);
    Route::post('/password/reset',  [LoginController::class, 'verifyReset']);
});
