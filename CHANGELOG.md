# CHANGELOG

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
