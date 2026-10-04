-- NEXA Group Finance Enterprise R4
-- Adds end-to-end dimensions, counterparties, multicurrency, closing controls,
-- integration delivery controls, real reconciliation/import metadata, and report snapshots.

ALTER TABLE journal_lines
  ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER account_id,
  ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER branch_id,
  ADD COLUMN project_code VARCHAR(80) NULL AFTER profit_center,
  ADD COLUMN transaction_currency CHAR(3) NOT NULL DEFAULT 'IDR' AFTER project_code,
  ADD COLUMN foreign_amount DECIMAL(24,6) NULL AFTER transaction_currency,
  ADD COLUMN exchange_rate DECIMAL(24,8) NOT NULL DEFAULT 1 AFTER foreign_amount,
  ADD COLUMN base_amount DECIMAL(20,2) NULL AFTER exchange_rate,
  ADD KEY idx_jl_dimensions(branch_id,department_id,cost_center,profit_center),
  ADD KEY idx_jl_currency(transaction_currency),
  ADD CONSTRAINT fk_jl_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  ADD CONSTRAINT fk_jl_department FOREIGN KEY(department_id) REFERENCES departments(id),
  ADD CONSTRAINT chk_jl_exchange_rate CHECK(exchange_rate > 0);

CREATE TABLE parties (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  party_type ENUM('customer','vendor','both') NOT NULL,
  code VARCHAR(50) NOT NULL,
  name VARCHAR(180) NOT NULL,
  tax_id VARCHAR(80) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NULL,
  address_text TEXT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  ar_account_id BIGINT UNSIGNED NULL,
  ap_account_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_party_code(company_id,code),
  KEY idx_party_type(company_id,party_type,is_active),
  CONSTRAINT fk_party_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_party_ar FOREIGN KEY(ar_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_party_ap FOREIGN KEY(ap_account_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

ALTER TABLE invoices
  ADD COLUMN party_id BIGINT UNSIGNED NULL AFTER branch_id,
  ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER party_id,
  ADD COLUMN subtotal DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER due_date,
  ADD COLUMN tax_total DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER subtotal,
  ADD COLUMN journal_id BIGINT UNSIGNED NULL AFTER external_ref,
  ADD COLUMN notes TEXT NULL AFTER journal_id,
  ADD CONSTRAINT fk_invoice_party FOREIGN KEY(party_id) REFERENCES parties(id),
  ADD CONSTRAINT fk_invoice_department FOREIGN KEY(department_id) REFERENCES departments(id),
  ADD CONSTRAINT fk_invoice_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id);

ALTER TABLE invoice_payments
  ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'IDR' AFTER amount,
  ADD COLUMN foreign_amount DECIMAL(24,6) NULL AFTER currency,
  ADD COLUMN exchange_rate DECIMAL(24,8) NOT NULL DEFAULT 1 AFTER foreign_amount;

ALTER TABLE payment_allocations
  ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

CREATE TABLE invoice_adjustments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  adjustment_type ENUM('credit_note','debit_note','writeoff') NOT NULL,
  adjustment_no VARCHAR(60) NOT NULL,
  adjustment_date DATE NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_invoice_adjustment_no(adjustment_no),
  KEY idx_invoice_adjustment(invoice_id,adjustment_date),
  CONSTRAINT fk_invoice_adjustment_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_invoice_adjustment_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_invoice_adjustment_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_invoice_adjustment_amount CHECK(amount > 0)
) ENGINE=InnoDB;

CREATE TABLE bank_import_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  bank_account_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  delimiter_char VARCHAR(5) NOT NULL DEFAULT ',',
  date_column VARCHAR(80) NOT NULL,
  description_column VARCHAR(80) NOT NULL,
  debit_column VARCHAR(80) NULL,
  credit_column VARCHAR(80) NULL,
  amount_column VARCHAR(80) NULL,
  reference_column VARCHAR(80) NULL,
  date_format VARCHAR(40) NOT NULL DEFAULT 'Y-m-d',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bank_import_profile(company_id,name),
  CONSTRAINT fk_bank_profile_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_profile_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id)
) ENGINE=InnoDB;

ALTER TABLE bank_reconciliation_sessions
  ADD COLUMN period_start DATE NULL AFTER period,
  ADD COLUMN period_end DATE NULL AFTER period_start,
  ADD COLUMN matched_amount DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER closing_balance,
  ADD COLUMN difference_amount DECIMAL(20,2) NOT NULL DEFAULT 0 AFTER matched_amount,
  ADD COLUMN closed_by BIGINT UNSIGNED NULL AFTER closed_at,
  ADD COLUMN reopened_at DATETIME NULL AFTER closed_by,
  ADD COLUMN reopened_by BIGINT UNSIGNED NULL AFTER reopened_at,
  ADD CONSTRAINT fk_recon_closed_by FOREIGN KEY(closed_by) REFERENCES users(id),
  ADD CONSTRAINT fk_recon_reopened_by FOREIGN KEY(reopened_by) REFERENCES users(id);

ALTER TABLE fixed_assets
  ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER branch_id,
  ADD COLUMN asset_account_id BIGINT UNSIGNED NULL AFTER depreciation_method,
  ADD COLUMN accumulated_depreciation_account_id BIGINT UNSIGNED NULL AFTER asset_account_id,
  ADD COLUMN depreciation_expense_account_id BIGINT UNSIGNED NULL AFTER accumulated_depreciation_account_id,
  ADD COLUMN acquisition_journal_id BIGINT UNSIGNED NULL AFTER depreciation_expense_account_id,
  ADD COLUMN disposal_date DATE NULL AFTER status,
  ADD COLUMN disposal_journal_id BIGINT UNSIGNED NULL AFTER disposal_date,
  ADD CONSTRAINT fk_asset_department FOREIGN KEY(department_id) REFERENCES departments(id),
  ADD CONSTRAINT fk_asset_account FOREIGN KEY(asset_account_id) REFERENCES chart_accounts(id),
  ADD CONSTRAINT fk_asset_accum_account FOREIGN KEY(accumulated_depreciation_account_id) REFERENCES chart_accounts(id),
  ADD CONSTRAINT fk_asset_dep_expense_account FOREIGN KEY(depreciation_expense_account_id) REFERENCES chart_accounts(id),
  ADD CONSTRAINT fk_asset_acq_journal FOREIGN KEY(acquisition_journal_id) REFERENCES journal_entries(id),
  ADD CONSTRAINT fk_asset_disposal_journal FOREIGN KEY(disposal_journal_id) REFERENCES journal_entries(id);

ALTER TABLE fiscal_periods
  ADD COLUMN company_id BIGINT UNSIGNED NULL FIRST,
  DROP INDEX uq_fiscal_period,
  ADD UNIQUE KEY uq_fiscal_period_company(company_id,fiscal_year,period),
  ADD KEY idx_period_company(company_id,fiscal_year,period,status),
  ADD CONSTRAINT fk_fiscal_period_company FOREIGN KEY(company_id) REFERENCES companies(id);

CREATE TABLE period_close_tasks (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  fiscal_year SMALLINT NOT NULL,
  period TINYINT UNSIGNED NOT NULL,
  task_code VARCHAR(80) NOT NULL,
  task_name VARCHAR(180) NOT NULL,
  status ENUM('pending','passed','waived') NOT NULL DEFAULT 'pending',
  detail_text VARCHAR(255) NULL,
  completed_by BIGINT UNSIGNED NULL,
  completed_at DATETIME NULL,
  UNIQUE KEY uq_close_task(company_id,fiscal_year,period,task_code),
  CONSTRAINT fk_close_task_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_close_task_user FOREIGN KEY(completed_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE approval_policies
  ADD COLUMN level_no TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER document_type,
  ADD COLUMN max_amount DECIMAL(20,2) NULL AFTER threshold_amount,
  ADD COLUMN require_distinct_approvers TINYINT(1) NOT NULL DEFAULT 1 AFTER min_approvers;

ALTER TABLE journal_approvals
  ADD COLUMN approval_policy_id BIGINT UNSIGNED NULL AFTER required_approvals,
  ADD COLUMN allowed_roles_json JSON NULL AFTER approval_policy_id,
  ADD COLUMN require_distinct_approvers TINYINT(1) NOT NULL DEFAULT 1 AFTER allowed_roles_json,
  ADD CONSTRAINT fk_journal_approval_policy FOREIGN KEY(approval_policy_id) REFERENCES approval_policies(id);

CREATE TABLE approval_decisions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  approval_id BIGINT UNSIGNED NOT NULL,
  approver_id BIGINT UNSIGNED NOT NULL,
  decision ENUM('approved','rejected') NOT NULL,
  note VARCHAR(255) NULL,
  decided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_approval_decision(approval_id,approver_id),
  CONSTRAINT fk_approval_decision_request FOREIGN KEY(approval_id) REFERENCES journal_approvals(id),
  CONSTRAINT fk_approval_decision_user FOREIGN KEY(approver_id) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE tax_transactions
  ADD COLUMN tax_code VARCHAR(40) NULL AFTER tax_profile_id,
  ADD COLUMN filing_period CHAR(7) NULL AFTER transaction_date,
  ADD COLUMN status ENUM('open','reported','paid','void') NOT NULL DEFAULT 'open' AFTER external_ref;

CREATE TABLE fx_revaluation_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  period CHAR(7) NOT NULL,
  currency CHAR(3) NOT NULL,
  closing_rate DECIMAL(24,8) NOT NULL,
  gain_loss_amount DECIMAL(20,2) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fx_revaluation(company_id,period,currency),
  CONSTRAINT fk_fx_reval_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_fx_reval_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_fx_reval_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE consolidation_runs
  ADD COLUMN scope_json JSON NULL AFTER elimination_count,
  ADD COLUMN posted_at DATETIME NULL AFTER validated_by,
  ADD COLUMN locked_at DATETIME NULL AFTER posted_at;

ALTER TABLE elimination_entries
  ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER account_id,
  ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER branch_id,
  ADD CONSTRAINT fk_elimination_entry_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  ADD CONSTRAINT fk_elimination_entry_department FOREIGN KEY(department_id) REFERENCES departments(id);

ALTER TABLE integration_events
  ADD COLUMN event_version VARCHAR(20) NOT NULL DEFAULT '1' AFTER event_type,
  ADD COLUMN mapping_version VARCHAR(40) NULL AFTER event_version,
  ADD COLUMN attempts TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER error_text,
  ADD COLUMN next_attempt_at DATETIME NULL AFTER attempts,
  ADD COLUMN posted_journal_id BIGINT UNSIGNED NULL AFTER next_attempt_at,
  ADD CONSTRAINT fk_integration_posted_journal FOREIGN KEY(posted_journal_id) REFERENCES journal_entries(id);

ALTER TABLE integration_outbox
  ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER id,
  ADD COLUMN idempotency_key CHAR(64) NULL AFTER aggregate_id,
  ADD UNIQUE KEY uq_outbox_idempotency(idempotency_key),
  ADD CONSTRAINT fk_outbox_company FOREIGN KEY(company_id) REFERENCES companies(id);

CREATE TABLE report_snapshots (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  period CHAR(7) NOT NULL,
  report_type VARCHAR(50) NOT NULL,
  dimension_hash CHAR(64) NOT NULL,
  payload_json JSON NOT NULL,
  generated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NULL,
  UNIQUE KEY uq_report_snapshot(company_id,period,report_type,dimension_hash),
  KEY idx_report_snapshot_expiry(expires_at)
) ENGINE=InnoDB;

CREATE TABLE document_sequences (
  company_id BIGINT UNSIGNED NOT NULL,
  document_type VARCHAR(40) NOT NULL,
  period_key CHAR(7) NOT NULL,
  last_number BIGINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY(company_id,document_type,period_key),
  CONSTRAINT fk_doc_sequence_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

-- Canonical RBAC permissions for R4. Runtime reads this table in production.
INSERT IGNORE INTO role_permissions(role_code,permission_code) VALUES
('group_owner','dashboard.view'),('group_owner','company.view'),('group_owner','company.manage'),('group_owner','journal.view'),('group_owner','journal.post'),('group_owner','journal.reverse'),('group_owner','income.manage'),('group_owner','approval.decide'),('group_owner','period.close'),('group_owner','arap.manage'),('group_owner','bank.manage'),('group_owner','bank.import'),('group_owner','bank.reconcile'),('group_owner','budget.manage'),('group_owner','asset.manage'),('group_owner','tax.manage'),('group_owner','fx.manage'),('group_owner','consolidation.manage'),('group_owner','integration.manage'),('group_owner','report.view'),('group_owner','report.export'),('group_owner','user.manage'),('group_owner','audit.view'),('group_owner','backup.manage'),
('group_finance','dashboard.view'),('group_finance','company.view'),('group_finance','journal.view'),('group_finance','journal.post'),('group_finance','journal.reverse'),('group_finance','income.manage'),('group_finance','approval.decide'),('group_finance','period.close'),('group_finance','arap.manage'),('group_finance','bank.manage'),('group_finance','bank.import'),('group_finance','bank.reconcile'),('group_finance','budget.manage'),('group_finance','asset.manage'),('group_finance','tax.manage'),('group_finance','fx.manage'),('group_finance','consolidation.manage'),('group_finance','integration.manage'),('group_finance','report.view'),('group_finance','report.export'),('group_finance','audit.view'),
('entity_admin','dashboard.view'),('entity_admin','company.view'),('entity_admin','journal.view'),('entity_admin','journal.post'),('entity_admin','income.manage'),('entity_admin','arap.manage'),('entity_admin','bank.manage'),('entity_admin','bank.import'),('entity_admin','bank.reconcile'),('entity_admin','budget.manage'),('entity_admin','asset.manage'),('entity_admin','tax.manage'),('entity_admin','fx.manage'),('entity_admin','report.view'),('entity_admin','report.export'),('entity_admin','integration.manage'),
('auditor','dashboard.view'),('auditor','company.view'),('auditor','journal.view'),('auditor','report.view'),('auditor','report.export'),('auditor','audit.view'),
('viewer','dashboard.view'),('viewer','company.view'),('viewer','journal.view'),('viewer','report.view');

CREATE TABLE asset_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('acquisition','transfer','impairment','disposal') NOT NULL,
  event_date DATE NOT NULL,
  from_branch_id BIGINT UNSIGNED NULL,
  to_branch_id BIGINT UNSIGNED NULL,
  from_department_id BIGINT UNSIGNED NULL,
  to_department_id BIGINT UNSIGNED NULL,
  amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  journal_id BIGINT UNSIGNED NULL,
  note VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_asset_event(asset_id,event_date),
  CONSTRAINT fk_asset_event_asset FOREIGN KEY(asset_id) REFERENCES fixed_assets(id),
  CONSTRAINT fk_asset_event_from_branch FOREIGN KEY(from_branch_id) REFERENCES branches(id),
  CONSTRAINT fk_asset_event_to_branch FOREIGN KEY(to_branch_id) REFERENCES branches(id),
  CONSTRAINT fk_asset_event_from_department FOREIGN KEY(from_department_id) REFERENCES departments(id),
  CONSTRAINT fk_asset_event_to_department FOREIGN KEY(to_department_id) REFERENCES departments(id),
  CONSTRAINT fk_asset_event_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_asset_event_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO chart_accounts(company_id,code,name,account_type,is_cash_bank,is_active)
VALUES(NULL,'5401','Laba/Rugi Pelepasan & Penurunan Nilai Aset','expense',0,1);
