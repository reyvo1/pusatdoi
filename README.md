# NEXA Group Finance — Enterprise R7 UAT Candidate

NEXA Group Finance adalah pusat keuangan multi-badan-usaha berbasis PHP + MySQL untuk konsolidasi hotel, retail, kos/properti, F&B, jasa, dan entitas lain dalam satu group. Runtime produksi tidak membutuhkan Node.js; Node/Playwright hanya digunakan pada CI Browser E2E.

## Status

**Enterprise R7 UAT Candidate (`7.0.0-r7-uat`)**. Source ini belum diberi label Production Final sampai seluruh GitHub Full UAT hijau pada MySQL 8.4, migration chain, HTTP/security/reports, Browser E2E, dan high-volume simulation.

## Fitur utama

- Multi company, branch, department, cost/profit center
- Chart of Accounts dan dimensional double-entry ledger
- Pendapatan & pengeluaran harian dengan tax/settlement
- AR/AP, invoice lines, partial payment, payment allocation, AP payment batch
- Cash & bank, transfer, CSV statement import, reconciliation
- Employee advance / reimbursement
- Loan / financing, repayment, interest, capital contribution, dividend/owner draw
- Budget, cash forecast, recurring journal, scenario planning
- Fixed asset lifecycle, depreciation, transfer, impairment, disposal
- Tax register, multi-currency / FX gain-loss
- Intercompany matching, consolidation runs, elimination entries
- Period close, year-end close, approval policies
- Bulk onboarding/import, opening balance, protected evidence documents
- Notification center dan management control ratios
- Integration inbox/outbox, idempotency, retry/dead-letter foundation
- Audit trail, logical snapshot + database backup/restore scripts
- 7 report views: P&L, Balance Sheet, Changes in Equity, Cash Flow, Ledger, Trial Balance, Intercompany

## Fresh install

```bash
mysql -u USER -p < database/schema_enterprise_r7.sql
mysql -u USER -p < database/seed_enterprise.sql
```

Set environment `NEXA_DEMO_MODE=false`, DB credentials, integration key, dan worker key. Gunakan HTTPS pada production.

## Upgrade dari baseline R2

Jalankan migration secara berurutan:

```text
database/migrations/20260925_v6_enterprise_r3.sql
database/migrations/20260926_v7_enterprise_r4.sql
database/migrations/20260926_v8_finance_operations.sql
database/migrations/20260926_v9_enterprise_usability.sql
database/migrations/20260926_v10_group_finance_controls.sql
```

Jangan lompat urutan dan selalu backup database sebelum migration.

## Local regression

```bash
bash tests/run-all-local.sh
```

Baseline saat paket dikunci: 62 legacy + 35 domain + 33 advanced + 17 R4 + 22 R5 + 6 R6 + 20 R7 = **195 workflow/rule tests PASS**, ditambah architecture gate dan production fail-closed gate.

## GitHub Full UAT

Workflow `.github/workflows/full-uat.yml` menjalankan:

1. Core / Static Regression
2. MySQL / Accounting UAT pada `schema_enterprise_r7.sql`
3. R2 → R7 Migration UAT (v6→v10)
4. HTTP / Security / Reports UAT
5. Browser E2E / Visual UAT
6. Multi-Entity / High-Volume UAT
7. Final verdict yang hanya hijau jika semua gate hijau

## Ubuntu repo

Repo lokal user:

```bash
cd ~/Desktop/program/'keuangan sentral'
```

Repo GitHub target: `reyvo1/doipusat`.

## Batas yang sengaja belum diklaim

Direct bank Open-Banking connectors, statutory Indonesian e-Faktur/e-Bupot filing, SSO/MFA provider integration, WORM/external audit storage, dan benchmark jutaan baris harus divalidasi/diintegrasikan sesuai infrastruktur nyata. Inventory, payroll, PMS/POS, dan property operations tetap sebaiknya menjadi source system terpisah yang mengirim data ke NEXA.
