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
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
    public function store(TransferRequest $request, TransferService $transfers): JsonResponse
    {
        $key = Idempotency::key($request);
        $hash = Idempotency::hash($request);

        $transaction = Idempotency::replay($key, $hash);
        if ($transaction === null) {
            try {
                $transaction = $transfers->transfer(
                    Account::findOrFail($request->integer('from')),
                    Account::findOrFail($request->integer('to')),
                    $request->integer('amount'),
                    Currency::from($request->string('currency')->value()),
                    $request->integer('fee'),
                    $key,
                    ['request_hash' => $hash],
                );
            } catch (UniqueConstraintViolationException $e) {
                // A concurrent request with the same key committed first.
                $transaction = Idempotency::replay($key, $hash) ?? throw $e;
            }
        }

        return TransactionResource::make($transaction)->response()->setStatusCode(201);
    }
}
