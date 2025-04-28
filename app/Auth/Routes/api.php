<?php

use Illuminate\Support\Facades\Route;
use App\Auth\Http\Controllers\AuthController;


Route::group(['prefix' => 'auth'], static function () {
    Route::group(['middleware' => 'auth:sanctum'], static function () {
        Route::get('/user', [AuthController::class, 'user'])->name('auth.user');
        Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    });

    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

