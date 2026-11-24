# Ledgerline: Build Spec

Drop this file into the new repo (as `SPEC.md`, or paste it into the repo's `CLAUDE.md`) and build milestone by milestone. Each milestone ends with a working, committed, tested state.

## One-line pitch
A double-entry ledger and payments API: multi-currency accounts, idempotent transfers, holds and captures, bank reconciliation and an append-only audit trail, with a live-updating dashboard.

## Stack
- **Backend:** PHP 8.3, Laravel 13, PostgreSQL 16, Redis 7
- **Web server:** Nginx + PHP-FPM (LEMP)
- **Frontend:** React + TypeScript via Inertia.js, Tailwind
- **Real-time:** Laravel Reverb (WebSockets) + Laravel Echo
- **Queues:** Laravel queues on Redis (Horizon for monitoring)
- **API docs:** OpenAPI via Scribe or L5-Swagger; GraphQL read API with Lighthouse (stretch)
- **Quality:** Pest (unit + feature), PHPStan (level 8 via Larastan), Laravel Pint, Playwright (E2E)
- **Infra:** Docker (nginx, php-fpm, worker, reverb, postgres, redis), docker-compose, GitHub Actions; deploy to a small VPS or AWS EC2

> **Changed 2026-11-23:** the spec originally said Laravel 11. Every Laravel 11 release now carries open security advisories (11.x is past end of security support) and Composer refuses to install it, so the project uses Laravel 13, which requires PHP 8.3 and keeps the rest of the stack unchanged.

## Core accounting rules
- Amounts are **integers in minor units** (cents). Never floats.
- Every transaction has **at least two entries** and the sum of debits equals the sum of credits **per currency**. Enforced in the service layer and with a database check (deferred constraint or trigger).
- Entries are **append-only**. Corrections are reversing transactions that reference the original.
- An account's balance = sum of its entries. Keep a cached `balance` + `version` on the account, updated inside the same DB transaction, and a nightly job that verifies cache = sum.
- Accounts can't go negative unless their type allows it (e.g. `liability` or `revenue` normal balances).

## Data model
- **Account:** id, name, code (nullable, unique; names system accounts such as `fees_cad`), type (asset | liability | equity | revenue | expense), currency (CAD | USD), allowNegative, balance (cached), version, createdAt
- **Transaction:** id, kind (transfer | hold | capture | void | fee | fx | reversal), status (posted | pending | voided), idempotencyKey (unique), reversesId (nullable), metadata (JSONB), postedAt, createdBy
- **Entry:** id, transactionId, accountId, direction (debit | credit), amount (bigint), currency, createdAt
- **Hold:** id, accountId, amount, currency, status (active | captured | voided | expired), expiresAt, transactionId
- **FxRate:** id, base, quote, rate (decimal 18,8), effectiveAt
- **StatementImport:** id, filename, uploadedAt, rowCount, matchedCount
- **StatementLine:** id, importId, date, description, amount, currency, matchedEntryId (nullable), status (matched | unmatched | ignored)
- **AuditLog:** id, actorId, action, subjectType, subjectId, before (JSONB), after (JSONB), createdAt

## API (v1, auth: Laravel Sanctum tokens)
| Method | Path | Notes |
|--------|------|-------|
| POST | /v1/accounts | Create account |
| GET | /v1/accounts/{id} | Balance, available balance (balance minus active holds) |
| GET | /v1/accounts/{id}/entries | Cursor pagination, date filters |
| POST | /v1/transfers | Requires `Idempotency-Key`; body: from, to, amount, currency, optional fee; returns transaction with entries |
| POST | /v1/holds | Authorize funds; reduces available balance |
| POST | /v1/holds/{id}/capture | Full or partial capture, posts the transfer |
| POST | /v1/holds/{id}/void | Releases the hold |
| POST | /v1/transactions/{id}/reverse | Posts a reversing transaction |
| POST | /v1/fx/convert | CAD to USD (or back) at the current stored rate; rate saved on the transaction |
| POST | /v1/reconciliation/imports | Upload bank CSV; queued matching job |
| GET | /v1/reconciliation/imports/{id} | Matched vs unmatched lines |

Same idempotency rules as Hookline: same key + same body returns the original result; same key + different body returns 409.

> **Decided 2026-11-23 (M3):** routes are served under Laravel's `/api` prefix (`POST /api/v1/transfers`), which keeps them apart from the Inertia web routes. A transfer debits `from` by amount + fee and credits `to` by amount and the currency's fees revenue account by fee, all in one transaction. Fees accounts are found by a new nullable unique `Account.code` (`fees_cad`, `fees_usd`), created by the seeder. The idempotency key is stored as the transaction's `idempotencyKey`, with a hash of the request in its metadata. No separate table is needed, and a failed request doesn't use up its key.

## Concurrency
- Wrap every money movement in a DB transaction and lock the affected account rows with `lockForUpdate()`, always in a consistent order (by account id) to avoid deadlocks.
- A Pest test fires 20 parallel withdrawals at an account that can only afford 10 and asserts exactly 10 succeed and the balance never goes negative.

## Milestones
1. **Skeleton (day 1):** Laravel 13 app, Docker (nginx + php-fpm + postgres + redis), Inertia + React + TS, Pint + PHPStan + Pest in GitHub Actions.
2. **Ledger core (day 2):** accounts, transactions, entries, balance invariant, reversal; thorough Pest tests for every rule above.
3. **Transfers API (day 3):** idempotent transfers, fees, row locking, the parallel-withdrawal test, consistent error format.
4. **Holds and FX (day 4):** authorize, capture (full and partial), void, hold expiry job; FX rates table and conversion.
5. **Reconciliation (day 5):** CSV upload, queued matching (amount + date window + description similarity), matched/unmatched view, manual match.
6. **Dashboard + real-time (day 6):** accounts and balances, transaction feed live over Reverb, transfer detail with entries, reconciliation screen, audit log view.
7. **E2E, docs, deploy (day 7):** Playwright (make a transfer, see live update, reverse it, import a statement), OpenAPI docs, README with GIF + diagram, deploy with a seeded demo org.

## Stretch
- GraphQL read API with Lighthouse (accounts, entries, statements).
- Outbound webhooks (`transfer.posted`, `transfer.failed`) delivered through Hookline.
- OAuth 2.0 / OIDC login (Socialite) + roles (admin, viewer).
- Monthly PDF statements.
- k6 load test of `POST /v1/transfers` with results in the README.

## Definition of done
- `docker compose up` runs nginx, php-fpm, worker, Reverb, Postgres and Redis; the README demo works end to end.
- CI green: Pint, PHPStan, Pest (including the concurrency test) and Playwright.
- README: pitch, GIF, architecture diagram, accounting rules explained in plain words, run steps, API overview, design decisions (why integer minor units, why append-only, how locking works), what's next.
- Live demo link and seeded demo org.
