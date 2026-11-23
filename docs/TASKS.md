# Ledgerline: task checklist

Tick tasks as they're done. One milestone at a time; each ends with a green build and a commit.

## M1: Skeleton
- [ ] Laravel 11 app, PHP 8.3, `declare(strict_types=1)` convention
- [ ] Docker: nginx + php-fpm, worker (supervisord), Reverb, postgres 16, redis 7
- [ ] Inertia + React + TypeScript + Tailwind via Vite
- [ ] Pint, Larastan (PHPStan level 8), Pest set up
- [ ] GitHub Actions: composer install, Pint, PHPStan, Pest
- [ ] `.env.example`, README stub

## M2: Ledger core
- [ ] Migrations: accounts, transactions, entries, audit_logs (bigint amounts)
- [ ] Enums: AccountType, Direction, TransactionKind, TransactionStatus
- [ ] LedgerService::post() enforcing balanced entries per currency
- [ ] DB-level guard (deferred constraint or trigger) for balance and append-only entries
- [ ] Cached balance + version updated in the same DB transaction
- [ ] Reversal transactions referencing the original
- [ ] Nightly job verifying cached balance = sum of entries
- [ ] Pest tests for every rule

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

## Session log
<!-- YYYY-MM-DD: what was done; open items -->
