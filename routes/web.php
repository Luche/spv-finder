<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SupervisorController;
use Illuminate\Support\Facades\Route;

Route::middleware('student.cookie')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/search', [SearchController::class, 'search'])->name('search');
    Route::get('/supervisor/{kddsn}', [SupervisorController::class, 'show'])->name('supervisor.show');
    Route::post('/supervisor/{kddsn}/contact', [SupervisorController::class, 'contact'])->name('supervisor.contact');
    Route::post('/identity', [IdentityController::class, 'store'])->name('identity.store');
});
