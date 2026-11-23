<?php

declare(strict_types=1);

use App\Domain\Ledger\Account;
use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\TransactionKind;
use Illuminate\Console\Scheduling\Schedule;

it('passes when every cached balance matches its entries', function () {
    $alice = fund(account(), 1000);
    ledger()->post(TransactionKind::Transfer, 'pay', [EntryLine::debit($alice, 250), EntryLine::credit(account(), 250)]);

    expect(ledger()->balanceMismatches())->toBe([]);
    $this->artisan('ledger:verify-balances')->assertSuccessful();
});

it('reports an account whose cached balance has drifted', function () {
    $alice = fund(account(), 1000);
    Account::whereKey($alice->id)->update(['balance' => 900]);

    expect(ledger()->balanceMismatches())->toBe([['account_id' => $alice->id, 'cached' => 900, 'actual' => 1000]]);
    $this->artisan('ledger:verify-balances')->assertFailed();
});

it('runs every night', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'ledger:verify-balances'));

    expect($event?->expression)->toBe('0 2 * * *');
});
