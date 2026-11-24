<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Ledger\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Account */
class AccountResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'currency' => $this->currency,
            'allow_negative' => $this->allow_negative,
            'balance' => ['amount' => $this->balance, 'currency' => $this->currency],
            'version' => $this->version,
            'created_at' => $this->created_at,
        ];
    }
}
