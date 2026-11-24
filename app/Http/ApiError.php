<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Ledger\LedgerException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** The one API error shape: { "error": { "code", "message", "details" } }. */
final class ApiError extends RuntimeException
{
    /** @param  array<string, mixed>  $details */
    public function __construct(
        public readonly int $status,
        public readonly string $errorCode,
        string $message,
        public readonly array $details = [],
    ) {
        parent::__construct($message);
    }

    public static function from(Throwable $e): self
    {
        return match (true) {
            $e instanceof self => $e,
            $e instanceof ValidationException => new self(422, 'validation_failed', $e->getMessage(), $e->errors()),
            $e instanceof LedgerException => new self(422, $e->errorCode, $e->getMessage()),
            $e instanceof AuthenticationException => new self(401, 'unauthenticated', 'Authentication required.'),
            $e instanceof UniqueConstraintViolationException => new self(409, 'conflict', 'The request conflicts with existing data.'),
            $e instanceof HttpExceptionInterface => match ($e->getStatusCode()) {
                403 => new self(403, 'forbidden', 'Forbidden.'),
                404 => new self(404, 'not_found', 'Not found.'),
                405 => new self(405, 'method_not_allowed', 'Method not allowed.'),
                default => new self($e->getStatusCode(), 'http_error', $e->getMessage()),
            },
            default => new self(500, 'server_error', config('app.debug') ? $e->getMessage() : 'Server error.'),
        };
    }

    public function toResponse(): JsonResponse
    {
        return response()->json(['error' => [
            'code' => $this->errorCode,
            'message' => $this->getMessage(),
            'details' => (object) $this->details,
        ]], $this->status);
    }
}
