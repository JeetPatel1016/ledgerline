<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

/** One side of a transaction to post. Its currency is the account's currency. */
final readonly class EntryLine
{
    public function __construct(
        public int $accountId,
        public Direction $direction,
        public int $amount,
    ) {}

    public static function debit(Account $account, int $amount): self
    {
        return new self($account->id, Direction::Debit, $amount);
    }

    public static function credit(Account $account, int $amount): self
    {
        return new self($account->id, Direction::Credit, $amount);
    }
}
