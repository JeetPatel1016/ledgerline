# Ledgerline

[![CI](https://github.com/OWNER/ledgerline/actions/workflows/ci.yml/badge.svg)](https://github.com/OWNER/ledgerline/actions/workflows/ci.yml)

A double-entry ledger and payments API: multi-currency accounts, idempotent transfers, holds and captures, bank reconciliation and an append-only audit trail, with a live-updating dashboard.

> **Status:** work in progress. Milestone 1 (skeleton) is in place; the ledger itself lands in M2. See [`docs/TASKS.md`](docs/TASKS.md) for progress and [`docs/SPEC.md`](docs/SPEC.md) for the full design.

## Stack

PHP 8.3 · Laravel 13 · PostgreSQL 16 · Redis 7 · Nginx + PHP-FPM · React + TypeScript via Inertia · Tailwind · Laravel Reverb · Pest · PHPStan (Larastan, level 8) · Pint

## Running locally

Requires Docker. PHP and Node run inside containers, so nothing else needs to be installed.

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

_Written up in full as the ledger lands (M2–M4)._ The short version: money is stored as whole cents, every transaction's debits equal its credits, entries are never edited or deleted, and every money-moving request is idempotent.
