<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ledger\Account;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListEntriesRequest;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\EntryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AccountController extends Controller
{
    public function store(StoreAccountRequest $request): JsonResponse
    {
        return AccountResource::make(Account::create($request->validated())->refresh())
            ->response()
            ->setStatusCode(201);
    }

    public function show(Account $account): AccountResource
    {
        return AccountResource::make($account);
    }

    /** Newest first, cursor-paginated; from/to filter by entry date (inclusive). */
    public function entries(ListEntriesRequest $request, Account $account): AnonymousResourceCollection
    {
        $entries = $account->entries()
            ->when($request->validated('from'), fn ($query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($request->validated('to'), fn ($query, string $to) => $query->whereDate('created_at', '<=', $to))
            ->orderByDesc('id')
            ->cursorPaginate(50);

        return EntryResource::collection($entries);
    }
}
