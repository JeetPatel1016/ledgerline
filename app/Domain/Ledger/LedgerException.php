<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use DomainException;

final class LedgerException extends DomainException
{
    /** @param  string  $errorCode  Stable machine-readable code, returned by the API. */
    private function __construct(string $message, public readonly string $errorCode)
    {
        parent::__construct($message);
    }

    public static function tooFewEntries(): self
    {
        return new self('A transaction needs at least two entries.', 'too_few_entries');
    }

    public static function nonPositiveAmount(): self
    {
        return new self('Entry amounts must be positive integers in minor units.', 'non_positive_amount');
    }

    public static function unknownAccount(int $id): self
    {
        return new self("Account {$id} does not exist.", 'unknown_account');
    }

    public static function unbalanced(Currency $currency): self
    {
        return new self("Debits and credits do not balance for {$currency->value}.", 'unbalanced');
    }

    public static function insufficientFunds(Account $account): self
    {
        return new self("Account {$account->id} cannot go negative.", 'insufficient_funds');
    }

    public static function notReversible(Transaction $transaction): self
    {
        return new self("Transaction {$transaction->id} cannot be reversed.", 'not_reversible');
    }

    public static function alreadyReversed(Transaction $transaction): self
    {
        return new self("Transaction {$transaction->id} has already been reversed.", 'already_reversed');
    }

    public static function currencyMismatch(Account $account, Currency $currency): self
    {
        return new self("Account {$account->id} holds {$account->currency->value}, not {$currency->value}.", 'currency_mismatch');
    }

    public static function noFeesAccount(Currency $currency): self
    {
        return new self("No fees account is set up for {$currency->value}.", 'no_fees_account');
    }
}
