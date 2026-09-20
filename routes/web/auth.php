<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Auth\Registration\UserRegistrationController;
use App\Http\Controllers\Web\User\WebUserLoginController;

/*
 * Login endpoints.
 *
 * throttle:login  - per credential+IP and per IP limits (config/security.php)
 * captcha:web     - reCAPTCHA verification, active when ENABLE_RECAPTCHA=1
 *
 * Account lockout is enforced inside the controller, since it needs the
 * resolved user record.
 */
Route::middleware(['throttle:login', 'captcha:web'])
    ->controller(LoginController::class)
    ->group(function () {
        Route::post('user/login', 'loginSpaUser')->name('spa-user-login');
        Route::post('admin-login', 'loginWebUsers')->name('spa-admin-login');
        Route::post('owner-login', 'loginFleetowners')->name('spa-owner-login');
        Route::post('dispatch-login', 'loginDispatchUsers')->name('spa-dispatcher-login');
        Route::post('dispatch-pro-login', 'loginDispatchProUsers')->name('spa-dispatcher-pro-login');
        Route::post('agent-login', 'loginAgentUsers')->name('spa-agent-login');
    });


// Login  Frontend
Route::middleware('guest')->controller(WebUserLoginController::class)->group(function () {


    Route::get('owner-login','Ownerindex')->name('owner-login');




});

Route::middleware(['throttle:register', 'captcha:web'])
    ->controller(UserRegistrationController::class)
    ->group(function () {
        Route::post('user/register', 'register')->name('user-register');
    });
