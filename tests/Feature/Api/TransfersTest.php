<?php

declare(strict_types=1);

use App\Domain\Ledger\Account;
use App\Domain\Ledger\AccountType;
use App\Domain\Ledger\Currency;
use App\Domain\Ledger\Transaction;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
    $this->from = fund(account(), 10_000);
    $this->to = account();
});

function postTransfer(array $body, ?string $key = 'key-1')
{
    return test()->withHeaders($key === null ? [] : ['Idempotency-Key' => $key])->postJson('/api/v1/transfers', $body);
}

function transferBody(array $overrides = []): array
{
    return ['from' => test()->from->id, 'to' => test()->to->id, 'amount' => 1250, 'currency' => 'CAD', ...$overrides];
}

it('posts a transfer and returns the transaction with its entries', function () {
    postTransfer(transferBody())
        ->assertCreated()
        ->assertJsonPath('data.kind', 'transfer')
        ->assertJsonPath('data.idempotency_key', 'key-1')
        ->assertJsonCount(2, 'data.entries')
        ->assertJsonPath('data.entries.0.direction', 'debit')
        ->assertJsonPath('data.entries.0.amount', ['amount' => 1250, 'currency' => 'CAD']);

    expect($this->from->refresh()->balance)->toBe(8_750)
        ->and($this->to->refresh()->balance)->toBe(1_250);
});

it('posts the fee to the fees account in the same transaction', function () {
    $fees = Account::create(['name' => 'Fees CAD', 'code' => 'fees_cad', 'type' => AccountType::Revenue, 'currency' => Currency::CAD]);

    postTransfer(transferBody(['fee' => 50]))->assertCreated()->assertJsonCount(3, 'data.entries');

    expect($this->from->refresh()->balance)->toBe(10_000 - 1_250 - 50)
        ->and($this->to->refresh()->balance)->toBe(1_250)
        ->and($fees->refresh()->balance)->toBe(50);
});

it('requires an Idempotency-Key', function () {
    postTransfer(transferBody(), key: null)
        ->assertStatus(400)
        ->assertJsonPath('error.code', 'idempotency_key_required');

    expect(Transaction::count())->toBe(1); // only the funding transaction
});

it('returns the original transaction when a request is repeated', function () {
    $first = postTransfer(transferBody())->assertCreated();
    $second = postTransfer(transferBody())->assertCreated();

    // Same content; jsonb may reorder metadata keys, which JSON doesn't care about.
    expect($second->json())->toEqual($first->json())
        ->and($this->from->refresh()->balance)->toBe(8_750);
});

it('treats the same body with keys in a different order as a repeat', function () {
    $first = postTransfer(transferBody())->assertCreated();
    $reordered = array_reverse(transferBody(), preserve_keys: true);

    expect(postTransfer($reordered)->assertCreated()->json('data.id'))->toBe($first->json('data.id'));
});

it('rejects a reused key with a different body with 409', function () {
    postTransfer(transferBody())->assertCreated();

    postTransfer(transferBody(['amount' => 999]))
        ->assertConflict()
        ->assertJsonPath('error.code', 'idempotency_key_reused');

    expect($this->from->refresh()->balance)->toBe(8_750);
});

it('refuses a transfer the source cannot afford', function () {
    postTransfer(transferBody(['amount' => 10_001]))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'insufficient_funds');

    expect($this->from->refresh()->balance)->toBe(10_000);
});

it('does not burn the key on a failed transfer', function () {
    postTransfer(transferBody(['amount' => 10_001]))->assertUnprocessable();

    postTransfer(transferBody(['amount' => 10_000]))->assertCreated();
});

it('rejects a currency that does not match the accounts', function () {
    postTransfer(transferBody(['currency' => 'USD']))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'currency_mismatch');
});

it('rejects a fee when no fees account exists', function () {
    postTransfer(transferBody(['fee' => 50]))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'no_fees_account');
});

it('validates the body', function (array $overrides, string $field) {
    postTransfer(transferBody($overrides))
        ->assertUnprocessable()
        ->assertJsonPath('error.code', 'validation_failed')
        ->assertJsonStructure(['error' => ['details' => [$field]]]);
})->with([
    'zero amount' => [['amount' => 0], 'amount'],
    'fractional amount' => [['amount' => 12.5], 'amount'],
    'unknown account' => [['to' => 999_999], 'to'],
    'negative fee' => [['fee' => -1], 'fee'],
    'bad currency' => [['currency' => 'EUR'], 'currency'],
]);

it('rejects a transfer to the same account', function () {
    postTransfer(transferBody(['to' => $this->from->id]))
        ->assertUnprocessable()
        ->assertJsonStructure(['error' => ['details' => ['to']]]);
});
