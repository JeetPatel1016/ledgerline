<?php

declare(strict_types=1);

use App\Domain\Ledger\Account;
use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\Direction;
use App\Domain\Ledger\EntryLine;
use App\Domain\Ledger\LedgerService;
use App\Domain\Ledger\TransactionKind;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Unit');

function ledger(): LedgerService
{
    return app(LedgerService::class);
}

function account(
    AccountType $type = AccountType::Liability,
    Currency $currency = Currency::CAD,
    bool $allowNegative = false,
): Account {
    return Account::create([
        'name' => $type->value.' '.Str::random(6),
        'type' => $type,
        'currency' => $currency,
        'allow_negative' => $allowNegative,
    ]);
}

/** Give $account a balance of $amount from a fresh counterparty account. */
function fund(Account $account, int $amount): Account
{
    $normal = $account->type->normalDirection();
    $source = account($normal === Direction::Credit ? AccountType::Asset : AccountType::Equity, $account->currency);

    ledger()->post(TransactionKind::Transfer, (string) Str::uuid(), [
        new EntryLine($account->id, $normal, $amount),
        new EntryLine($source->id, $normal->opposite(), $amount),
    ]);

    return $account->refresh();
}
