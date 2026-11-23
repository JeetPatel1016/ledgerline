# Ledgerline

[![CI](https://github.com/JeetPatel1016/ledgerline/actions/workflows/ci.yml/badge.svg)](https://github.com/JeetPatel1016/ledgerline/actions/workflows/ci.yml)

A double-entry ledger and payments API: multi-currency accounts, idempotent transfers, holds and captures, bank reconciliation and an append-only audit trail, with a live-updating dashboard.

> **Status:** work in progress. Milestone 1 (skeleton) is in place; the ledger itself lands in M2. See [`docs/TASKS.md`](docs/TASKS.md) for progress and [`docs/SPEC.md`](docs/SPEC.md) for the full design.

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
- **Every money-moving request is idempotent** (API, M3).
