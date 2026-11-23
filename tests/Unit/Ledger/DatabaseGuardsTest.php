<?php

declare(strict_types=1);

use App\Domain\Ledger\Account;
use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Direction;
use App\Domain\Ledger\Entry;
use App\Domain\Ledger\Transaction;
use App\Domain\Ledger\TransactionKind;
use App\Domain\Ledger\TransactionStatus;
use App\Models\AuditLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

// These bypass LedgerService on purpose: the database must hold the rules on its own.
// The balance trigger is deferred to commit; tests force it with SET CONSTRAINTS ALL IMMEDIATE
// because RefreshDatabase never commits.

/** Insert a transaction with raw entries, skipping the service. */
function rawTransaction(Direction ...$directions): Transaction
{
    $transaction = Transaction::create([
        'kind' => TransactionKind::Transfer,
        'status' => TransactionStatus::Posted,
        'idempotency_key' => 'raw-'.uniqid(),
    ]);
    foreach ($directions as $direction) {
        $account = account(AccountType::Asset, allowNegative: true);
        $transaction->entries()->create([
            'account_id' => $account->id,
            'direction' => $direction,
            'amount' => 100,
            'currency' => $account->currency,
        ]);
    }

    return $transaction;
}

function checkDeferredConstraints(): void
{
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
}

it('rejects an unbalanced transaction at commit', function () {
    rawTransaction(Direction::Debit, Direction::Debit);

    expect(fn () => checkDeferredConstraints())->toThrow(QueryException::class, 'is unbalanced');
});

it('rejects a transaction with a single entry at commit', function () {
    rawTransaction(Direction::Debit);

    expect(fn () => checkDeferredConstraints())->toThrow(QueryException::class, 'fewer than two entries');
});

it('accepts what the service posts at commit', function () {
    fund(account(), 100);

    checkDeferredConstraints();

    expect(Entry::count())->toBe(2);
});

it('refuses to update an entry', function () {
    $entry = fund(account(), 100)->entries()->firstOrFail();

    expect(fn () => $entry->update(['amount' => 1]))->toThrow(QueryException::class, 'append-only');
});

it('refuses to delete an entry', function () {
    $entry = fund(account(), 100)->entries()->firstOrFail();

    expect(fn () => $entry->delete())->toThrow(QueryException::class, 'append-only');
});

it('refuses to truncate entries', function () {
    expect(fn () => DB::statement('TRUNCATE entries CASCADE'))->toThrow(QueryException::class, 'append-only');
});

it('refuses to change or delete audit log rows', function (string $operation) {
    fund(account(), 100);
    $log = AuditLog::sole();

    expect(fn () => $operation === 'update' ? $log->update(['action' => 'x']) : $log->delete())
        ->toThrow(QueryException::class, 'append-only');
})->with(['update', 'delete']);

it('rejects zero-amount entries', function () {
    $transaction = rawTransaction();

    expect(fn () => $transaction->entries()->create([
        'account_id' => account()->id,
        'direction' => Direction::Debit,
        'amount' => 0,
        'currency' => 'CAD',
    ]))->toThrow(QueryException::class, 'entries_amount_positive');
});

it('rejects a negative balance on an account that does not allow it', function () {
    $account = account();

    expect(fn () => Account::whereKey($account->id)->update(['balance' => -1]))
        ->toThrow(QueryException::class, 'accounts_non_negative');
});
