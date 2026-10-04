# HANDOFF — NEXA Enterprise R7.1 UAT Repair Candidate

Baseline: `NEXA-GROUP-FINANCE-ENTERPRISE-R7.1-UAT-REPAIR-2026-10-04.zip`

GitHub target: `reyvo1/pusatdoi`
Local Ubuntu repo: `/home/ivo/Desktop/program/pusatdoi`

The 2026-10-04 GitHub red run was analyzed from two uploaded log ZIPs. Root causes and fixes are documented in `GITHUB-UAT-FAILURE-ANALYSIS.md`. Local deterministic gates are green (201 assertions plus architecture/fail-closed/lint).

Do not weaken UAT. Next action is to overwrite the repo with R7.1 repair source, commit, push, and evaluate the new GitHub logs. MySQL 8.4 fresh schema and R2→R7 migration are still mandatory external proof.
