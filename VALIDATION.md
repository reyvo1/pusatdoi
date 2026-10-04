# VALIDATION — Enterprise R7.1 UAT Repair Candidate

Date: 2026-10-04

## Local deterministic gates
- Schema/migration/CI contract: 6/6 PASS
- Legacy regression: 62/62 PASS
- Enterprise domain: 35/35 PASS
- Enterprise advanced: 33/33 PASS
- R4 workflow: 17/17 PASS
- R5 operations: 22/22 PASS
- R6 usability: 6/6 PASS
- R7 group controls: 20/20 PASS
- Total rule/workflow assertions: 201 PASS
- Architecture gate: PASS
- Production fail-closed: PASS
- PHP lint: PASS
- JavaScript syntax (`app.js`, `r4-ui.js`, `r5-ui.js`, `r6-ui.js`, `r7-ui.js`): PASS

## GitHub log root-cause repair
See `GITHUB-UAT-FAILURE-ANALYSIS.md`. The repair addresses:
1. duplicate `uq_payment_invoice` in fresh schema / v7;
2. `uq_budget` foreign-key support during v6 migration;
3. date-dependent consolidation test;
4. malformed standalone MySQL workflow command block.

## Mandatory next gate
Re-run both GitHub Actions workflows on MySQL 8.4. Do not mark Production Final until Full UAT and standalone MySQL Production Simulation are green.
