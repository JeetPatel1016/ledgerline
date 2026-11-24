<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Ledger\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Transaction */
class TransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kind' => $this->kind,
            'status' => $this->status,
            'idempotency_key' => $this->idempotency_key,
            'reverses_id' => $this->reverses_id,
            'metadata' => $this->metadata,
            'posted_at' => $this->posted_at,
            'entries' => EntryResource::collection($this->entries),
        ];
    }
}
