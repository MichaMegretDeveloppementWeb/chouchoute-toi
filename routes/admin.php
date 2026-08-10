<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Back-office
|--------------------------------------------------------------------------
|
| Every route here runs behind the `admin` guard. The Analytics and Marketing
| modules of falcon/analytics mount themselves on admin/analytics and
| admin/marketing with the same stack, declared in config/analytics.php.
|
*/

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('/profil', ProfileController::class)->name('profile');
