<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use Capell\Frontend\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', PageController::class)->middleware('capell-frontend')->name('home');
Route::post('/contact', ContactController::class)->middleware('throttle:contact')->name('contact.send');
