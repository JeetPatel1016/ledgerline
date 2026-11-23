<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use DomainException;

final class LedgerException extends DomainException
{
    public static function tooFewEntries(): self
    {
        return new self('A transaction needs at least two entries.');
    }

    public static function nonPositiveAmount(): self
    {
        return new self('Entry amounts must be positive integers in minor units.');
    }

    public static function unknownAccount(int $id): self
    {
        return new self("Account {$id} does not exist.");
    }

    public static function unbalanced(Currency $currency): self
    {
        return new self("Debits and credits do not balance for {$currency->value}.");
    }

    public static function insufficientFunds(Account $account): self
    {
        return new self("Account {$account->id} cannot go negative.");
    }

    public static function notReversible(Transaction $transaction): self
    {
        return new self("Transaction {$transaction->id} cannot be reversed.");
    }

    public static function alreadyReversed(Transaction $transaction): self
    {
        return new self("Transaction {$transaction->id} has already been reversed.");
    }
}
