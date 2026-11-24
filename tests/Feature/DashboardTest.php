<?php

declare(strict_types=1);

use App\Domain\Ledger\Transaction;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('lists accounts with their balances', function () {
    $wallet = fund(account(), 1250);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('appName', config('app.name'))
            ->where('accounts', fn ($accounts) => collect($accounts)->firstWhere('id', $wallet->id)['balance'] === ['amount' => 1250, 'currency' => 'CAD']));
});

it('creates an account from the form', function () {
    $this->post('/accounts', ['name' => 'Alice', 'type' => 'liability', 'currency' => 'CAD'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->post('/accounts', ['name' => '', 'type' => 'nope', 'currency' => 'CAD'])
        ->assertSessionHasErrors(['name', 'type']);
});

it('shows an account with its entries, newest first', function () {
    $wallet = fund(fund(account(), 100), 200);

    $this->get("/accounts/{$wallet->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Account')
            ->where('account.id', $wallet->id)
            ->has('entries', 2)
            ->where('entries.0.amount.amount', 200)
            ->where('nextCursor', null));
});

it('posts a transfer once per Idempotency-Key', function () {
    $from = fund(account(), 1_000);
    $to = account();
    $body = ['from' => $from->id, 'to' => $to->id, 'amount' => 300, 'currency' => 'CAD', 'fee' => 0];

    $this->withHeader('Idempotency-Key', 'web-1')->post('/transfers', $body)->assertSessionHas('success');
    $this->withHeader('Idempotency-Key', 'web-1')->post('/transfers', $body)->assertSessionHas('success');

    expect($from->refresh()->balance)->toBe(700)
        ->and(Transaction::where('idempotency_key', 'web-1')->count())->toBe(1);
});

it('shows ledger and idempotency errors as a form error', function () {
    $from = fund(account(), 1_000);
    $to = account();
    $body = ['from' => $from->id, 'to' => $to->id, 'amount' => 5_000, 'currency' => 'CAD'];

    $this->withHeader('Idempotency-Key', 'web-2')->post('/transfers', $body)
        ->assertSessionHasErrors(['transfer' => "Account {$from->id} cannot go negative."]);

    $this->flushHeaders()->post('/transfers', [...$body, 'amount' => 1])
        ->assertSessionHasErrors('transfer');
});
