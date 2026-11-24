<?php

declare(strict_types=1);

use App\Domain\Ledger\LedgerService;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('ledger:verify-balances', function (LedgerService $ledger): int {
    $mismatches = $ledger->balanceMismatches();

    if ($mismatches === []) {
        $this->info('All cached balances match their entries.');

        return 0;
    }

    Log::critical('Ledger balance mismatch', ['mismatches' => $mismatches]);
    $this->table(['account_id', 'cached', 'actual'], $mismatches);

    return 1;
})->purpose('Check every cached account balance against the sum of its entries');

Schedule::command('ledger:verify-balances')->dailyAt('02:00');

Artisan::command('api:token {email}', function (string $email): int {
    $user = User::where('email', $email)->first();
    if ($user === null) {
        $this->error("No user with email {$email}.");

        return 1;
    }

    $this->line($user->createToken('cli')->plainTextToken);

    return 0;
})->purpose('Issue a Sanctum API token for a user and print it');
