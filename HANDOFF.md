# HANDOFF — NEXA Enterprise R7.3 GitHub UAT Root-Fix Candidate

Baseline: `NEXA-GROUP-FINANCE-ENTERPRISE-R7.3-GITHUB-UAT-ROOT-FIX-2026-10-04.zip`
Version: `7.0.3-r7.3-deep-audit`
GitHub: `reyvo1/pusatdoi`
User local repo: `/home/ivo/Desktop/program/pusatdoi`
Last observed GitHub SHA before this repair: `80beb34cbad701553a87b22a876d7a6e3215f301`
Failed Full UAT run analyzed: `37206221993`

This full source is the source of truth for the next chat. Do not return to R7.2/R7.2.2 patch baselines.

Local deterministic evidence after the current root-fix: 282/282 assertions PASS, PHP/JS lint PASS, architecture PASS, production fail-closed PASS. Existing demo route/report sweep evidence remains 33/33 pages and 7/7 report views HTTP 200 with no PHP warning/fatal.

Confirmed GitHub root causes fixed in this candidate:

1. Fresh seed `income_categories` declared 8 columns but supplied 7 values; all rows now include explicit `is_active=1`. A generic seed INSERT arity gate prevents recurrence.
2. `r4SqlCompanyMetrics()` used four SQL placeholders for two repeated date ranges but supplied only two PDO parameters; both start/end pairs are now bound.
3. Production `reverseJournal()` persisted `reversal_of` correctly but omitted it from its return contract; production return now exposes the original journal id.
4. Standalone MySQL workflow no longer uses a narrow `paths:` trigger. It runs on every `main`/`master` push so exact-SHA release evidence cannot silently mix commits.

UAT remains strict. No assertion was removed, relaxed, converted to warning, or put behind `continue-on-error`.

RULE: Never weaken UAT. If GitHub remains red, inspect the first causal error and fix application/schema/backend/frontend/workflow code. Do not bypass foreign keys or change expected business results merely to force green.

Next mandatory action: overlay this full source at repo root while preserving `.git`, run `bash tests/run-all-local.sh`, commit, push, then evaluate both GitHub workflows on the new exact SHA. Production Final remains blocked until MySQL 8.4 fresh install, migration regression, HTTP/security/reports, browser E2E, scale simulation, and final verdict are all green on the same SHA.
