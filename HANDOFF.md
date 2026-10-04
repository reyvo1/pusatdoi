# HANDOFF — NEXA Enterprise R7.2 Root-Fix UAT Candidate

Baseline version: `7.0.2-r7.2-uat`.

The latest uploaded GitHub logs were traced against exact commit `b9c4d796022482728349ef9f4b330f2f71649c1d`.

Root causes fixed in source:

1. fresh R7 schema mixed old CREATE shapes with migration ALTERs and failed at `bank_reconciliation_sessions.period_end`;
2. SchemaInspector could treat all MySQL tables as missing due to INFORMATION_SCHEMA result-key casing;
3. Journal Multi-Line UI exposed foreign currency without sending/validating FX amount/rate.

Do not revert to R7/R7.1 patches. Push the complete R7.2 source (or the R7.2 patch) and use the new GitHub logs as the next evidence. Do not weaken any UAT gate.

Local evidence: 258 assertions PASS, 0 FAIL + lint/architecture/fail-closed + 33/33 demo HTTP pages.

Mandatory external evidence still pending: MySQL 8.4 fresh schema, R2→R7 migration, HTTP/security/reports, Browser E2E real journal round-trip and scale simulation.
