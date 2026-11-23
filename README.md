# Ledgerline

[![CI](https://github.com/JeetPatel1016/ledgerline/actions/workflows/ci.yml/badge.svg)](https://github.com/JeetPatel1016/ledgerline/actions/workflows/ci.yml)

A double-entry ledger and payments API: multi-currency accounts, idempotent transfers, holds and captures, bank reconciliation and an append-only audit trail, with a live-updating dashboard.

> **Status:** work in progress. The skeleton (M1), the ledger core (M2) and the transfers API (M3) are in place. See [`docs/TASKS.md`](docs/TASKS.md) for progress and [`docs/SPEC.md`](docs/SPEC.md) for the full design.

## Stack

PHP 8.3 · Laravel 13 · PostgreSQL 16 · Redis 7 · Nginx + PHP-FPM · React + TypeScript via Inertia · Tailwind · Laravel Reverb · Pest · PHPStan (Larastan, level 8) · Pint

## Running locally

Requires Docker. PHP and Node run inside containers, so nothing else needs to be installed. `vendor/` and `node_modules/` live in Docker volumes (reading them through the Windows/macOS file share was ~30x slower), so always run `composer` and `npm` inside the containers as shown.

```bash
cp .env.example .env
docker compose up -d                                   # nginx, php-fpm, worker, reverb, postgres, redis
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose run --rm node npm install
docker compose run --rm node npm run build            # or: docker compose up node  (Vite dev server)
```

The app is served at <http://localhost:8080>.

## Quality checks

```bash
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pest
```

## Accounting rules (in plain words)

- **Money is whole cents.** Amounts are integers in minor units (`1250` = $12.50), stored as `bigint`. No floats, ever.
- **Every transaction balances.** A transaction is two or more entries; for each currency, the debits add up to exactly the credits. The service checks this before writing, and a Postgres constraint trigger checks it again at commit, so even a bug or a hand-written SQL insert can't leave the books unbalanced.
- **Debits and credits mean what accountants mean.** Asset and expense accounts grow with debits; liability, equity and revenue accounts grow with credits. A customer's wallet is a liability (we owe them the money), so a deposit *credits* it.
- **Entries are never edited or deleted.** The database rejects `UPDATE`, `DELETE` and `TRUNCATE` on entries and on the audit log. A mistake is fixed by posting a *reversal*: a new transaction with every entry flipped, linked to the original. A transaction can be reversed once, and a reversal can't be reversed.
- **Balances can't go negative** unless the account is explicitly allowed to (enforced in the service and by a `CHECK` constraint).
- **Cached balances are checked nightly.** Each account keeps a cached `balance` and `version`, updated in the same DB transaction as its entries, with the rows locked in ascending id order. `php artisan ledger:verify-balances` (scheduled at 02:00) recomputes every balance from its entries and fails loudly on any drift.
- **Every money-moving request is idempotent.** `POST /api/v1/transfers` needs an `Idempotency-Key` header. Sending the same key and body again returns the original transaction instead of moving money twice. Sending the same key with a different body is rejected with `409`. The key is the ledger transaction's unique `idempotency_key`, so even two identical requests racing each other can only post once.
- **Concurrent withdrawals can't overdraw.** Each posting locks its accounts (`SELECT … FOR UPDATE`, ascending id, so two transfers can't deadlock) before reading balances. A test fires 20 withdrawals from separate processes at a wallet that can afford 10. Exactly 10 succeed and the balance ends at zero. Without the lock, all 20 succeed.

## API

All routes are under `/api/v1` and need a Sanctum token (`Authorization: Bearer …`). To issue a token for a seeded user:

```bash
docker compose exec app php artisan api:token test@example.com
```

| Method | Path | Notes |
|--------|------|-------|
| POST | `/api/v1/accounts` | `{ name, type, currency, allow_negative? }` |
| GET | `/api/v1/accounts/{id}` | Account with its cached balance |
| GET | `/api/v1/accounts/{id}/entries` | Newest first, cursor-paginated; `?from=YYYY-MM-DD&to=YYYY-MM-DD` |
| POST | `/api/v1/transfers` | `Idempotency-Key` header; `{ from, to, amount, currency, fee? }` |

A transfer debits `from` by `amount + fee`, credits `to` by `amount` and credits the currency's fees revenue account by `fee`. All three entries are one transaction, so the transfer and its fee post or fail together. Money travels as `{ "amount": 1250, "currency": "CAD" }`. Every error has the same shape:

```json
{ "error": { "code": "insufficient_funds", "message": "Account 7 cannot go negative.", "details": {} } }
```
