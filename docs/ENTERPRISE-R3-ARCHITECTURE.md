# NEXA Group Finance — Enterprise R3 Architecture

## Design goals

Enterprise R3 separates accounting rules from HTTP/UI and storage. The root PHP pages remain shared-hosting friendly, while business rules live under `src/` and are autoloaded without Composer.

### Layers

1. **UI / HTTP** — `index.php`, `api.php`, exports, integration endpoints.
2. **Application** — orchestration via `Nexa\\Application\\EnterpriseKernel`.
3. **Domain** — accounting, revenue, AR/AP, banking, consolidation, tax, FX, assets, budget, reports, security, audit.
4. **Infrastructure / legacy bridge** — converts existing state/database rows into typed domain objects.
5. **Database** — MySQL/InnoDB, migrations, foreign keys, unique constraints, idempotency keys.

## Core invariants

- Posted journals must have debit = credit and at least two valid lines.
- A closed fiscal period blocks posting/reversal/payment/elimination.
- Company-scoped users cannot operate on another entity.
- Large transactions require approval unless the internal approval process authorizes posting.
- Reversal is additive: original journals are never deleted.
- Daily income is a multi-line balanced journal: revenue credits and cash/bank debits.
- AR/AP allocation cannot exceed invoice outstanding balance.
- Bank reconciliation requires same company, matching cash direction, and matching amount.
- Consolidation eliminations are group-layer records; entity books remain unchanged.
- Integration writes to staging/idempotent event inbox before posting to the ledger.
- Backup restore verifies SHA-256 before mutation.

## Shared hosting

No Node.js is required on production. JavaScript/CSS are static assets. Node/Playwright is used only in CI browser UAT.
