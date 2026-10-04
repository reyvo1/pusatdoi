# Changelog

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
