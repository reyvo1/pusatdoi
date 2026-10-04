# Architecture

Sources (Hotel/PMS, POS, Property, CSV, API) -> Staging/Mapping -> Validation -> Double-entry Ledger -> Subledgers -> Consolidation -> Reports/Dashboard.

The ledger is authoritative. Dashboard totals are derived from posted journal lines, not duplicated summary fields. Cross-entity records carry an explicit counterparty so reconciliation and future elimination can be deterministic.
