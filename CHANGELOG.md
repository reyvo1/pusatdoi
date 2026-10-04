# CHANGELOG

## Enterprise R7.3 Deep Audit UAT Candidate — 2026-10-04
- Full source/security/integrity audit; no UAT weakening.
- Production environment now defaults fail-closed instead of silently enabling demo mode.
- First-owner production setup requires strong configured setup key.
- Hardened inline APP_DATA against stored script breakout.
- Masked raw PDO errors from API clients.
- Integration idempotency is immutable and concurrency-safe; conflicting duplicate keys return 409.
- Aligned integration external_ref to 160 chars across API/schema/migration.
- Added CSV/bank-import resource limits and deep security contract gate.
- Preserved R7.2.2 canonical schema dependency and exact migration-preservation fixes.
- Final local evidence: 278/278 deterministic assertions PASS; 33/33 demo pages + 7/7 report views HTTP 200.

## Enterprise R7.2.2 GitHub UAT Root Fix — 2026-10-04

- Fixed canonical fresh-schema dependency order: `parties` now exists before `invoices.party_id` foreign key is created.
- Added static forward-FK dependency detection to schema contract so future table-order regressions fail before GitHub MySQL import.
- Strengthened R2→R7 migration preservation: exact legacy company/account rows must remain byte-for-byte equivalent on selected business fields.
- Migration UAT now separately asserts the five intentional system accounts required by R4/R5 (`1161`, `2201`, `3102`, `5401`, `5501`) with exact names/types/flags and exact final count.
- Added workflow YAML TAB guard; both workflow files parse cleanly after the repair.
- No UAT assertion was removed or converted to warning/continue-on-error.

## Enterprise R7.2 Root-Fix UAT Candidate — 2026-10-04

- Refactored `schema_enterprise_r7.sql` into a true final-state fresh schema; removed migration ALTER leftovers.
- Corrected final bank reconciliation session shape to match runtime backend.
- Hardened SchemaInspector with aliased `TABLE_NAME` + `PDO::FETCH_COLUMN`.
- Fixed multi-currency Journal Multi-Line frontend/backend contract.
- Backend now rejects non-base journals without foreign amount or inconsistent FX conversion.
- Added `tests/frontend-backend-contract.php`.
- Expanded schema contract to detect duplicate tables, named indexes and constraints.
- Added real browser UI→API→backend→MySQL multi-currency journal round-trip.
- Added schema/frontend contracts and JavaScript checks to MySQL workflow.
- Expanded MySQL workflow path triggers to assets/tests changes.
- Local full gate: 258 assertions PASS, 0 FAIL.
