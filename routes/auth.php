<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\Password\ChoosePasswordController;
use App\Http\Controllers\Auth\Password\ForgotPasswordController;
use App\Http\Controllers\Auth\Password\SignOutForTheLinkController;
use App\Http\Controllers\Auth\SignIn\LoginController;
use App\Http\Controllers\Auth\SignIn\LogoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| The sign-in space
|--------------------------------------------------------------------------
|
| Signing in and out of the `admin` guard, the forgotten password and the
| invitation · a space of its own, between the site and its back-office. Its
| addresses stay under `/admin`, where a visitor looks for them.
|
*/

Route::middleware('guest:admin')->group(function (): void {
    Route::get('/connexion', LoginController::class)->name('login');
    Route::get('/mot-de-passe-oublie', ForgotPasswordController::class)->name('password.forgot');
});

// The account by its number and the secret in the path · no address travels in a link.
// Open to an account already signed in, which the page tells who it is.
Route::middleware('throttle:password-links')->group(function (): void {
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

    // Signing out to open a link, then the link.
    Route::post('/lien/{purpose}/{account}/{token}', SignOutForTheLinkController::class)
        ->whereIn('purpose', ['reset', 'invitation'])
        ->whereNumber('account')
        ->name('link.sign-out');
});
