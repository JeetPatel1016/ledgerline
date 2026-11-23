<?php

declare(strict_types=1);

use App\Domain\Ledger\Account;
use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\Direction;
use App\Domain\Ledger\LedgerException;
use App\Domain\Ledger\Transaction;
use App\Domain\Payments\TransferService;

beforeEach(function () {
    $this->fees = Account::create(['name' => 'Fees CAD', 'code' => 'fees_cad', 'type' => AccountType::Revenue, 'currency' => Currency::CAD]);
    $this->from = fund(account(), 1_000);
    $this->to = account();
});

it('debits amount plus fee and credits the recipient and the fees account', function () {
    $tx = app(TransferService::class)->transfer($this->from, $this->to, 700, Currency::CAD, 30, 'fee-1');

    $lines = $tx->entries->map(fn ($e) => [$e->account_id, $e->direction, $e->amount])->all();
    expect($lines)->toEqualCanonicalizing([
        [$this->from->id, Direction::Debit, 730],
        [$this->to->id, Direction::Credit, 700],
        [$this->fees->id, Direction::Credit, 30],
    ]);
    expect([$this->from->refresh()->balance, $this->to->refresh()->balance, $this->fees->refresh()->balance])
        ->toBe([270, 700, 30]);
});

it('posts nothing when the source can afford the amount but not the fee', function () {
    $before = Transaction::count();

    expect(fn () => app(TransferService::class)->transfer($this->from, $this->to, 1_000, Currency::CAD, 1, 'fee-2'))
        ->toThrow(LedgerException::class, 'cannot go negative');

    expect(Transaction::count())->toBe($before)
        ->and($this->fees->refresh()->balance)->toBe(0)
        ->and($this->to->refresh()->balance)->toBe(0);
});

it('skips the fee line when the fee is zero', function () {
    $tx = app(TransferService::class)->transfer($this->from, $this->to, 100, Currency::CAD, 0, 'fee-3');

    expect($tx->entries)->toHaveCount(2);
});
