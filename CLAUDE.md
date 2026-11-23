# Ledgerline: instructions for coding sessions

Ledgerline is a portfolio project: a double-entry ledger and payments API (Laravel 13 on Nginx + PHP-FPM, PostgreSQL, Redis, React via Inertia). The full spec is in `docs/SPEC.md`; the working checklist is in `docs/TASKS.md`. Read both at the start of every session.

## Goal
A public GitHub repo that shows fintech-grade correctness: balanced double-entry, idempotent money movement, safe concurrency, reconciliation and an audit trail, with green CI, a live demo and a README that explains the accounting rules in plain words. Correctness over feature count.

## How to work in this repo
- Work **one milestone at a time** from `docs/TASKS.md`. Don't start the next milestone until the current one builds, its tests pass and it's committed.
- At the end of each task, tick it in `docs/TASKS.md` and add a one-line note under "Session log".
- `docs/SPEC.md` is the source of truth. If a decision changes it, update it in the same change and explain why in the commit message.
- Ask before adding a dependency that isn't in the spec's stack.

## Keep it simple (Karpathy guidelines)
- **Think first.** State assumptions; if something is ambiguous, ask instead of guessing.
- **Simplest thing that works.** No speculative abstractions, options or config. If it could be half the code, rewrite it.
- **Surgical changes.** Touch only what the task needs; don't reformat or "improve" neighbouring code.
- **Goal-driven.** Define the check that proves a task is done (a test, a command) before writing the code.

## Non-negotiable accounting rules
- Amounts are integers in minor units (cents), stored as `bigint`. Never floats, never decimals for amounts.
- Every transaction's debits equal its credits per currency. Enforce in the service and in the database.
- Entries are append-only. No updates or deletes on `entries`; corrections are reversing transactions.
- All money movement happens inside a DB transaction that locks affected accounts with `lockForUpdate()` in ascending account-id order.
- Every money-moving endpoint requires an `Idempotency-Key`.
- Any change to these rules needs a test proving the new behaviour.

## Layout
Standard Laravel layout at the repo root. `docs/` holds `SPEC.md` and `TASKS.md`.
```
app/Domain/Ledger      Accounts, Transactions, Entries, LedgerService (posting rules)
app/Domain/Payments    Holds, Transfers, Fees, FX
app/Domain/Reconciliation  CSV import, matching job
app/Http/Controllers/Api/V1
app/Http/Requests      form request validation
resources/js           React + TypeScript (Inertia pages)
docker/                nginx.conf, php-fpm Dockerfile, supervisord for workers
tests/Unit, tests/Feature, e2e/ (Playwright)
```

## Commands (keep these working; update if they change)
Run from the repo root. PHP and Node run in containers; nothing but Docker is needed on the host.
```
cp .env.example .env
docker compose up -d                               # nginx, php-fpm, worker, reverb, postgres, redis
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
docker compose run --rm node npm install
docker compose up node                             # Vite dev server (or: docker compose run --rm node npm run build)
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/phpstan analyse
docker compose exec app ./vendor/bin/pest
npx playwright test
```

## Code conventions
- PHP 8.3 features (readonly classes, enums for statuses and directions); `declare(strict_types=1);` in every PHP file.
- Business rules live in domain services, not controllers or models. Controllers validate (FormRequest), call a service, return a resource.
- One API error shape: `{ "error": { "code", "message", "details" } }`.
- Money values travel as `{ "amount": 1250, "currency": "CAD" }` in the API; format only in the UI.
- TypeScript strict on the frontend; no `any` without a comment.
- Never log tokens or full card-like data.

## Testing rules
- Pest unit tests for every posting rule (balanced, append-only, reversal, fees, FX rounding).
- Feature tests for each API endpoint including idempotency (repeat and conflict).
- The parallel-withdrawal concurrency test must stay green.
- Playwright E2E for the dashboard flows listed in the spec.
- A change isn't done until Pint, PHPStan and Pest pass.

## Git
- Small commits with conventional messages: `feat(ledger): enforce balanced entries per currency`.
- Commit per task; no giant initial commit.
- Never commit `.env`; keep `.env.example` current.

## README (write as you go, finish in the last milestone)
Pitch, demo GIF, architecture diagram, the accounting rules in plain words, run steps, API overview, design decisions (integer minor units, append-only entries, locking strategy, idempotency), what's next, CI badge, live link.
