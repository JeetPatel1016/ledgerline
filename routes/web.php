<?php

declare(strict_types=1);

use App\Http\Controllers\Web\AccountController;
use App\Http\Controllers\Web\SessionController;
use App\Http\Controllers\Web\TransferController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:6,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/', [AccountController::class, 'index'])->name('dashboard');
    Route::post('/accounts', [AccountController::class, 'store']);
    Route::get('/accounts/{account}', [AccountController::class, 'show'])->name('accounts.show');

    Route::post('/transfers', [TransferController::class, 'store']);
});
