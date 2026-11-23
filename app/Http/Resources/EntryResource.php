<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Ledger\Entry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Entry */
class EntryResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transaction_id' => $this->transaction_id,
            'account_id' => $this->account_id,
            'direction' => $this->direction,
            'amount' => ['amount' => $this->amount, 'currency' => $this->currency],
            'created_at' => $this->created_at,
        ];
    }
}
