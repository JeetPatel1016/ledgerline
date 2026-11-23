<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

enum AccountType: string
{
    case Asset = 'asset';
    case Liability = 'liability';
    case Equity = 'equity';
    case Revenue = 'revenue';
    case Expense = 'expense';

    /** The direction that increases this account's balance. */
    public function normalDirection(): Direction
    {
        return match ($this) {
            self::Asset, self::Expense => Direction::Debit,
            self::Liability, self::Equity, self::Revenue => Direction::Credit,
        };
    }
}
