# RELEASE EVIDENCE — NEXA Enterprise R7.2 Root-Fix UAT Candidate

Version: `7.0.2-r7.2-uat`  
Date: 2026-10-04

## Grounded external evidence

The repair was derived from uploaded GitHub Actions logs for repository `reyvo1/pusatdoi` and audited against exact commit:

`b9c4d796022482728349ef9f4b330f2f71649c1d`

Observed GitHub failures:

- Fresh schema: `ERROR 1054 (42S22) ... Unknown column 'period_end' in 'bank_reconciliation_sessions'`.
- Migration verifier: reported every enterprise table missing after migration steps completed.

See `GITHUB-UAT-ROOT-CAUSE-R7.2.md`.

## Local evidence after repair

- 258/258 contract, rule and workflow assertions PASS.
- All PHP syntax PASS.
- All JavaScript syntax PASS.
- Architecture gate PASS.
- Production fail-closed PASS.
- Demo route sweep: 33/33 pages HTTP 200.
- Demo server PHP fatal/warning: 0.
- Fresh schema static contract: no migration ALTER leftovers, duplicate table definitions, duplicate columns, duplicate named indexes or duplicate constraint names.
- UI API action parity: all detected frontend actions have backend routes.
- Critical journal, reconciliation, invoice and R5–R7 runtime contracts PASS.

## External proof intentionally pending

This environment has no PDO drivers/MySQL server. Therefore the following remain GitHub-only mandatory gates:

- MySQL 8.4 fresh schema import and seed.
- R2 → R7 migration chain.
- production MySQL accounting tests.
- HTTP/security/report/export UAT.
- Browser E2E real UI→API→backend→MySQL FX journal posting.
- multi-entity/high-volume simulation.

No Production Final claim is made until those gates are green.
