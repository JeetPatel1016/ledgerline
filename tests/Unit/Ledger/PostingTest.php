<?php

declare(strict_types=1);

use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\Direction;
use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\LedgerException;
use App\Domain\Ledger\Transaction;
use App\Domain\Ledger\TransactionKind;
use App\Domain\Ledger\TransactionStatus;
use App\Models\AuditLog;
use Illuminate\Database\UniqueConstraintViolationException;

it('posts a balanced transaction and updates cached balances and versions', function () {
    $cash = account(AccountType::Asset);
    $customer = account(AccountType::Liability);

    $transaction = ledger()->post(TransactionKind::Transfer, 'deposit-1', [
        EntryLine::debit($cash, 1250),
        EntryLine::credit($customer, 1250),
    ]);

    expect($transaction->status)->toBe(TransactionStatus::Posted)
        ->and($transaction->posted_at)->not->toBeNull()
        ->and($transaction->entries)->toHaveCount(2)
        ->and($transaction->entries->pluck('currency')->unique()->all())->toBe([Currency::CAD])
        ->and($cash->refresh()->balance)->toBe(1250)
        ->and($customer->refresh()->balance)->toBe(1250)
        ->and($cash->version)->toBe(1)
        ->and($customer->version)->toBe(1);
});

it('moves money between two customer accounts', function () {
    $alice = fund(account(), 1000);
    $bob = account();

    ledger()->post(TransactionKind::Transfer, 'alice-to-bob', [
        EntryLine::debit($alice, 300),
        EntryLine::credit($bob, 300),
    ]);

    expect($alice->refresh()->balance)->toBe(700)
        ->and($bob->refresh()->balance)->toBe(300);
});

it('rejects a transaction whose debits and credits differ, writing nothing', function () {
    $cash = account(AccountType::Asset);
    $customer = account();

    expect(fn () => ledger()->post(TransactionKind::Transfer, 'bad', [
        EntryLine::debit($cash, 1000),
        EntryLine::credit($customer, 999),
    ]))->toThrow(LedgerException::class, 'do not balance for CAD');

    expect(Transaction::count())->toBe(0)
        ->and($cash->refresh()->balance)->toBe(0)
        ->and($customer->refresh()->balance)->toBe(0);
});

it('balances per currency, not in total', function () {
    expect(fn () => ledger()->post(TransactionKind::Transfer, 'mixed', [
        EntryLine::debit(account(AccountType::Asset, Currency::CAD), 100),
        EntryLine::credit(account(AccountType::Liability, Currency::USD), 100),
    ]))->toThrow(LedgerException::class, 'do not balance');
});

it('accepts a transaction that balances in each of two currencies', function () {
    $cadCash = account(AccountType::Asset, Currency::CAD);
    $cadCustomer = account(AccountType::Liability, Currency::CAD);
    $usdCash = account(AccountType::Asset, Currency::USD);
    $usdCustomer = account(AccountType::Liability, Currency::USD);

    $transaction = ledger()->post(TransactionKind::Transfer, 'two-currencies', [
        EntryLine::debit($cadCash, 1370),
        EntryLine::credit($cadCustomer, 1370),
        EntryLine::debit($usdCash, 1000),
        EntryLine::credit($usdCustomer, 1000),
    ]);

    expect($transaction->entries)->toHaveCount(4)
        ->and($usdCustomer->refresh()->balance)->toBe(1000);
});

it('needs at least two entries', function () {
    expect(fn () => ledger()->post(TransactionKind::Transfer, 'one', [
        EntryLine::debit(account(AccountType::Asset), 100),
    ]))->toThrow(LedgerException::class, 'at least two entries');
});

it('rejects amounts that are not positive', function (int $amount) {
    expect(fn () => ledger()->post(TransactionKind::Transfer, 'zero', [
        EntryLine::debit(account(AccountType::Asset), $amount),
        EntryLine::credit(account(), $amount),
    ]))->toThrow(LedgerException::class, 'must be positive');
})->with([0, -500]);

it('rejects entries for accounts that do not exist', function () {
    expect(fn () => ledger()->post(TransactionKind::Transfer, 'ghost', [
        EntryLine::debit(account(AccountType::Asset), 100),
        new EntryLine(999_999, Direction::Credit, 100),
    ]))->toThrow(LedgerException::class, 'Account 999999 does not exist');
});

it('refuses to take an account below zero, writing nothing', function () {
    $alice = fund(account(), 500);
    $bob = account();
    $transactionsBefore = Transaction::count();

    expect(fn () => ledger()->post(TransactionKind::Transfer, 'overdraw', [
        EntryLine::debit($alice, 501),
        EntryLine::credit($bob, 501),
    ]))->toThrow(LedgerException::class, "Account {$alice->id} cannot go negative");

    expect(Transaction::count())->toBe($transactionsBefore)
        ->and($alice->refresh()->balance)->toBe(500)
        ->and($bob->refresh()->balance)->toBe(0);
});

it('lets an account that allows negative balances go below zero', function () {
    $settlement = account(AccountType::Liability, allowNegative: true);
    $customer = account();

    ledger()->post(TransactionKind::Transfer, 'advance', [
        EntryLine::debit($settlement, 800),
        EntryLine::credit($customer, 800),
    ]);

    expect($settlement->refresh()->balance)->toBe(-800);
});

it('rejects a reused idempotency key', function () {
    $post = fn () => ledger()->post(TransactionKind::Transfer, 'same-key', [
        EntryLine::debit(account(AccountType::Asset), 100),
        EntryLine::credit(account(), 100),
    ]);
    $post();

    expect($post)->toThrow(UniqueConstraintViolationException::class);
});

it('records every posting in the audit log', function () {
    $transaction = fund(account(), 100)->entries()->firstOrFail()->transaction;

    $log = AuditLog::sole();
    expect($log->action)->toBe('transaction.posted')
        ->and($log->subject_id)->toBe($transaction->id)
        ->and($log->after['entries'])->toHaveCount(2);
});
