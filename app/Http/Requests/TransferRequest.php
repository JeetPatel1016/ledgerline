<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Ledger\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'from' => ['required', 'integer', 'exists:accounts,id'],
            'to' => ['required', 'integer', 'different:from', 'exists:accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'fee' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
