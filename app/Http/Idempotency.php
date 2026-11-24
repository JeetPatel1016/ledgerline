<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Ledger\Transaction;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

/**
 * Idempotency-Key handling for money-moving endpoints. The key is stored as the
 * ledger transaction's idempotency_key (unique), and a hash of the request in its
 * metadata, so a repeat can be answered from the transaction it created.
 */
final class Idempotency
{
    /**
     * Post at most once per Idempotency-Key. A repeat of the same request returns the
     * transaction the first one created; the same key with a different request is a 409.
     *
     * @param  Closure(string, array<string, mixed>): Transaction  $post  receives the key and metadata to store
     */
    public static function once(Request $request, Closure $post): Transaction
    {
        $key = self::key($request);
        $hash = self::hash($request);

        $transaction = self::replay($key, $hash);
        if ($transaction !== null) {
            return $transaction;
        }

        try {
            return $post($key, ['request_hash' => $hash]);
        } catch (UniqueConstraintViolationException $e) {
            // A concurrent request with the same key committed first.
            return self::replay($key, $hash) ?? throw $e;
        }
    }

    private static function key(Request $request): string
    {
        $key = (string) $request->header('Idempotency-Key');
        if ($key === '' || strlen($key) > 255) {
            throw new ApiError(400, 'idempotency_key_required', 'An Idempotency-Key header of 1 to 255 characters is required.');
        }

        return $key;
    }

    /** What makes two requests "the same": method, path and body. */
    private static function hash(Request $request): string
    {
        $body = $request->all();
        ksort($body);

        return hash('sha256', $request->method().' '.$request->path().' '.json_encode($body));
    }

    private static function replay(string $key, string $hash): ?Transaction
    {
        $transaction = Transaction::with('entries')->where('idempotency_key', $key)->first();
        if ($transaction !== null && ($transaction->metadata['request_hash'] ?? null) !== $hash) {
            throw new ApiError(409, 'idempotency_key_reused', 'This Idempotency-Key was already used with a different request.');
        }

        return $transaction;
    }
}
