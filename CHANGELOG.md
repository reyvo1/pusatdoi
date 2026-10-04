# CHANGELOG

## Enterprise R7.1 UAT Repair Candidate — 2026-10-04
- Fixed MySQL 8.4 fresh-schema failure: removed duplicate `uq_payment_invoice` creation from R4 delta/fresh schemas.
- Fixed R2→R7 migration blocker: added dedicated `idx_budget_company` before dropping legacy `uq_budget`, so the company foreign key remains supported.
- Fixed the same duplicate index in migration v7 so the upgrade chain can proceed past R3.
- Fixed R4 consolidation regression test to explicitly request the same accounting period as its fixture (`2026-09`); this removes calendar-date nondeterminism without weakening the report assertion.
- Fixed malformed `mysql-production.yml` command block so MySQL tests execute as separate commands.
- Added `tests/schema-contract.php` and wired it into Full UAT core regression to prevent recurrence of these schema/migration/workflow contract defects.

## Enterprise R7 UAT Candidate — 2026-09-26

### Group finance controls
- Employee cash advance / reimbursement lifecycle with ledger posting.
- Loan facility, disbursement, principal/interest repayment.
- Capital contribution, dividend and owner-draw accounting movements.
- Budget scenario / forecast versions and monthly target lines.
- Notification center derived from live financial conditions.
- Management KPI service: margin, current ratio, cash ratio, DSO, DPO, leverage and working capital.
- Management control ratios surfaced on Executive Dashboard.

### R6 usability included in fresh R7 schema
- Bulk onboarding/import jobs and row-level errors.
- AP payment batches.
- Protected evidence/document attachments with SHA-256.

### CI / schema reliability
- Fresh UAT standardized on `schema_enterprise_r7.sql`.
- Upgrade UAT standardized on R2 → v6 → v7 → v8 → v9 → v10.
- Fixed malformed multi-command GitHub workflow steps for R6/R7 tests.
- Architecture gate expanded to require R6/R7 runtime, UI, migrations and services.
- HTTP coverage expanded to 33 pages.
- Browser E2E expanded to advance, loan/equity, planning and notification workflows.
- Health check expanded to require R6/R7 production tables.

### Regression status
- 195 workflow/rule tests PASS locally.
- Architecture PASS.
- Production fail-closed PASS.
- 33/33 demo routes HTTP 200.
- 7/7 report views HTTP 200.
- No PHP warning/fatal during route sweep.
