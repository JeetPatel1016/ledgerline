<?php

declare(strict_types=1);

use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\TransactionKind;
use Illuminate\Support\Facades\DB;

it('locks every affected account in one query, in ascending id order', function () {
    $low = fund(account(), 500);
    $high = account();

    DB::enableQueryLog();
    // Lines name the higher id first; the lock order must not follow line order.
    ledger()->post(TransactionKind::Transfer, 'lock-order', [EntryLine::credit($high, 100), EntryLine::debit($low, 100)]);
    $locks = collect(DB::getQueryLog())->pluck('query')->filter(fn (string $sql) => str_contains($sql, 'for update'));

    expect($locks)->toHaveCount(1)
        ->and($locks->first())->toContain('order by "id" asc');
});
