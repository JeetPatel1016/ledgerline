<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Ledger\Account;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\LedgerException;
use App\Domain\Payments\TransferService;
use App\Http\ApiError;
use App\Http\Controllers\Controller;
use App\Http\Idempotency;
use App\Http\Requests\TransferRequest;
use Illuminate\Http\RedirectResponse;

class TransferController extends Controller
{
    /** Same flow as the API (Idempotency-Key header included); errors come back as a form error. */
    public function store(TransferRequest $request, TransferService $transfers): RedirectResponse
    {
        try {
            $transaction = Idempotency::once($request, fn (string $key, array $metadata) => $transfers->transfer(
                Account::findOrFail($request->integer('from')),
                Account::findOrFail($request->integer('to')),
                $request->integer('amount'),
                Currency::from($request->string('currency')->value()),
                $request->integer('fee'),
                $key,
                $metadata,
            ));
        } catch (ApiError|LedgerException $e) {
            return back()->withErrors(['transfer' => $e->getMessage()]);
        }

        return back()->with('success', "Transfer #{$transaction->id} posted.");
    }
}
