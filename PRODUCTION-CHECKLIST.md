# Production Checklist — Enterprise R7

- [ ] Verify ZIP SHA-256.
- [ ] Run `bash tests/run-all-local.sh` on Ubuntu.
- [ ] Fresh install uses `database/schema_enterprise_r7.sql`, or upgrade R2 with v6→v10 migrations in order.
- [ ] Backup production database before migration and test restore.
- [ ] Set `NEXA_DEMO_MODE=false` and valid DB credentials.
- [ ] Set strong setup/integration/worker secrets outside web root or environment manager.
- [ ] Use HTTPS and secure PHP session cookie settings at hosting/web-server layer.
- [ ] Create real Group Owner; remove/disable CI/test credentials.
- [ ] Verify company/entity scope for every Entity Admin.
- [ ] Load real COA, opening balances, fiscal periods, tax profiles and bank mappings.
- [ ] Configure approval policies and closing checklist.
- [ ] Run GitHub `NEXA Full UAT`; require every gate green.
- [ ] Review Browser E2E screenshot artifacts.
- [ ] Run MySQL backup + restore DR smoke.
- [ ] UAT real sample: daily income/expense, AR/AP, bank reconciliation, advance, loan, asset, consolidation, tax/FX, reports/export.
- [ ] Benchmark expected production transaction volume before sign-off.
- [ ] Keep PMS/POS/HR/inventory systems as source systems; integrate via staging/idempotent API.
