# Ledgerline: task checklist

Tick tasks as they're done. One milestone at a time; each ends with a green build and a commit.

## M1: Skeleton
- [x] Laravel 13 app, PHP 8.3, `declare(strict_types=1)` convention
- [x] Docker: nginx + php-fpm, worker (supervisord), Reverb, postgres 16, redis 7
- [x] Inertia + React + TypeScript + Tailwind via Vite
- [x] Pint, Larastan (PHPStan level 8), Pest set up
- [x] GitHub Actions: composer install, Pint, PHPStan, Pest
- [x] `.env.example`, README stub

## M2: Ledger core
- [x] Migrations: accounts, transactions, entries, audit_logs (bigint amounts)
- [x] Enums: AccountType, Direction, TransactionKind, TransactionStatus
- [x] LedgerService::post() enforcing balanced entries per currency
- [x] DB-level guard (deferred constraint or trigger) for balance and append-only entries
- [x] Cached balance + version updated in the same DB transaction
- [x] Reversal transactions referencing the original
- [x] Nightly job verifying cached balance = sum of entries
- [x] Pest tests for every rule

## M3: Transfers API
- [x] Sanctum token auth
- [x] `POST /v1/accounts`, `GET /v1/accounts/{id}`, `GET /v1/accounts/{id}/entries`
- [x] `POST /v1/transfers` with Idempotency-Key (repeat returns original, different body 409)
- [x] Fees posted to the fees account
- [x] `lockForUpdate()` in ascending account-id order
- [x] Concurrency test: 20 parallel withdrawals, only 10 affordable, balance never negative
- [x] Consistent error format

## M4: Holds and FX
- [ ] Holds: authorize, capture (full/partial), void
- [ ] Available balance = balance minus active holds
- [ ] Hold expiry scheduled job
- [ ] FX rates table + `POST /v1/fx/convert` (rate saved on the transaction)
- [ ] Rounding rules documented and tested

## M5: Reconciliation
- [ ] CSV upload endpoint + StatementImport/StatementLine tables
- [ ] Queued matching job (amount + date window + description similarity)
- [ ] Matched / unmatched / ignored view, manual match
- [ ] Tests with sample CSVs

## M6: Dashboard + real-time
- [ ] Accounts list with balances and available balances
- [ ] Transaction feed updating live over Reverb
- [ ] Transfer detail with its entries
- [ ] Reconciliation screen
- [ ] Audit log view
- [ ] Empty, loading and error states

## M7: E2E, docs, deploy
- [ ] Playwright: make a transfer, see live update, reverse it, import a statement
- [ ] Playwright in CI
- [ ] OpenAPI docs (Scribe or L5-Swagger)
- [ ] README: pitch, GIF, diagram, accounting rules, design decisions
- [ ] Deploy (VPS or EC2) with seeded demo org; live link + CI badge

## Stretch
- [ ] GraphQL read API (Lighthouse)
- [ ] Outbound webhooks via Hookline
- [ ] OAuth/OIDC login (Socialite) + roles
- [ ] Monthly PDF statements
- [ ] k6 load test results in README

## Next up
Status: M1, M2 and M3 done locally (Pint, PHPStan, 60 Pest tests green). M3 is on branch `claude/karpathy-skills-installed-da4dfe`, not yet pushed, so CI hasn't run it. Start **M4: Holds and FX**.

Starting points for M4:
- Holds and FX endpoints move money, so they need an `Idempotency-Key`. Reuse `App\Http\Idempotency` (`key()`, `hash()`, `replay()`) the way `TransferController` does, including the `UniqueConstraintViolationException` → replay fallback for racing duplicates.
- `GET /api/v1/accounts/{id}` doesn't return an available balance yet. Add `available_balance` to `AccountResource` once holds exist.
- New domain errors: add a `LedgerException` named constructor with an `errorCode`. `ApiError::from()` maps it to a 422.
- Concurrency tests live in `tests/Concurrency` (no `RefreshDatabase`; `migrate:fresh` before and after). `withdraw.php` shows how to run one posting per process.

Other open items:
- Reversal race (from M2): two concurrent reversals both pass the `alreadyReversed` pre-check, and the loser hits the unique `reverses_id` index. The API currently returns a generic `409 conflict` for it. Map it to `already_reversed` when `POST /v1/transactions/{id}/reverse` is built.
- Idempotency keys are global, not per user, and any token can read or move any account (no account ownership in the data model yet). Revisit with roles (stretch).
- `transactions.created_by` and `audit_logs.actor_id` aren't filled in from the API user yet.
- Every fee transfer locks the shared fees account, which serializes fee-paying transfers per currency. Fine at demo scale.
- CI warns that `actions/checkout@v4`, `setup-node@v4` and `cache@v4` target the deprecated Node 20. Bump them when convenient.
- Larastan doesn't analyse `tests/` or `config/` (Pest's `$this` and Laravel's stock config don't pass level 8).

## Session log
<!-- YYYY-MM-DD: what was done; open items -->
- 2026-11-23: M1 done. Laravel 13 (not 11: every 11.x release has open security advisories); standard Laravel layout at repo root, SPEC and TASKS in `docs/`. Docker stack (nginx, php-fpm, worker, reverb, postgres 16 + test DB, redis 7, node for Vite); Inertia + React + TS strict + Tailwind 4; Pint (strict_types), Larastan L8, Pest on Postgres; CI workflow written but not yet run on GitHub (no remote). Later: CI green on GitHub (fixed Pages→pages case bug); vendor/ and node_modules/ moved to Docker volumes, page load 6.2s→0.2s, Pest ~1s.
- 2026-11-23: M2 done. Ledger in `app/Domain/Ledger` (enums, models, `LedgerService::post/reverse/balanceMismatches`). DB guards: deferred constraint trigger (>=2 entries, balanced per currency), append-only triggers on entries and audit_logs, CHECK for non-negative balances, unique `reverses_id`. `ledger:verify-balances` scheduled 02:00. 31 Pest tests; deferred trigger also verified on a real commit. Decision: balances are signed by the account type's normal direction; entry currency always equals account currency.
- 2026-11-23: M3 done. Sanctum (`install:api`, routes under `/api/v1`, `php artisan api:token <email>`); accounts endpoints with cursor-paginated entries; `TransferService` (`app/Domain/Payments`) posts amount + fee in one transaction, with the fee credited to the `fees_cad`/`fees_usd` revenue account (new `accounts.code`, seeded). Idempotency stores the key and a request hash on the ledger transaction (repeat → original, different body → 409, a failed request doesn't use up its key). `ApiError` gives one error shape; `LedgerException` now has `errorCode`. Lock-order test, plus a 20-process concurrency test (10 of 20 succeed; all 20 go through when `lockForUpdate` is removed). 60 tests.
