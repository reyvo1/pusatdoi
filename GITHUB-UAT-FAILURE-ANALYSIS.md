# GitHub UAT Failure Analysis — R7.1

Source evidence: GitHub Actions logs uploaded 2026-10-04.

## Root causes found

1. **Fresh MySQL schema — P0**
   - Error: `ERROR 1061 (42000): Duplicate key name 'uq_payment_invoice'`.
   - Cause: `payment_allocations` already declared `uq_payment_invoice` in the base schema, then the R4 delta added the same unique index again.
   - Impact: MySQL Accounting, HTTP/Security, Browser E2E, Scale, and standalone Production MySQL all stopped during schema import.
   - Fix: keep the unique index exactly once in fresh schemas; remove the duplicate from the R4 delta and v7 migration.

2. **R2 → R7 migration — P0**
   - Error: `ERROR 1553 (HY000): Cannot drop index 'uq_budget': needed in a foreign key constraint`.
   - Cause: MySQL used the legacy composite `uq_budget` as the supporting index for the `company_id` foreign key. v6 attempted to drop it before creating another company-leading index.
   - Fix: create `idx_budget_company(company_id)` first, then drop/replace `uq_budget`. Fresh R7 schema now also declares this explicit supporting index.

3. **Core consolidation regression — deterministic-test defect**
   - Symptom: `Group report applies consolidation adjustments` failed.
   - Cause: the test created a consolidation run for `2026-09` but called `financialReportData(null)` without a period. On 2026-10-04 the report correctly defaulted to October, so September elimination lines were not expected in that report.
   - Fix: the test now explicitly requests `2026-09` before asserting elimination lines. The production consolidation logic was not bypassed or weakened.

4. **Standalone MySQL workflow — latent CI defect**
   - Cause: the `Run production MySQL test` step had additional PHP commands incorrectly indented under a scalar `run:` line. It had not yet surfaced because schema import failed first.
   - Fix: convert to a proper multi-line `run: |` block and run `mysql-production.php`, `r5-mysql-uat.php`, and `r7-mysql-uat.php` as separate commands.

## New prevention gate
`tests/schema-contract.php` validates:
- exactly one `uq_payment_invoice` in fresh R7 schema;
- v7 does not recreate the R3 allocation unique;
- v6 creates a supporting budget-company index before dropping the legacy unique;
- fresh R7 includes the explicit supporting budget-company index;
- standalone MySQL workflow runs PHP suites as separate commands;
- Full UAT references the R7 schema in every MySQL-dependent gate.

## Local result after repair
- Schema/migration contract: **6/6 PASS**
- Legacy regression: **62/62 PASS**
- Enterprise domain: **35/35 PASS**
- Enterprise advanced: **33/33 PASS**
- R4 workflows: **17/17 PASS**
- R5 operations: **22/22 PASS**
- R6 usability: **6/6 PASS**
- R7 controls: **20/20 PASS**
- Architecture: **PASS**
- Production fail-closed: **PASS**
- PHP lint: **PASS**
- JavaScript syntax: **PASS**

MySQL 8.4 runtime remains a mandatory GitHub gate; the local environment used for this repair does not provide a MySQL server.
