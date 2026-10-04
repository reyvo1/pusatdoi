# NEXA Enterprise R7 — Release Evidence

## Locally proven

- PHP syntax: PASS across source.
- JavaScript syntax: PASS (`app.js`, R4-R7 UI, browser UAT script).
- Legacy regression: 62/62 PASS.
- Enterprise domain: 35/35 PASS.
- Enterprise advanced: 33/33 PASS.
- R4 workflow/UI-API: 17/17 PASS.
- R5 finance operations: 22/22 PASS.
- R6 usability: 6/6 PASS.
- R7 group finance controls: 20/20 PASS.
- Total workflow/rule assertions: **195 PASS**.
- Architecture gate: PASS, 107 enterprise PHP modules discovered.
- Production fail-closed: PASS when PDO MySQL is unavailable.
- Demo route sweep: 33/33 HTTP 200.
- Report view sweep: 7/7 HTTP 200.
- PHP warning/fatal during route sweep: 0.
- GitHub workflow YAML parse: PASS.

## R7 workflows actually executed locally

- Employee advance issue → balanced journal → settlement/reimbursement.
- Loan disbursement → outstanding principal → principal/interest repayment.
- Capital contribution/equity posting.
- Budget scenario/version → monthly target → variance comparison.
- Live notification derivation.
- Management KPI calculation.
- Entity Admin scoping for new R7 domains.
- R6 bulk COA import, AP payment batch and protected document evidence.

## Must still be proven by GitHub

Local runtime does not provide PDO MySQL/MySQL server, so no claim is made that MySQL R7 tests passed locally. GitHub must prove:

- clean `schema_enterprise_r7.sql` on MySQL 8.4;
- R2→R7 migration v6→v10;
- `r7-mysql-uat.php` and relational DB UAT;
- HTTP/security/report/export paths on production-mode DB;
- Browser E2E including R7 modals and visual artifacts;
- high-volume/multi-entity simulation;
- final verdict only if every dependency succeeds.
