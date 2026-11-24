<?php

declare(strict_types=1);

namespace App\Domain\Payments;

use App\Domain\Ledger\Account;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\LedgerException;
use App\Domain\Ledger\LedgerService;
use App\Domain\Ledger\Transaction;
use App\Domain\Ledger\TransactionKind;

final readonly class TransferService
{
    public function __construct(private LedgerService $ledger) {}

    /**
     * Debit $from by amount + fee, credit $to by amount and the fees account by fee,
     * all in one transaction so the transfer and its fee post or fail together.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function transfer(
        Account $from,
        Account $to,
        int $amount,
        Currency $currency,
        int $fee,
        string $idempotencyKey,
        array $metadata = [],
    ): Transaction {
        foreach ([$from, $to] as $account) {
            if ($account->currency !== $currency) {
                throw LedgerException::currencyMismatch($account, $currency);
            }
        }

        $lines = [EntryLine::debit($from, $amount + $fee), EntryLine::credit($to, $amount)];
        if ($fee > 0) {
            $lines[] = EntryLine::credit(Account::fees($currency) ?? throw LedgerException::noFeesAccount($currency), $fee);
        }

        return $this->ledger->post(TransactionKind::Transfer, $idempotencyKey, $lines, [
            'from' => $from->id,
            'to' => $to->id,
            'amount' => $amount,
            'fee' => $fee,
        ] + $metadata);
    }
}
