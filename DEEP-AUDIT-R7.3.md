# NEXA Group Finance — R7.3 Deep Audit

Date: 2026-10-04
Version: 7.0.3-r7.3-deep-audit
Status: UAT Candidate — NOT Production Final until GitHub MySQL/browser/scale gates are green.

## Audit scope
Full source audit covered canonical fresh schema, R2→R7 migrations, accounting runtime, frontend→API→backend contracts, entity scoping/RBAC, reports, integration/idempotency, uploads/imports, setup/auth bootstrap, production fail-closed behavior, CI workflows, and demo HTTP rendering.

## Root causes fixed (not UAT bypasses)
- Canonical fresh schema no longer contains migration ALTER leftovers.
- Fresh schema FK dependency order corrected (`parties` exists before `invoices`).
- Schema contract rejects forward foreign keys, duplicate tables/columns/indexes/constraints, and literal TABs in workflow YAML.
- Migration UAT preserves legacy company/COA rows exactly and allows only the five specified required system accounts.
- `SchemaInspector` reads INFORMATION_SCHEMA deterministically using `TABLE_NAME AS table_name` + `PDO::FETCH_COLUMN`.
- Multi-currency journal UI/backend contract requires explicit FX rate/foreign amount and rejects inconsistent conversions.
- Production no longer silently defaults to Demo Mode when `NEXA_ENV=production`.
- First Group Owner setup in production requires a configured strong `NEXA_SETUP_KEY` (minimum 24 chars).
- Inline `APP_DATA` JSON uses JSON_HEX flags to prevent script-breakout stored XSS.
- PDO/database failures returned by API are masked from clients and logged server-side.
- Integration idempotency is immutable and concurrency-safe using atomic no-op upsert + canonical payload comparison; conflicting reuse returns 409.
- Integration `external_ref` length is aligned to 160 chars across API, fresh schema and upgrade migration.
- Bulk CSV/bank imports have file/row resource limits.
- Authenticated POST API routes remain CSRF-protected.
- HTTP UAT now hard-fails unauthorized mutation attempts unless they return HTTP 401 and leave database state unchanged; the previous non-blocking `|| true` check was removed.
- Runtime logs are excluded from Git (`storage/*.log`).

## Local evidence
- Schema/migration contract: 37/37 PASS
- Frontend/backend contract: 26/26 PASS
- Deep audit security/integrity: 16/16 PASS
- Legacy regression: 62/62 PASS
- Enterprise domain: 35/35 PASS
- Enterprise advanced: 33/33 PASS
- R4 workflow: 21/21 PASS
- R5 finance operations: 22/22 PASS
- R6 usability: 6/6 PASS
- R7 group controls: 20/20 PASS
- Deterministic assertions: 278/278 PASS
- PHP lint: PASS
- JavaScript syntax: PASS
- Architecture/modularity: PASS (107 enterprise PHP modules)
- Production fail-closed: PASS
- Demo route sweep: 33/33 HTTP 200
- Report render sweep: 7/7 HTTP 200
- PHP fatal/warning in sweep: 0

## Mandatory external proof still pending
Local environment does not provide the same MySQL 8.4/Playwright production stack as GitHub. Do not label this Production Final until these GitHub gates pass without weakening them:
1. MySQL 8.4 fresh schema + accounting UAT
2. R2→R7 migration with exact legacy preservation
3. HTTP/security/reports/export UAT
4. Browser E2E including real multi-currency journal round trip
5. Multi-entity/high-volume simulation
6. Production Candidate final verdict

## Residual enterprise hardening (not hidden)
- Strict CSP without inline handlers/scripts would require a larger frontend event-binding refactor.
- Real production-volume benchmark (millions of journal lines) remains required.
- Bank API, statutory DJP connectors, SSO/MFA, WORM/SIEM and external notification delivery are external integrations, not locally proven here.
