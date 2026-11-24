<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ledger\Account;
use App\Domain\Ledger\Currency;
use App\Domain\Payments\TransferService;
use App\Http\Controllers\Controller;
use App\Http\Idempotency;
use App\Http\Requests\TransferRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
    public function store(TransferRequest $request, TransferService $transfers): JsonResponse
    {
        $transaction = Idempotency::once($request, fn (string $key, array $metadata) => $transfers->transfer(
            Account::findOrFail($request->integer('from')),
            Account::findOrFail($request->integer('to')),
            $request->integer('amount'),
            Currency::from($request->string('currency')->value()),
            $request->integer('fee'),
            $key,
            $metadata,
        ));

        return TransactionResource::make($transaction)->response()->setStatusCode(201);
    }
}
