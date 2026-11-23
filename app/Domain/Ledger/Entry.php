<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Append-only: the database rejects updates and deletes.
 *
 * @property int $id
 * @property int $transaction_id
 * @property int $account_id
 * @property Direction $direction
 * @property int $amount Minor units, always positive.
 * @property Currency $currency
 */
#[Fillable(['transaction_id', 'account_id', 'direction', 'amount', 'currency'])]
class Entry extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /** @return BelongsTo<Account, $this> */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    protected function casts(): array
    {
        return [
            'direction' => Direction::class,
            'currency' => Currency::class,
            'amount' => 'integer',
        ];
    }
}
