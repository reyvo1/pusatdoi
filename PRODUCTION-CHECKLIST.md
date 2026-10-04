# PRODUCTION CHECKLIST — R7.2

R7.2 is a UAT candidate, not Production Final.

Before production sign-off:

- [ ] `NEXA Full UAT` all jobs green on GitHub.
- [ ] `NEXA MySQL Production Simulation` green on GitHub.
- [ ] Fresh MySQL 8.4 import of `database/schema_enterprise_r7.sql` PASS.
- [ ] R2→R7 migration chain PASS with legacy counts preserved.
- [ ] Browser E2E real multi-currency journal round-trip PASS.
- [ ] HTTP/security/reports/export gate PASS with no PHP warning/fatal.
- [ ] Scale simulation PASS.
- [ ] Production environment has PDO MySQL enabled.
- [ ] Real company COA, opening balances, bank accounts, tax profiles, users/roles and approval policies reviewed.
- [ ] Backup/restore drill completed on staging data.
- [ ] Integration keys and application secrets replaced with production secrets.
- [ ] Demo mode disabled and application fails closed if DB is unavailable.
