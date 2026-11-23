<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Dashboard', ['appName' => config('app.name')])->name('dashboard');
