<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Auth\ChoosePasswordController;
use App\Http\Controllers\Admin\Auth\ForgotPasswordController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Back-office authentication
|--------------------------------------------------------------------------
|
| Sign-in and sign-out of the `admin` guard. Kept apart from the back-office
| block itself because these routes are the only ones reachable while the
| administrator is not authenticated yet.
|
*/

Route::middleware('guest:admin')->group(function (): void {
    Route::get('/connexion', LoginController::class)->name('login');
    Route::get('/mot-de-passe-oublie', ForgotPasswordController::class)->name('password.forgot');

    // The account by its number and the secret in the path · no address travels in a link.
    Route::get('/mot-de-passe/{account}/{token}', ChoosePasswordController::class)
        ->whereNumber('account')
        ->defaults('purpose', 'reset')
        ->name('password.reset');
    Route::get('/bienvenue/{account}/{token}', ChoosePasswordController::class)
        ->whereNumber('account')
        ->defaults('purpose', 'invitation')
        ->name('invitation');
});

Route::middleware('auth:admin')->group(function (): void {
    Route::post('/deconnexion', LogoutController::class)->name('logout');
});
