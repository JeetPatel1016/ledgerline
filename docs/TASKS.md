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
- [ ] Sanctum token auth
- [ ] `POST /v1/accounts`, `GET /v1/accounts/{id}`, `GET /v1/accounts/{id}/entries`
- [ ] `POST /v1/transfers` with Idempotency-Key (repeat returns original, different body 409)
- [ ] Fees posted to the fees account
- [ ] `lockForUpdate()` in ascending account-id order
- [ ] Concurrency test: 20 parallel withdrawals, only 10 affordable, balance never negative
- [ ] Consistent error format

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
Status: M1 and M2 done, CI green on `main`. Start **M3: Transfers API**.

Starting points for M3:
- Install Sanctum with `php artisan install:api` (Laravel 13 ships without API routes). Sanctum is in the spec's stack; no need to ask.
- Transfers call `LedgerService::post()`; don't duplicate posting logic. The Idempotency-Key handling (same key + same body → original response, different body → 409) belongs at the API layer. The ledger already rejects a reused key with a unique violation, which is the backstop.
- Fees: add the fee lines to the same transaction as the transfer, crediting a fees revenue account, so the transfer and its fee post atomically.
- Map `LedgerException` and DB constraint violations to the single error shape `{ "error": { "code", "message", "details" } }`. LedgerException doesn't carry a code yet; add one when the API needs it.
- Open from M2: two reversals of the same transaction arriving at once both pass the `alreadyReversed` pre-check, and the loser fails on the unique `reverses_id` index with a raw `UniqueConstraintViolationException`. Map that to the "already reversed" error.
- Concurrency test (20 parallel withdrawals, 10 affordable): `RefreshDatabase` wraps each test in a transaction that never commits, so this test needs real commits and separate DB connections or processes, plus its own cleanup.

Other open items:
- CI warns that `actions/checkout@v4`, `setup-node@v4` and `cache@v4` target the deprecated Node 20. Bump them when convenient.
- Larastan doesn't analyse `tests/` or `config/` (Pest's `$this` and Laravel's stock config don't pass level 8).

## Session log
<!-- YYYY-MM-DD: what was done; open items -->
- 2026-11-23: M1 done. Laravel 13 (not 11: every 11.x release has open security advisories); standard Laravel layout at repo root, SPEC and TASKS in `docs/`. Docker stack (nginx, php-fpm, worker, reverb, postgres 16 + test DB, redis 7, node for Vite); Inertia + React + TS strict + Tailwind 4; Pint (strict_types), Larastan L8, Pest on Postgres; CI workflow written but not yet run on GitHub (no remote). Later: CI green on GitHub (fixed Pages→pages case bug); vendor/ and node_modules/ moved to Docker volumes, page load 6.2s→0.2s, Pest ~1s.
- 2026-11-23: M2 done. Ledger in `app/Domain/Ledger` (enums, models, `LedgerService::post/reverse/balanceMismatches`). DB guards: deferred constraint trigger (>=2 entries, balanced per currency), append-only triggers on entries and audit_logs, CHECK for non-negative balances, unique `reverses_id`. `ledger:verify-balances` scheduled 02:00. 31 Pest tests; deferred trigger also verified on a real commit. Decision: balances are signed by the account type's normal direction; entry currency always equals account currency.
