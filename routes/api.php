<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::post('accounts', [AccountController::class, 'store']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::get('accounts/{account}/entries', [AccountController::class, 'entries']);

    Route::post('transfers', [TransferController::class, 'store']);
});
