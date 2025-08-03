<?php

use App\Http\Controllers\Auth\AuthViewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthViewController::class)->group(function () {
    Route::get('/auth/sign/in', 'signin_page')->name('auth.sign.in');
    Route::get('/auth/sign/up', 'signup_page')->name('auth.sign.up');
    Route::get('/auth/forgot/password', 'password_forgot')->name('auth.forgot.password');
    Route::get('/auth/reset/password', 'password_reset')->name('auth.reset.password');
});

Route::controller(PasswordController::class)->group(function () {
    Route::post('/password/forgot', 'forgot_password')->name('forgot.password');
    Route::post('/password/reset', 'reset_password')->name('reset.password');
});

Route::controller(RegisterController::class)->group(function () {
    Route::post('/sign/up', 'signUp')->name('sign.up');
    Route::any('/activate/account/{tracking_id}', 'activate_account')->name('activate.account');
});


Route::post('/sign/in', [LoginController::class, 'authenticate'])->name('sign.in');

Route::get('/get/new/csrf-token', function () {
    return response()->json([
        'token' => csrf_token(),
    ]);
})->name('get.new.csrf-token');