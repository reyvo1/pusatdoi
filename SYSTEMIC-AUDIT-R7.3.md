# NEXA Group Finance R7.3 — Systemic Full-Source Audit

Date: 2026-10-04
Scope: complete R7.3 GitHub-UAT root-fix source tree

## Root cause of repeated red GitHub UAT

The repeated failures were not caused by repository size. The primary systemic problem was an assurance gap:

1. `tests/run-all-local.sh` ran deterministic/static/demo-domain gates only.
2. Real MySQL 8.4 execution, migrated-database regression, HTTP runtime, browser E2E and scale were deferred to GitHub Actions.
3. The previous terminal message ended with `LOCAL GATES PASS`, which could be mistaken for production readiness even though production SQL/PDO had not executed locally.
4. This allowed production-only defects (SQL seed arity and native-PDO binding/return-contract defects) to survive local testing and appear only in GitHub.
5. Prior debugging reacted to first visible GitHub failures instead of enforcing a full-source systemic preflight first.

## Full-source inventory actually scanned

- Total files: 211
- PHP: 148
- JavaScript/MJS: 6
- SQL: 18
- GitHub workflow YAML: 2
- Shell scripts: 5

## Systemic checks performed

- PHP syntax across all PHP files: PASS
- JS/MJS syntax across all JS/MJS files: PASS
- Bash syntax across all shell scripts: PASS
- Source manifest: 210 hashed files, 0 mismatch, 0 missing: PASS
- GitHub full-UAT trigger on every main/master push: PASS
- GitHub standalone MySQL trigger on every main/master push: PASS
- No workflow `paths:` filter: PASS
- No workflow `continue-on-error: true`: PASS
- No literal TAB in workflows: PASS
- Workflow test/bin references exist: PASS
- Browser-authenticated POST action inventory: 66 routes discovered
- CSRF on all 66 POST actions: PASS
- Enterprise seed literal INSERT arity: PASS
- Native PDO emulation disabled contract: PASS
- Company metrics duplicated date-range binding contract: PASS
- Production reversal `reversal_of` return contract: PASS

## Proven bugs already corrected before this pass

- `database/seed_enterprise.sql`: income-category INSERT column/value mismatch.
- `lib/r4_runtime.php`: company metric SQL repeated a date range twice but previously bound only one pair, producing PDO HY093 with native prepares.
- production reversal runtime: returned row previously omitted `reversal_of`, breaking audit-chain regression.
- standalone MySQL workflow: previous `paths:` filtering could leave final SHA without MySQL evidence.

## QA architecture correction in this package

Added `tests/systemic-source-audit.php`, executed by both local deterministic gates and GitHub workflows. It verifies manifest integrity, workflow trigger contract, workflow bypass patterns, file references, POST/CSRF coverage, seed arity, native-PDO contracts and known production audit contracts.

Changed local final verdict from a generic `LOCAL GATES PASS` to:

`LOCAL DETERMINISTIC GATES PASS — PRODUCTION NOT YET PROVEN; real MySQL/browser gates must pass on the exact same GitHub SHA.`

This intentionally prevents a local deterministic run from being interpreted as production approval.

## What is still not proven locally

The current execution environment has PDO but not `pdo_mysql`/a MySQL server. Therefore the following cannot honestly be declared PASS by local execution here:

- MySQL 8.4 fresh schema + seed execution
- production MySQL accounting tests
- R2 → R7 migration regression on MySQL
- HTTP application runtime backed by MySQL
- Browser/Playwright E2E backed by MySQL
- high-volume MySQL simulation

These remain mandatory GitHub exact-SHA gates. No assertion should be removed or weakened if one fails.
