<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Ledger\Account;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\AccountResource;
use App\Http\Resources\EntryResource;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'accounts' => AccountResource::collection(Account::orderBy('id')->get())->resolve(),
        ]);
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        $account = Account::create($request->validated());

        return back()->with('success', "Account #{$account->id} created.");
    }

    public function show(Account $account): Response
    {
        $page = $account->entries()->orderByDesc('id')->cursorPaginate(25);

        return Inertia::render('Account', [
            'account' => AccountResource::make($account)->resolve(),
            'entries' => EntryResource::collection($page->items())->resolve(),
            'nextCursor' => $page->nextCursor()?->encode(),
            'prevCursor' => $page->previousCursor()?->encode(),
        ]);
    }
}
