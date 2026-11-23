<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property TransactionKind $kind
 * @property TransactionStatus $status
 * @property string $idempotency_key
 * @property int|null $reverses_id
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable|null $posted_at
 * @property int|null $created_by
 * @property-read EloquentCollection<int, Entry> $entries
 */
#[Fillable(['kind', 'status', 'idempotency_key', 'reverses_id', 'metadata', 'posted_at', 'created_by'])]
class Transaction extends Model
{
    /** @return HasMany<Entry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class);
    }

    /** @return BelongsTo<Transaction, $this> */
    public function reverses(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reverses_id');
    }

    /** @return HasOne<Transaction, $this> */
    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reverses_id');
    }

    protected function casts(): array
    {
        return [
            'kind' => TransactionKind::class,
            'status' => TransactionStatus::class,
            'metadata' => 'array',
            'posted_at' => 'immutable_datetime',
        ];
    }
}
