<?php

declare(strict_types=1);

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
});

Route::middleware('auth:admin')->group(function (): void {
    Route::post('/deconnexion', LogoutController::class)->name('logout');
});
