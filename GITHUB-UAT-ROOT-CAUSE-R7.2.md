# GitHub UAT Root-Cause Analysis — R7.2

Date: 2026-10-04  
Audited GitHub commit: `b9c4d796022482728349ef9f4b330f2f71649c1d`

## Evidence from uploaded GitHub logs

The uploaded runs fail before the application can execute most production UAT:

1. Fresh MySQL 8.4 schema import fails with:
   `ERROR 1054 (42S22) at line 808: Unknown column 'period_end' in 'bank_reconciliation_sessions'`.
2. The R2 → R7 migration chain reaches the verifier, but the verifier reports every enterprise table missing.
3. Because fresh schema creation fails, the HTTP, browser, scale and separate production-MySQL jobs are blocked upstream and cannot prove application behavior.

## Root cause 1 — canonical fresh schema was not canonical

`database/schema_enterprise_r7.sql` was assembled from final CREATE statements plus migration-era `ALTER TABLE` statements. `bank_reconciliation_sessions` was first created with the old R3 shape (`period`, `opening_balance`, `closing_balance`) and later altered as if `period_start` / `period_end` already existed. This is a source-design defect, not a CI defect.

### Permanent correction

The fresh R7 schema is now a true final-state schema:

- every R4–R7 column, index and foreign key is defined directly in its `CREATE TABLE`;
- all migration-era `ALTER TABLE` statements were removed from the fresh schema;
- the final reconciliation table directly contains `period_start`, `period_end`, `matched_amount`, `difference_amount`, close/reopen audit fields and FKs;
- static contract tests reject any future `ALTER TABLE` in the fresh schema, duplicate columns, duplicate table names, duplicate named indexes or duplicate constraint names.

Upgrade migrations remain separate and continue to use ALTER statements, which is their correct purpose.

## Root cause 2 — schema verifier incorrectly read INFORMATION_SCHEMA

`SchemaInspector::tables()` used `array_column(..., 'table_name')`. MySQL/PDO can expose INFORMATION_SCHEMA labels as `TABLE_NAME`; therefore a database containing tables could be interpreted as an empty array.

### Permanent correction

The inspector now uses:

`SELECT TABLE_NAME AS table_name ...` plus `PDO::FETCH_COLUMN`.

This removes dependence on associative-key casing and makes the upgrade verifier deterministic.

## Root cause 3 — frontend/backend accounting contract mismatch

The R4 Journal Multi-Line frontend allowed USD/SGD selection, but did not send `exchange_rate` and `foreign_amount`. The backend silently defaulted rate to `1`. A non-IDR journal could therefore be accepted with the wrong economic value.

### Permanent correction

- UI now exposes exchange rate and foreign amount.
- company base currency is included in runtime context.
- base-currency journals require rate exactly `1`.
- non-base journals require positive foreign amount and positive rate.
- backend verifies `round(foreign_amount × exchange_rate)` equals the base debit/credit amount (tolerance 1 minor unit).
- Browser E2E now creates a real USD journal through the UI, API, backend and MySQL and confirms it appears in the ledger.

## CI contract hardening

New/expanded gates:

- `tests/schema-contract.php` — canonical schema / migration contract.
- `tests/frontend-backend-contract.php` — UI action → API route → runtime → schema contract.
- R4 workflow tests include FX accounting validation.
- Full UAT runs the frontend/backend contract.
- separate MySQL workflow now runs both contracts, JS syntax checks, and is triggered by assets/tests changes.
- Browser UAT submits a real multi-currency journal instead of only checking that the modal opens.

## Local evidence

Current local result after repair:

- Schema/migration contract: 33/33 PASS
- Frontend/backend contract: 26/26 PASS
- Legacy regression: 62/62 PASS
- Enterprise domain: 35/35 PASS
- Enterprise advanced: 33/33 PASS
- R4 workflow: 21/21 PASS
- R5 operations: 22/22 PASS
- R6 usability: 6/6 PASS
- R7 controls: 20/20 PASS
- Total rule/workflow/contract assertions: **258 PASS, 0 FAIL**
- PHP lint: PASS
- JavaScript syntax: PASS
- Architecture gate: PASS
- Production fail-closed: PASS
- Demo HTTP route sweep: 33/33 HTTP 200, no PHP fatal/warning

## Still requires GitHub proof

The local runtime has no MySQL server / PDO MySQL driver. Therefore R7.2 is a UAT repair candidate, not Production Final. MySQL 8.4 fresh install, R2→R7 migration, production HTTP, browser round-trip and scale simulation must all pass in GitHub Actions before final sign-off.


## 2026-10-04 follow-up — commit 601be22a

GitHub Actions **did run** for commit `601be22a21c79d125f06a507646b2f2f74ad0c18`. The Full UAT and MySQL Production Simulation both executed. Core/static regression passed; MySQL-dependent gates exposed the next causal layer.

### Root cause 4 — fresh schema had one forward foreign-key dependency

The canonical schema created `invoices` before `parties`, while `invoices.party_id` had a strict foreign key to `parties(id)`. MySQL 8.4 correctly rejected the fresh import with `ERROR 1824: Failed to open the referenced table 'parties'`.

Permanent correction: `parties` is created before `invoices`; the FK remains enabled. `tests/schema-contract.php` now parses every fresh-schema FK and rejects any reference to a table that is created later. This fixes the source dependency graph rather than disabling foreign-key checks or removing the constraint.

### Migration preservation was measuring the wrong invariant

The R2 fixture contains 10 legacy Chart-of-Accounts rows. R4/R5 migrations intentionally add exactly five global system accounts required by new accounting workflows: `5401`, `5501`, `3102`, `1161`, `2201`. Therefore a raw `COUNT(*)` equality (10 before versus 15 after) falsely reported data corruption.

The gate is now **stronger**, not weaker: it snapshots every legacy company and legacy account row, compares those exact rows after migration, then independently requires the five system accounts with exact codes, names, types and flags, and finally verifies the final account count is `legacy + 5`. Unexpected mutation, deletion, or extra insertion still fails the job.

### Local proof after this repair

- Schema/migration contract: 37/37 PASS
- Frontend/backend contract: 26/26 PASS
- All existing legacy/domain/R4/R5/R6/R7 gates remain PASS
- PHP/JS syntax PASS
- GitHub workflow YAML parse PASS
- No UAT assertion removed, bypassed, or converted to continue-on-error
