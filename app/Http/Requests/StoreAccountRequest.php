<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency' => ['required', Rule::enum(Currency::class)],
            'allow_negative' => ['sometimes', 'boolean'],
        ];
    }
}
