<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property AccountType $type
 * @property Currency $currency
 * @property bool $allow_negative
 * @property int $balance Cached sum of entries, in minor units, positive in the type's normal direction.
 * @property int $version Incremented on every posting that touches the account.
 */
#[Fillable(['name', 'type', 'currency', 'allow_negative'])]
class Account extends Model
{
    protected $attributes = ['balance' => 0, 'version' => 0, 'allow_negative' => false];

    /** @return HasMany<Entry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /** Signed change to this account's balance from one entry. */
    public function delta(Direction $direction, int $amount): int
    {
        return $direction === $this->type->normalDirection() ? $amount : -$amount;
    }

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'currency' => Currency::class,
            'allow_negative' => 'boolean',
            'balance' => 'integer',
            'version' => 'integer',
        ];
    }
}
