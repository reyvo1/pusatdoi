# Deep Coverage Audit — NEXA Enterprise R7

## Central finance scope now covered end-to-end or with an explicit runtime workflow

Entity/organization/COA, double-entry journal, daily income and expense, customer/vendor, AR/AP invoice and allocations, AP batches, bank transfer/reconciliation/import, employee advances/reimbursements, loans/repayments, equity distributions/contributions, budget/forecast/scenario planning, assets/depreciation/disposal, tax register/calculation, FX settlement, intercompany/consolidation/elimination, period/year-end close, recurring journals, opening balances, bulk imports, evidence documents, approval, notification, KPI, audit and integration staging.

## Residual enterprise-hardening items (not blockers for GitHub UAT candidate)

1. Direct bank/Open-Banking connectors and automatic statement polling require actual bank providers/credentials.
2. Indonesian statutory filing adapters (e-Faktur/e-Bupot/SPT) require current DJP interface/legal mapping and should be a separate connector layer.
3. SSO/SAML/OIDC and MFA/TOTP depend on deployment identity policy; current app has role/session/password controls but these integrations are not claimed.
4. Immutable/WORM audit export and SIEM forwarding require external storage/observability infrastructure.
5. Email/Telegram/Slack notification delivery is not yet a guaranteed production channel; current Notification Center derives in-app alerts.
6. Large-scale performance must be benchmarked on representative MySQL data/hosting. SQL reporting paths exist, but millions-of-lines SLA is not claimed before scale evidence.
7. Inventory, payroll, hotel PMS, POS and property operations are deliberately not duplicated in this central finance system; they should integrate through the staging/idempotent integration layer.

## Release decision

R7 is feature-complete enough for **Full GitHub UAT** of the central-finance product boundary. It remains a UAT Candidate until MySQL, migration, HTTP/security, browser and scale gates all pass.
