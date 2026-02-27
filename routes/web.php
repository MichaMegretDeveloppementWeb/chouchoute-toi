<?php

use App\Http\Controllers\Web\AboutController;
use App\Http\Controllers\Web\ContactController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\LegalController;
use App\Http\Controllers\Web\PrestationsController;
use App\Http\Controllers\Web\ReviewsController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/prestations', PrestationsController::class)->name('prestations');
Route::get('/a-propos', AboutController::class)->name('about');
Route::get('/avis', ReviewsController::class)->name('reviews');
Route::get('/contact', ContactController::class)->name('contact');
Route::get('/mentions-legales', LegalController::class)->name('legal');
