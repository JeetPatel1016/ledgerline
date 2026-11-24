<?php

declare(strict_types=1);

use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

beforeEach(fn () => Sanctum::actingAs(User::factory()->create()));

it('creates an account', function () {
    $this->postJson('/api/v1/accounts', ['name' => 'Alice wallet', 'type' => 'liability', 'currency' => 'CAD'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Alice wallet')
        ->assertJsonPath('data.type', 'liability')
        ->assertJsonPath('data.allow_negative', false)
        ->assertJsonPath('data.balance', ['amount' => 0, 'currency' => 'CAD']);
});

it('rejects an invalid account with the error shape', function () {
    $this->postJson('/api/v1/accounts', ['name' => 'X', 'type' => 'piggybank', 'currency' => 'EUR'])
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['code', 'message', 'details' => ['type', 'currency']]]);
});

it('shows an account with its balance', function () {
    $wallet = fund(account(AccountType::Liability, Currency::USD), 1250);

    $this->getJson("/api/v1/accounts/{$wallet->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $wallet->id)
        ->assertJsonPath('data.balance', ['amount' => 1250, 'currency' => 'USD']);
});

it('returns not_found for a missing account', function () {
    $this->getJson('/api/v1/accounts/999999')
        ->assertNotFound()
        ->assertExactJson(['error' => ['code' => 'not_found', 'message' => 'Not found.', 'details' => []]]);
});

it('lists entries newest first with cursor pagination', function () {
    $wallet = account();
    foreach ([100, 200, 300] as $amount) {
        fund($wallet, $amount);
    }

    $first = $this->getJson("/api/v1/accounts/{$wallet->id}/entries")->assertOk();
    expect($first->json('data.*.amount.amount'))->toBe([300, 200, 100])
        ->and($first->json('data.0.account_id'))->toBe($wallet->id);
    expect($first->json('meta.next_cursor'))->toBeNull();
});

it('filters entries by date', function () {
    $wallet = account();
    $this->travelTo('2026-01-15 12:00:00');
    fund($wallet, 100);
    $this->travelBack();
    fund($wallet, 200);

    $this->getJson("/api/v1/accounts/{$wallet->id}/entries?from=2026-01-01&to=2026-01-31")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount.amount', 100);

    $this->getJson("/api/v1/accounts/{$wallet->id}/entries?from=2026-02-01")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount.amount', 200);
});

it('requires a token', function () {
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer nope')->getJson('/api/v1/accounts/1')
        ->assertUnauthorized()
        ->assertJsonPath('error.code', 'unauthenticated');
});

it('accepts a token issued by api:token', function () {
    $user = User::factory()->create();
    $this->app['auth']->forgetGuards();

    expect(Artisan::call('api:token', ['email' => $user->email]))->toBe(0);

    $this->withToken(trim(Artisan::output()))->postJson('/api/v1/accounts', ['name' => 'A', 'type' => 'asset', 'currency' => 'CAD'])
        ->assertCreated();
});
