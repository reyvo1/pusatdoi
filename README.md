# NEXA Group Finance — Enterprise R7.3 Deep Audit UAT Candidate

Version: `7.0.3-r7.3-deep-audit`
Date: 2026-10-04

R7.2 is built from the exact source audited in GitHub commit `b9c4d796022482728349ef9f4b330f2f71649c1d` and fixes the root causes exposed by the latest MySQL 8.4 UAT logs.

## Important R7.2 corrections

- canonical fresh schema is final-shape only; migration ALTER statements are no longer replayed inside fresh installs;
- final `bank_reconciliation_sessions` schema matches backend fields;
- MySQL `SchemaInspector` no longer depends on INFORMATION_SCHEMA key casing;
- Journal Multi-Line frontend/backend multi-currency contract is explicit and validated;
- static frontend → API → runtime → schema contract gate added;
- Browser E2E now posts a real USD journal through the UI and verifies it in the ledger;
- separate MySQL workflow now executes schema/frontend contracts and JS checks.

See `GITHUB-UAT-ROOT-CAUSE-R7.2.md` for the evidence and detailed root-cause analysis.

## Fresh installation

Use only:

```bash
mysql -uUSER -p < database/schema_enterprise_r7.sql
mysql -uUSER -p < database/seed_enterprise.sql
```

Do **not** run R3/R4/R5/R6/R7 migrations after the fresh schema. They are only for upgrading an older database.

## Upgrade path from R2

Apply, in order:

1. `database/migrations/20260925_v6_enterprise_r3.sql`
2. `database/migrations/20260926_v7_enterprise_r4.sql`
3. `database/migrations/20260926_v8_finance_operations.sql`
4. `database/migrations/20260926_v9_enterprise_usability.sql`
5. `database/migrations/20260926_v10_group_finance_controls.sql`

Then run `php tests/schema-enterprise.php`.

## Local gate

```bash
bash tests/run-all-local.sh
```

Current R7.2 local result: **258 assertions PASS, 0 FAIL** plus PHP/JS lint, architecture and fail-closed PASS.

Production Final is intentionally withheld until all GitHub MySQL 8.4 / migration / HTTP / Browser E2E / scale gates are green.

## R7.3 deep-audit safety notes
- Production defaults fail-closed; demo fallback is not automatic when `NEXA_ENV=production`.
- First production owner bootstrap requires `NEXA_SETUP_KEY` >= 24 characters.
- See `DEEP-AUDIT-R7.3.md` for the complete root-cause audit and residual external proof.
