# Changelog

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
