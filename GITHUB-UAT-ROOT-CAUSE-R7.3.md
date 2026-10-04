# GitHub UAT Root-Cause Analysis — R7.3 follow-up

Date: 2026-10-04  
GitHub run: `37206221993`  
Observed commit: `80beb34cbad701553a87b22a876d7a6e3215f301`

## Evidence and causal grouping

The failing Full UAT was not five independent feature failures.

- Core / Static Regression passed.
- MySQL / Accounting, HTTP / Security / Reports, Browser E2E, and Multi-Entity / High-Volume all stopped at the same fresh `schema + seed` boundary.
- R2 -> R7 migration completed all migration and preservation checks, then failed during production runtime regression.
- Final verdict correctly failed because dependent gates were red.

## Root cause A — fresh seed row arity mismatch

`database/seed_enterprise.sql` declares eight columns for `income_categories`:

`id, company_id, code, name, revenue_account_id, tax_profile_id, sort_order, is_active`

but every seeded row supplied only seven values. MySQL therefore rejects the seed import before HTTP, browser, and scale jobs can begin application testing.

### Permanent correction

- Every `income_categories` seed row now supplies `is_active = 1` explicitly.
- `tests/schema-contract.php` now generically parses all `INSERT ... VALUES` statements in the enterprise seed and fails when any row value count differs from the declared column count.

This is stronger than a one-off test for `income_categories`; future seed arity regressions are also blocked locally and in CI.

## Root cause B — production company-metrics placeholder mismatch

`r4SqlCompanyMetrics()` uses the date predicate once for revenue and once for expense, resulting in four positional SQL placeholders, but supplied only two PDO parameters.

GitHub exposed this as:

`SQLSTATE[HY093]: Invalid parameter number`

### Permanent correction

The function now binds both start/end pairs. A deep-audit source contract protects the four-parameter binding, while the existing MySQL production regression remains the runtime proof.

## Root cause C — production reversal return contract incomplete

The production branch of `reverseJournal()` persisted the correct `reversal_of` relationship in MySQL but returned only `id`, `no`, and `status`. The MySQL regression therefore could not verify the audit-chain relation from the returned production object and reported an undefined `reversal_of` key.

### Permanent correction

The production return value now includes `reversal_of => original journal id`, matching the demo/runtime contract and the database relation. The deep-audit suite now guards this contract as well.

## Exact-SHA CI hardening

The standalone MySQL workflow previously used a `paths:` filter. A documentation-only final commit could therefore have Full UAT evidence on one SHA and standalone MySQL evidence on an older SHA.

The workflow now runs on every push to `main`/`master` (plus manual dispatch and pull requests), so release evidence can be tied to the exact final SHA.

## Local proof after repair

- Schema / migration contract: 39/39 PASS
- Frontend / backend contract: 26/26 PASS
- Deep audit security / integrity: 18/18 PASS
- Legacy regression: 62/62 PASS
- Enterprise domain: 35/35 PASS
- Enterprise advanced: 33/33 PASS
- R4 workflow: 21/21 PASS
- R5 operations: 22/22 PASS
- R6 usability: 6/6 PASS
- R7 group controls: 20/20 PASS
- Architecture gate: PASS
- Production fail-closed: PASS
- PHP lint / JavaScript syntax: PASS

Rule/workflow/contract assertions: **282 PASS, 0 FAIL**.

## Remaining release gate

Production Final is still blocked until GitHub MySQL 8.4, migration regression, HTTP/security/reports, browser E2E, scale simulation, and final verdict all pass on the same new commit SHA. No UAT assertion was removed or relaxed by this repair.
