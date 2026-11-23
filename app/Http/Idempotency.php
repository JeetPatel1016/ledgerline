<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Ledger\Transaction;
use Illuminate\Http\Request;

/**
 * Idempotency-Key handling for money-moving endpoints. The key is stored as the
 * ledger transaction's idempotency_key (unique), and a hash of the request in its
 * metadata, so a repeat can be answered from the transaction it created.
 */
final class Idempotency
{
    public static function key(Request $request): string
    {
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '' || strlen($key) > 255) {
            throw new ApiError(400, 'idempotency_key_required', 'An Idempotency-Key header of 1 to 255 characters is required.');
        }

        return $key;
    }

    /** What makes two requests "the same": method, path and body. */
    public static function hash(Request $request): string
    {
        $body = $request->all();
        ksort($body);

        return hash('sha256', $request->method().' '.$request->path().' '.json_encode($body));
    }

    /**
     * The transaction an earlier request with this key created, or null if there is none.
     * A key reused with a different request is a conflict.
     */
    public static function replay(string $key, string $hash): ?Transaction
    {
        $transaction = Transaction::with('entries')->where('idempotency_key', $key)->first();
        if ($transaction !== null && ($transaction->metadata['request_hash'] ?? null) !== $hash) {
            throw new ApiError(409, 'idempotency_key_reused', 'This Idempotency-Key was already used with a different request.');
        }

        return $transaction;
    }
}
