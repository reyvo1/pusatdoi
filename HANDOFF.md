# HANDOFF — NEXA Enterprise R7 UAT Candidate

Baseline: `NEXA-GROUP-FINANCE-ENTERPRISE-R7-UAT-CANDIDATE-2026-09-26.zip`

Do not revert to R2/R3/R4. R7 includes the finance-operation and usability layers from R5/R6 plus group-control workflows (advance, financing, equity, scenario planning, notification/KPI).

Canonical fresh schema: `database/schema_enterprise_r7.sql`.
Canonical upgrade chain from R2: v6, v7, v8, v9, v10 in order.

Primary local gate:

```bash
bash tests/run-all-local.sh
```

Primary remote gate: GitHub Actions `NEXA Full UAT` on `reyvo1/doipusat`.

If GitHub is red, preserve safety controls and fix root cause. Never bypass RBAC, approval thresholds, period locks, journal-balance checks, entity scope, migration constraints or production fail-closed behavior merely to pass CI.
