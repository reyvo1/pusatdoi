-- NEXA Group Finance R2 -> Enterprise R3
-- Apply once after v5_daily_income on MySQL 8.0+.

ALTER TABLE branches ADD COLUMN timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Makassar' AFTER name;

CREATE TABLE departments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(160) NOT NULL,
  cost_center_code VARCHAR(30) NULL,
  profit_center_code VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_department_company_code(company_id,code),
  CONSTRAINT fk_department_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_department_branch FOREIGN KEY(branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE journal_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  batch_no VARCHAR(50) NOT NULL,
  batch_date DATE NOT NULL,
  source_type VARCHAR(40) NOT NULL,
  status ENUM('draft','pending','approved','posted','rejected','cancelled') NOT NULL DEFAULT 'draft',
  total_debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  total_credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  posted_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_journal_batch_no(company_id,batch_no),
  CONSTRAINT fk_journal_batch_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_journal_batch_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_journal_batch_creator FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_journal_batch_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

ALTER TABLE journal_entries ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER branch_id;
ALTER TABLE journal_entries ADD COLUMN batch_id BIGINT UNSIGNED NULL AFTER department_id;
ALTER TABLE journal_entries ADD COLUMN external_ref VARCHAR(120) NULL AFTER source_ref;
ALTER TABLE journal_entries ADD CONSTRAINT fk_je_department FOREIGN KEY(department_id) REFERENCES departments(id);
ALTER TABLE journal_entries ADD CONSTRAINT fk_je_batch FOREIGN KEY(batch_id) REFERENCES journal_batches(id);
CREATE INDEX idx_journal_external ON journal_entries(company_id,external_ref);

ALTER TABLE income_categories ADD COLUMN tax_profile_id BIGINT UNSIGNED NULL AFTER revenue_account_id;
ALTER TABLE income_categories ADD CONSTRAINT fk_income_category_tax FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id);
ALTER TABLE bank_accounts ADD COLUMN currency CHAR(3) NOT NULL DEFAULT 'IDR' AFTER account_number;
ALTER TABLE fixed_assets ADD COLUMN depreciation_method ENUM('straight_line') NOT NULL DEFAULT 'straight_line' AFTER residual_value;
ALTER TABLE invoices ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER company_id;
ALTER TABLE invoices ADD COLUMN external_ref VARCHAR(120) NULL AFTER currency;
ALTER TABLE invoices ADD CONSTRAINT fk_invoice_branch FOREIGN KEY(branch_id) REFERENCES branches(id);

CREATE TABLE invoice_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  description VARCHAR(255) NOT NULL,
  quantity DECIMAL(18,4) NOT NULL DEFAULT 1,
  unit_price DECIMAL(20,2) NOT NULL,
  tax_profile_id BIGINT UNSIGNED NULL,
  tax_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  line_total DECIMAL(20,2) NOT NULL,
  CONSTRAINT fk_invoice_line_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_invoice_line_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_invoice_line_tax FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id)
) ENGINE=InnoDB;

CREATE TABLE payment_allocations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NOT NULL,
  allocated_amount DECIMAL(20,2) NOT NULL,
  allocated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_payment_invoice(payment_id,invoice_id),
  CONSTRAINT fk_alloc_payment FOREIGN KEY(payment_id) REFERENCES invoice_payments(id),
  CONSTRAINT fk_alloc_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_alloc_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE bank_statement_imports (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  file_name VARCHAR(255) NOT NULL,
  file_sha256 CHAR(64) NOT NULL,
  row_count INT UNSIGNED NOT NULL DEFAULT 0,
  imported_count INT UNSIGNED NOT NULL DEFAULT 0,
  skipped_count INT UNSIGNED NOT NULL DEFAULT 0,
  imported_by BIGINT UNSIGNED NULL,
  imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bank_import_hash(company_id,file_sha256),
  CONSTRAINT fk_bank_import_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_import_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id),
  CONSTRAINT fk_bank_import_user FOREIGN KEY(imported_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE bank_reconciliation_sessions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  period CHAR(7) NOT NULL,
  opening_balance DECIMAL(20,2) NOT NULL DEFAULT 0,
  closing_balance DECIMAL(20,2) NOT NULL DEFAULT 0,
  status ENUM('open','review','closed') NOT NULL DEFAULT 'open',
  prepared_by BIGINT UNSIGNED NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  closed_at DATETIME NULL,
  UNIQUE KEY uq_bank_recon_session(company_id,bank_account_id,period),
  CONSTRAINT fk_recon_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_recon_bank_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id),
  CONSTRAINT fk_recon_preparer FOREIGN KEY(prepared_by) REFERENCES users(id),
  CONSTRAINT fk_recon_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE bank_reconciliation_matches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id BIGINT UNSIGNED NOT NULL,
  bank_feed_id BIGINT UNSIGNED NOT NULL,
  journal_id BIGINT UNSIGNED NOT NULL,
  matched_amount DECIMAL(20,2) NOT NULL,
  match_type ENUM('manual','auto') NOT NULL DEFAULT 'manual',
  confidence DECIMAL(5,2) NULL,
  matched_by BIGINT UNSIGNED NULL,
  matched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bank_recon_feed(bank_feed_id),
  CONSTRAINT fk_recon_match_session FOREIGN KEY(session_id) REFERENCES bank_reconciliation_sessions(id),
  CONSTRAINT fk_recon_match_feed FOREIGN KEY(bank_feed_id) REFERENCES bank_feed(id),
  CONSTRAINT fk_recon_match_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_recon_match_user FOREIGN KEY(matched_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE asset_depreciation_schedule (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT UNSIGNED NOT NULL,
  period CHAR(7) NOT NULL,
  depreciation_amount DECIMAL(20,2) NOT NULL,
  accumulated_amount DECIMAL(20,2) NOT NULL,
  book_value DECIMAL(20,2) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  status ENUM('planned','posted','reversed') NOT NULL DEFAULT 'planned',
  UNIQUE KEY uq_asset_dep_period(asset_id,period),
  CONSTRAINT fk_asset_dep_asset FOREIGN KEY(asset_id) REFERENCES fixed_assets(id),
  CONSTRAINT fk_asset_dep_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE tax_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  tax_profile_id BIGINT UNSIGNED NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  transaction_date DATE NOT NULL,
  tax_base DECIMAL(20,2) NOT NULL,
  tax_amount DECIMAL(20,2) NOT NULL,
  direction ENUM('output','input','withholding') NOT NULL,
  external_ref VARCHAR(120) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_tax_tx_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_tax_tx_profile FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id),
  CONSTRAINT fk_tax_tx_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE fx_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_date DATE NOT NULL,
  base_currency CHAR(3) NOT NULL,
  quote_currency CHAR(3) NOT NULL,
  rate DECIMAL(24,8) NOT NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'manual',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fx_rate(rate_date,base_currency,quote_currency,source)
) ENGINE=InnoDB;

CREATE TABLE approval_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  document_type VARCHAR(50) NOT NULL,
  threshold_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  min_approvers TINYINT UNSIGNED NOT NULL DEFAULT 1,
  roles_json JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_approval_policy_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

ALTER TABLE journal_approvals ADD COLUMN document_type VARCHAR(50) NOT NULL DEFAULT 'journal' AFTER journal_date;
ALTER TABLE journal_approvals ADD COLUMN required_approvals TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER requested_by;

CREATE TABLE consolidation_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period CHAR(7) NOT NULL,
  run_no VARCHAR(50) NOT NULL,
  status ENUM('draft','validated','posted','locked') NOT NULL DEFAULT 'draft',
  entity_count INT UNSIGNED NOT NULL DEFAULT 0,
  elimination_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_by BIGINT UNSIGNED NULL,
  validated_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_consolidation_run(period,run_no),
  CONSTRAINT fk_consolidation_creator FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_consolidation_validator FOREIGN KEY(validated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE elimination_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  consolidation_run_id BIGINT UNSIGNED NOT NULL,
  from_company_id BIGINT UNSIGNED NOT NULL,
  to_company_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  reference VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  CONSTRAINT fk_elimination_run FOREIGN KEY(consolidation_run_id) REFERENCES consolidation_runs(id),
  CONSTRAINT fk_elimination_entry_from FOREIGN KEY(from_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elimination_entry_to FOREIGN KEY(to_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elimination_entry_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

CREATE TABLE integration_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source VARCHAR(80) NOT NULL,
  external_ref VARCHAR(150) NOT NULL,
  idempotency_key CHAR(64) NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  occurred_at DATETIME NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('received','validated','posted','failed','dead_letter') NOT NULL DEFAULT 'received',
  payload_json JSON NOT NULL,
  error_text TEXT NULL,
  UNIQUE KEY uq_integration_idempotency(idempotency_key),
  CONSTRAINT fk_integration_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE integration_outbox (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  event_type VARCHAR(80) NOT NULL,
  aggregate_type VARCHAR(80) NOT NULL,
  aggregate_id VARCHAR(80) NOT NULL,
  payload_json JSON NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  last_error TEXT NULL
) ENGINE=InnoDB;

CREATE TABLE settlement_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  settlement_date DATE NOT NULL,
  channel VARCHAR(60) NOT NULL,
  external_ref VARCHAR(120) NOT NULL,
  gross_amount DECIMAL(20,2) NOT NULL,
  fee_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  net_amount DECIMAL(20,2) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  status ENUM('pending','matched','posted','exception') NOT NULL DEFAULT 'pending',
  UNIQUE KEY uq_settlement_ref(company_id,channel,external_ref),
  CONSTRAINT fk_settlement_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_settlement_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_code VARCHAR(40) NOT NULL,
  permission_code VARCHAR(80) NOT NULL,
  PRIMARY KEY(role_code,permission_code)
) ENGINE=InnoDB;

-- Structural parity with fresh Enterprise R3 schema.
ALTER TABLE companies ADD COLUMN updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;
ALTER TABLE branches ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_active;
ALTER TABLE chart_accounts ADD COLUMN company_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(company_id,0)) STORED AFTER company_id;
ALTER TABLE chart_accounts ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_active;
ALTER TABLE chart_accounts ADD UNIQUE KEY uq_coa_company_code(company_scope,code);
ALTER TABLE users ADD COLUMN last_login_at DATETIME NULL AFTER is_active;
ALTER TABLE audit_logs ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER user_id;
ALTER TABLE audit_logs ADD COLUMN request_id VARCHAR(80) NULL AFTER payload_json;
ALTER TABLE audit_logs ADD KEY idx_audit_company(company_id,created_at);
ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_company FOREIGN KEY(company_id) REFERENCES companies(id);
ALTER TABLE bank_accounts ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER is_active;
ALTER TABLE budgets DROP INDEX uq_budget;
ALTER TABLE budgets ADD COLUMN branch_id BIGINT UNSIGNED NULL AFTER company_id;
ALTER TABLE budgets ADD COLUMN department_id BIGINT UNSIGNED NULL AFTER branch_id;
ALTER TABLE budgets ADD COLUMN branch_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(branch_id,0)) STORED AFTER department_id;
ALTER TABLE budgets ADD COLUMN department_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(department_id,0)) STORED AFTER branch_scope;
ALTER TABLE budgets ADD UNIQUE KEY uq_budget(company_id,branch_scope,department_scope,fiscal_year,period,account_id,scenario);
ALTER TABLE budgets ADD CONSTRAINT fk_budget_branch FOREIGN KEY(branch_id) REFERENCES branches(id);
ALTER TABLE budgets ADD CONSTRAINT fk_budget_department FOREIGN KEY(department_id) REFERENCES departments(id);
ALTER TABLE currency_rates ADD COLUMN source VARCHAR(80) NOT NULL DEFAULT 'manual' AFTER rate_to_idr;
ALTER TABLE currency_rates DROP PRIMARY KEY, ADD PRIMARY KEY(rate_date,currency_code,source);
