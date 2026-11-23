<?php

declare(strict_types=1);

namespace App\Domain\Ledger;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

final class LedgerService
{
    /**
     * Post a balanced transaction. Locks every affected account (ascending id),
     * writes the entries and updates each cached balance in one DB transaction.
     *
     * @param  list<EntryLine>  $lines
     * @param  array<string, mixed>  $metadata
     */
    public function post(
        TransactionKind $kind,
        string $idempotencyKey,
        array $lines,
        array $metadata = [],
        ?Transaction $reverses = null,
    ): Transaction {
        if (count($lines) < 2) {
            throw LedgerException::tooFewEntries();
        }
        foreach ($lines as $line) {
            if ($line->amount <= 0) {
                throw LedgerException::nonPositiveAmount();
            }
        }

        return DB::transaction(function () use ($kind, $idempotencyKey, $lines, $metadata, $reverses): Transaction {
            $accounts = $this->lockAccounts(array_map(fn (EntryLine $line): int => $line->accountId, $lines));
            $this->assertBalanced($lines, $accounts);

            $transaction = Transaction::create([
                'kind' => $kind,
                'status' => TransactionStatus::Posted,
                'idempotency_key' => $idempotencyKey,
                'reverses_id' => $reverses?->id,
                'metadata' => $metadata,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                $account = $accounts[$line->accountId];
                $transaction->entries()->create([
                    'account_id' => $account->id,
                    'direction' => $line->direction,
                    'amount' => $line->amount,
                    'currency' => $account->currency,
                ]);
                $account->balance += $account->delta($line->direction, $line->amount);
            }

            foreach ($accounts as $account) {
                if ($account->balance < 0 && ! $account->allow_negative) {
                    throw LedgerException::insufficientFunds($account);
                }
                $account->version++;
                $account->save();
            }

            $transaction->load('entries');
            AuditLog::create([
                'action' => 'transaction.posted',
                'subject_type' => $transaction->getMorphClass(),
                'subject_id' => $transaction->id,
                'after' => $transaction->toArray(),
            ]);

            return $transaction;
        });
    }

    /** Post a transaction that undoes $original by flipping every entry. */
    public function reverse(Transaction $original, string $idempotencyKey): Transaction
    {
        if ($original->kind === TransactionKind::Reversal || $original->status !== TransactionStatus::Posted) {
            throw LedgerException::notReversible($original);
        }
        if ($original->reversal()->exists()) {
            throw LedgerException::alreadyReversed($original);
        }

        $lines = array_values($original->entries
            ->map(fn (Entry $entry): EntryLine => new EntryLine($entry->account_id, $entry->direction->opposite(), $entry->amount))
            ->all());

        return $this->post(TransactionKind::Reversal, $idempotencyKey, $lines, reverses: $original);
    }

    /**
     * Accounts whose cached balance differs from the sum of their entries.
     *
     * @return list<array{account_id: int, cached: int, actual: int}>
     */
    public function balanceMismatches(): array
    {
        $totals = Entry::query()
            ->selectRaw('account_id, direction, sum(amount) as total')
            ->groupBy('account_id', 'direction')
            ->toBase()
            ->get()
            ->groupBy('account_id');

        $mismatches = [];
        foreach (Account::query()->orderBy('id')->get() as $account) {
            $actual = 0;
            foreach ($totals->get($account->id, []) as $row) {
                $actual += $account->delta(Direction::from($row->direction), (int) $row->total);
            }
            if ($actual !== $account->balance) {
                $mismatches[] = ['account_id' => $account->id, 'cached' => $account->balance, 'actual' => $actual];
            }
        }

        return $mismatches;
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, Account> keyed by account id, in ascending id order
     */
    private function lockAccounts(array $ids): array
    {
        $ids = array_values(array_unique($ids));
        $accounts = Account::query()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id')->all();

        foreach ($ids as $id) {
            if (! isset($accounts[$id])) {
                throw LedgerException::unknownAccount($id);
            }
        }

        return $accounts;
    }

    /**
     * @param  list<EntryLine>  $lines
     * @param  array<int, Account>  $accounts
     */
    private function assertBalanced(array $lines, array $accounts): void
    {
        $net = [];
        foreach ($lines as $line) {
            $currency = $accounts[$line->accountId]->currency->value;
            $net[$currency] = ($net[$currency] ?? 0) + ($line->direction === Direction::Debit ? $line->amount : -$line->amount);
        }

        foreach ($net as $currency => $sum) {
            if ($sum !== 0) {
                throw LedgerException::unbalanced(Currency::from($currency));
            }
        }
    }
}
