# PRODUCTION CHECKLIST — R7.3

- [ ] Verify full-source ZIP SHA-256 and `SOURCE-MANIFEST.txt`.
- [ ] Run `bash tests/run-all-local.sh` after extraction.
- [ ] Fresh install uses `database/schema_enterprise_r7.sql`; upgrades use official migrations v6→v10 in order.
- [ ] `NEXA_ENV=production`, Demo Mode disabled, PDO MySQL enabled.
- [ ] Configure strong `NEXA_SETUP_KEY` (>=24 chars) before first Group Owner bootstrap, then rotate/disable setup exposure after initialization.
- [ ] Replace all CI/test credentials and integration keys with production secrets.
- [ ] Backup database and prove restore on staging before migration.
- [ ] Load/review real COA, opening balances, bank mappings, tax profiles, users, roles, approval policies and fiscal periods.
- [ ] GitHub NEXA Full UAT all gates green on the exact production-candidate SHA.
- [ ] GitHub NEXA MySQL Production Simulation green.
- [ ] Review Browser E2E visual evidence.
- [ ] Run production-volume benchmark before final sign-off.
- [ ] Keep PMS/POS/HR/inventory as source systems; integrate through idempotent staging/inbox APIs.
