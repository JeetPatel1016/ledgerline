<?php

declare(strict_types=1);

use App\Domain\Ledger\Direction;
use App\Domain\Ledger\Entry;
use Symfony\Component\Process\Process;

// Needs real commits seen by other connections, so no RefreshDatabase:
// start from a fresh schema and leave one behind for the other suites.
beforeEach(fn () => $this->artisan('migrate:fresh'));
afterEach(fn () => $this->artisan('migrate:fresh'));

it('lets exactly 10 of 20 parallel withdrawals through and never goes negative', function () {
    $wallet = fund(account(), 1_000);
    $payout = account();
    $startAt = microtime(true) + 3; // time for every process to boot before any of them posts

    $processes = collect(range(1, 20))->map(function (int $i) use ($wallet, $payout, $startAt) {
        $process = new Process(
            [PHP_BINARY, __DIR__.'/withdraw.php', $wallet->id, $payout->id, 100, "withdrawal-{$i}", $startAt],
            env: ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => config('database.connections.pgsql.database')],
            timeout: 60,
        );
        $process->start();

        return $process;
    });

    $codes = $processes->map(fn (Process $process) => $process->wait());
    $errors = $processes->map(fn (Process $process) => $process->getErrorOutput())->filter()->values();

    expect($errors->all())->toBe([])
        ->and($codes->countBy()->sortKeys()->all())->toBe([0 => 10, 3 => 10])
        ->and($wallet->refresh()->balance)->toBe(0)
        ->and($payout->refresh()->balance)->toBe(1_000)
        ->and(Entry::where('account_id', $wallet->id)->where('direction', Direction::Debit)->count())->toBe(10)
        ->and(ledger()->balanceMismatches())->toBe([]);
});
