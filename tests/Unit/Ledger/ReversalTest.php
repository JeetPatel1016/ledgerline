<?php

declare(strict_types=1);

use App\Domain\Ledger\Direction;
use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\LedgerException;
use App\Domain\Ledger\TransactionKind;

it('reverses a transaction by flipping every entry and restoring balances', function () {
    $alice = fund(account(), 1000);
    $bob = account();
    $original = ledger()->post(TransactionKind::Transfer, 'pay-bob', [
        EntryLine::debit($alice, 400),
        EntryLine::credit($bob, 400),
    ]);

    $reversal = ledger()->reverse($original, 'undo-pay-bob');

    expect($reversal->kind)->toBe(TransactionKind::Reversal)
        ->and($reversal->reverses_id)->toBe($original->id)
        ->and($reversal->entries->firstWhere('account_id', $alice->id)?->direction)->toBe(Direction::Credit)
        ->and($reversal->entries->firstWhere('account_id', $bob->id)?->direction)->toBe(Direction::Debit)
        ->and($alice->refresh()->balance)->toBe(1000)
        ->and($bob->refresh()->balance)->toBe(0)
        ->and($original->entries()->count())->toBe(2);
});

it('reverses a transaction only once', function () {
    $original = fund(account(), 100)->entries()->firstOrFail()->transaction;
    ledger()->reverse($original, 'undo-1');

    expect(fn () => ledger()->reverse($original, 'undo-2'))
        ->toThrow(LedgerException::class, 'already been reversed');
});

it('does not reverse a reversal', function () {
    $original = fund(account(), 100)->entries()->firstOrFail()->transaction;
    $reversal = ledger()->reverse($original, 'undo');

    expect(fn () => ledger()->reverse($reversal, 'redo'))
        ->toThrow(LedgerException::class, 'cannot be reversed');
});

it('cannot reverse money that has already been spent', function () {
    $alice = account();
    $deposit = fund($alice, 500)->entries()->firstOrFail()->transaction;
    ledger()->post(TransactionKind::Transfer, 'spend', [
        EntryLine::debit($alice, 500),
        EntryLine::credit(account(), 500),
    ]);

    expect(fn () => ledger()->reverse($deposit, 'undo-deposit'))
        ->toThrow(LedgerException::class, 'cannot go negative');
});
