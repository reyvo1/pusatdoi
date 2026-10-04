CREATE DATABASE IF NOT EXISTS nexa_group_finance CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nexa_group_finance;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- R5 operational finance tables
DROP TABLE IF EXISTS year_end_closes;
DROP TABLE IF EXISTS cash_forecast_items;
DROP TABLE IF EXISTS recurring_journal_runs;
DROP TABLE IF EXISTS recurring_journal_lines;
DROP TABLE IF EXISTS recurring_journal_templates;
DROP TABLE IF EXISTS bank_transfers;
DROP TABLE IF EXISTS opening_balance_lines;
DROP TABLE IF EXISTS opening_balance_batches;
DROP TABLE IF EXISTS expense_categories;

DROP TABLE IF EXISTS integration_outbox;
DROP TABLE IF EXISTS integration_events;
DROP TABLE IF EXISTS integration_staging;
DROP TABLE IF EXISTS settlement_batches;
DROP TABLE IF EXISTS elimination_entries;
DROP TABLE IF EXISTS consolidation_runs;
DROP TABLE IF EXISTS intercompany_eliminations;
DROP TABLE IF EXISTS fx_events;
DROP TABLE IF EXISTS fx_rates;
DROP TABLE IF EXISTS currency_rates;
DROP TABLE IF EXISTS tax_transactions;
DROP TABLE IF EXISTS invoice_payments;
DROP TABLE IF EXISTS payment_allocations;
DROP TABLE IF EXISTS invoice_lines;
DROP TABLE IF EXISTS invoices;
DROP TABLE IF EXISTS bank_reconciliation_matches;
DROP TABLE IF EXISTS bank_reconciliation_sessions;
DROP TABLE IF EXISTS bank_statement_imports;
DROP TABLE IF EXISTS bank_feed;
DROP TABLE IF EXISTS journal_approvals;
DROP TABLE IF EXISTS approval_policies;
DROP TABLE IF EXISTS asset_depreciation_schedule;
DROP TABLE IF EXISTS fixed_assets;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS income_categories;
DROP TABLE IF EXISTS journal_lines;
DROP TABLE IF EXISTS journal_entries;
DROP TABLE IF EXISTS journal_batches;
DROP TABLE IF EXISTS fiscal_periods;
DROP TABLE IF EXISTS bank_accounts;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS chart_accounts;
DROP TABLE IF EXISTS tax_profiles;
DROP TABLE IF EXISTS departments;
DROP TABLE IF EXISTS branches;
DROP TABLE IF EXISTS companies;

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE companies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL,
  name VARCHAR(160) NOT NULL,
  legal_name VARCHAR(180) NULL,
  business_type VARCHAR(80) NOT NULL,
  tax_id VARCHAR(64) NULL,
  base_currency CHAR(3) NOT NULL DEFAULT 'IDR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_company_code(code)
) ENGINE=InnoDB;

CREATE TABLE branches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(160) NOT NULL,
  timezone VARCHAR(64) NOT NULL DEFAULT 'Asia/Makassar',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_branch_company_code(company_id,code),
  KEY idx_branch_company(company_id,is_active),
  CONSTRAINT fk_branch_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

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
  KEY idx_department_branch(branch_id),
  CONSTRAINT fk_department_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_department_branch FOREIGN KEY(branch_id) REFERENCES branches(id)
) ENGINE=InnoDB;

CREATE TABLE chart_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  company_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(company_id,0)) STORED,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(160) NOT NULL,
  account_type ENUM('asset','liability','equity','revenue','expense') NOT NULL,
  parent_id BIGINT UNSIGNED NULL,
  is_cash_bank TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_coa_company_code(company_scope,code),
  KEY idx_coa_type(account_type,is_active),
  CONSTRAINT fk_coa_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_coa_parent FOREIGN KEY(parent_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('group_owner','group_finance','entity_admin','auditor','viewer') NOT NULL,
  company_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_email(email),
  KEY idx_user_company(company_id,role,is_active),
  CONSTRAINT fk_user_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_code VARCHAR(40) NOT NULL,
  permission_code VARCHAR(80) NOT NULL,
  PRIMARY KEY(role_code,permission_code)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  KEY idx_login_attempt(identifier_hash,ip_address,attempted_at,success)
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  company_id BIGINT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NULL,
  payload_json JSON NULL,
  request_id VARCHAR(80) NULL,
  ip_address VARCHAR(64) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_created(created_at),
  KEY idx_audit_company(company_id,created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id),
  CONSTRAINT fk_audit_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE fiscal_periods (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  fiscal_year SMALLINT NOT NULL,
  period TINYINT UNSIGNED NOT NULL,
  status ENUM('open','soft_closed','closed') NOT NULL DEFAULT 'open',
  closed_at DATETIME NULL,
  closed_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_fiscal_period_company(company_id,fiscal_year,period),
  KEY idx_period_status(fiscal_year,period,status),
  KEY idx_period_company(company_id,fiscal_year,period,status),
  CONSTRAINT fk_period_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_period_user FOREIGN KEY(closed_by) REFERENCES users(id),
  CONSTRAINT chk_period_number CHECK(period BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE approval_policies (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  document_type VARCHAR(50) NOT NULL,
  level_no TINYINT UNSIGNED NOT NULL DEFAULT 1,
  threshold_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  max_amount DECIMAL(20,2) NULL,
  min_approvers TINYINT UNSIGNED NOT NULL DEFAULT 1,
  require_distinct_approvers TINYINT(1) NOT NULL DEFAULT 1,
  roles_json JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_approval_policy(company_id,document_type,is_active),
  CONSTRAINT fk_approval_policy_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT chk_approval_policy_amounts CHECK(max_amount IS NULL OR max_amount >= threshold_amount)
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
  KEY idx_journal_batch_status(company_id,status,batch_date),
  CONSTRAINT fk_journal_batch_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_journal_batch_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_journal_batch_creator FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_journal_batch_approver FOREIGN KEY(approved_by) REFERENCES users(id),
  CONSTRAINT chk_batch_totals CHECK(total_debit >= 0 AND total_credit >= 0)
) ENGINE=InnoDB;

CREATE TABLE journal_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  batch_id BIGINT UNSIGNED NULL,
  journal_no VARCHAR(50) NOT NULL,
  journal_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  source_type VARCHAR(40) NOT NULL DEFAULT 'manual',
  source_ref VARCHAR(100) NULL,
  external_ref VARCHAR(120) NULL,
  status ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',
  posted_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  reversal_of BIGINT UNSIGNED NULL,
  reversed_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_journal_no(journal_no),
  KEY idx_journal_scope(company_id,journal_date,status),
  KEY idx_journal_external(company_id,external_ref),
  CONSTRAINT fk_je_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_je_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_je_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_je_batch FOREIGN KEY(batch_id) REFERENCES journal_batches(id),
  CONSTRAINT fk_je_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_je_reversal_of FOREIGN KEY(reversal_of) REFERENCES journal_entries(id),
  CONSTRAINT fk_je_reversed_by FOREIGN KEY(reversed_by) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE journal_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  journal_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  cost_center VARCHAR(80) NULL,
  profit_center VARCHAR(80) NULL,
  project_code VARCHAR(80) NULL,
  transaction_currency CHAR(3) NOT NULL DEFAULT 'IDR',
  foreign_amount DECIMAL(24,6) NULL,
  exchange_rate DECIMAL(24,8) NOT NULL DEFAULT 1,
  base_amount DECIMAL(20,2) NULL,
  intercompany_company_id BIGINT UNSIGNED NULL,
  KEY idx_jl_account(account_id),
  KEY idx_jl_intercompany(intercompany_company_id),
  KEY idx_jl_dimensions(branch_id,department_id,cost_center,profit_center),
  KEY idx_jl_currency(transaction_currency),
  CONSTRAINT fk_jl_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE,
  CONSTRAINT fk_jl_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_jl_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_jl_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_jl_intercompany FOREIGN KEY(intercompany_company_id) REFERENCES companies(id),
  CONSTRAINT chk_jl_nonnegative CHECK(debit >= 0 AND credit >= 0),
  CONSTRAINT chk_jl_one_side CHECK(NOT(debit > 0 AND credit > 0)),
  CONSTRAINT chk_jl_positive CHECK(debit > 0 OR credit > 0),
  CONSTRAINT chk_jl_exchange_rate CHECK(exchange_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE journal_approvals (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  journal_id BIGINT UNSIGNED NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  journal_date DATE NOT NULL,
  document_type VARCHAR(50) NOT NULL DEFAULT 'journal',
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  requested_by BIGINT UNSIGNED NOT NULL,
  required_approvals TINYINT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  decided_by BIGINT UNSIGNED NULL,
  payload_json JSON NULL,
  KEY idx_approval_status(status,requested_at),
  CONSTRAINT fk_approval_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_approval_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_approval_requester FOREIGN KEY(requested_by) REFERENCES users(id),
  CONSTRAINT fk_approval_decider FOREIGN KEY(decided_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE tax_profiles (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  rate DECIMAL(9,4) NOT NULL DEFAULT 0,
  tax_type ENUM('input','output','withholding','none') NOT NULL DEFAULT 'none',
  input_account_id BIGINT UNSIGNED NULL,
  output_account_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_tax_profile_code(code),
  CONSTRAINT fk_tax_input_account FOREIGN KEY(input_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_tax_output_account FOREIGN KEY(output_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT chk_tax_rate CHECK(rate >= 0 AND rate <= 100)
) ENGINE=InnoDB;

CREATE TABLE income_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  revenue_account_id BIGINT UNSIGNED NOT NULL,
  tax_profile_id BIGINT UNSIGNED NULL,
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_income_category(company_id,code),
  KEY idx_income_category_company(company_id,is_active,sort_order),
  CONSTRAINT fk_income_category_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_income_category_account FOREIGN KEY(revenue_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_income_category_tax FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id)
) ENGINE=InnoDB;

CREATE TABLE bank_accounts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  bank_name VARCHAR(100) NOT NULL,
  account_name VARCHAR(120) NOT NULL,
  account_number VARCHAR(120) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bank_company(company_id,is_active),
  CONSTRAINT fk_bank_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_coa FOREIGN KEY(account_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

CREATE TABLE budgets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  branch_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(branch_id,0)) STORED,
  department_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(department_id,0)) STORED,
  fiscal_year SMALLINT NOT NULL,
  period TINYINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  scenario VARCHAR(40) NOT NULL DEFAULT 'budget',
  KEY idx_budget_company(company_id),
  UNIQUE KEY uq_budget(company_id,branch_scope,department_scope,fiscal_year,period,account_id,scenario),
  CONSTRAINT fk_budget_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_budget_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_budget_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_budget_coa FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT chk_budget_period CHECK(period BETWEEN 1 AND 12)
) ENGINE=InnoDB;

CREATE TABLE fixed_assets (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  asset_code VARCHAR(40) NOT NULL,
  name VARCHAR(160) NOT NULL,
  category VARCHAR(80) NOT NULL,
  acquisition_date DATE NOT NULL,
  acquisition_cost DECIMAL(20,2) NOT NULL,
  useful_life_months INT NOT NULL,
  residual_value DECIMAL(20,2) NOT NULL DEFAULT 0,
  depreciation_method ENUM('straight_line') NOT NULL DEFAULT 'straight_line',
  asset_account_id BIGINT UNSIGNED NULL,
  accumulated_depreciation_account_id BIGINT UNSIGNED NULL,
  depreciation_expense_account_id BIGINT UNSIGNED NULL,
  acquisition_journal_id BIGINT UNSIGNED NULL,
  status ENUM('active','disposed','sold') NOT NULL DEFAULT 'active',
  disposal_date DATE NULL,
  disposal_journal_id BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_asset_code(asset_code),
  KEY idx_asset_company(company_id,status),
  CONSTRAINT fk_asset_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_asset_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_asset_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_asset_account FOREIGN KEY(asset_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_asset_accum_account FOREIGN KEY(accumulated_depreciation_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_asset_dep_expense_account FOREIGN KEY(depreciation_expense_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_asset_acq_journal FOREIGN KEY(acquisition_journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_asset_disposal_journal FOREIGN KEY(disposal_journal_id) REFERENCES journal_entries(id),
  CONSTRAINT chk_asset_life CHECK(useful_life_months > 0),
  CONSTRAINT chk_asset_values CHECK(acquisition_cost >= 0 AND residual_value >= 0 AND residual_value <= acquisition_cost)
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

-- Party master must exist before invoices because invoices.party_id has a strict FK.
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

CREATE TABLE invoices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  party_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  invoice_type ENUM('receivable','payable') NOT NULL,
  invoice_no VARCHAR(60) NOT NULL,
  party_name VARCHAR(180) NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  subtotal DECIMAL(20,2) NOT NULL DEFAULT 0,
  tax_total DECIMAL(20,2) NOT NULL DEFAULT 0,
  amount DECIMAL(20,2) NOT NULL,
  paid_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  status ENUM('open','partial','paid','void') NOT NULL DEFAULT 'open',
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  external_ref VARCHAR(120) NULL,
  journal_id BIGINT UNSIGNED NULL,
  notes TEXT NULL,
  UNIQUE KEY uq_invoice(company_id,invoice_no),
  KEY idx_invoice_due(company_id,invoice_type,due_date,status),
  CONSTRAINT fk_invoice_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_invoice_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_invoice_party FOREIGN KEY(party_id) REFERENCES parties(id),
  CONSTRAINT fk_invoice_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_invoice_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT chk_invoice_amount CHECK(amount > 0),
  CONSTRAINT chk_invoice_paid CHECK(paid_amount >= 0 AND paid_amount <= amount)
) ENGINE=InnoDB;

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
  KEY idx_invoice_lines_invoice(invoice_id),
  CONSTRAINT fk_invoice_line_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
  CONSTRAINT fk_invoice_line_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_invoice_line_tax FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id),
  CONSTRAINT chk_invoice_line_quantity CHECK(quantity > 0),
  CONSTRAINT chk_invoice_line_values CHECK(unit_price >= 0 AND tax_amount >= 0 AND line_total >= 0)
) ENGINE=InnoDB;

CREATE TABLE invoice_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  foreign_amount DECIMAL(24,6) NULL,
  exchange_rate DECIMAL(24,8) NOT NULL DEFAULT 1,
  method VARCHAR(40) NOT NULL DEFAULT 'bank',
  reference_no VARCHAR(120) NULL,
  journal_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_payment_invoice(invoice_id,payment_date),
  CONSTRAINT fk_payment_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_payment_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_payment_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_payment_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_payment_amount CHECK(amount > 0),
  CONSTRAINT chk_payment_exchange_rate CHECK(exchange_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE payment_allocations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  payment_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NOT NULL,
  allocated_amount DECIMAL(20,2) NOT NULL,
  allocated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_payment_invoice(payment_id,invoice_id),
  CONSTRAINT fk_alloc_payment FOREIGN KEY(payment_id) REFERENCES invoice_payments(id),
  CONSTRAINT fk_alloc_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_alloc_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_alloc_amount CHECK(allocated_amount > 0)
) ENGINE=InnoDB;

CREATE TABLE bank_feed (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  txn_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  direction ENUM('in','out') NOT NULL,
  status ENUM('unmatched','matched','ignored') NOT NULL DEFAULT 'unmatched',
  journal_id BIGINT UNSIGNED NULL,
  external_ref VARCHAR(120) NOT NULL,
  imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bank_feed_external(company_id,external_ref),
  KEY idx_bank_feed_match(company_id,status,txn_date),
  CONSTRAINT fk_bank_feed_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_feed_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id),
  CONSTRAINT fk_bank_feed_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT chk_bank_amount CHECK(amount > 0)
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
  period_start DATE NULL,
  period_end DATE NULL,
  opening_balance DECIMAL(20,2) NOT NULL DEFAULT 0,
  closing_balance DECIMAL(20,2) NOT NULL DEFAULT 0,
  matched_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  difference_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  status ENUM('open','review','closed') NOT NULL DEFAULT 'open',
  prepared_by BIGINT UNSIGNED NULL,
  reviewed_by BIGINT UNSIGNED NULL,
  closed_at DATETIME NULL,
  closed_by BIGINT UNSIGNED NULL,
  reopened_at DATETIME NULL,
  reopened_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_bank_recon_session(company_id,bank_account_id,period),
  KEY idx_bank_recon_period(company_id,period_start,period_end,status),
  CONSTRAINT fk_recon_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_recon_bank_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id),
  CONSTRAINT fk_recon_preparer FOREIGN KEY(prepared_by) REFERENCES users(id),
  CONSTRAINT fk_recon_reviewer FOREIGN KEY(reviewed_by) REFERENCES users(id),
  CONSTRAINT fk_recon_closed_by FOREIGN KEY(closed_by) REFERENCES users(id),
  CONSTRAINT fk_recon_reopened_by FOREIGN KEY(reopened_by) REFERENCES users(id),
  CONSTRAINT chk_bank_recon_period CHECK(period_start IS NULL OR period_end IS NULL OR period_start <= period_end)
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
  CONSTRAINT fk_recon_match_user FOREIGN KEY(matched_by) REFERENCES users(id),
  CONSTRAINT chk_recon_match_amount CHECK(matched_amount > 0)
) ENGINE=InnoDB;

CREATE TABLE currency_rates (
  rate_date DATE NOT NULL,
  currency_code CHAR(3) NOT NULL,
  rate_to_idr DECIMAL(24,8) NOT NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'manual',
  PRIMARY KEY(rate_date,currency_code,source),
  CONSTRAINT chk_currency_rate CHECK(rate_to_idr > 0)
) ENGINE=InnoDB;

CREATE TABLE fx_rates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_date DATE NOT NULL,
  base_currency CHAR(3) NOT NULL,
  quote_currency CHAR(3) NOT NULL,
  rate DECIMAL(24,8) NOT NULL,
  source VARCHAR(80) NOT NULL DEFAULT 'manual',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_fx_rate(rate_date,base_currency,quote_currency,source),
  CONSTRAINT chk_fx_rate CHECK(rate > 0)
) ENGINE=InnoDB;

CREATE TABLE fx_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  currency CHAR(3) NOT NULL,
  foreign_amount DECIMAL(20,6) NOT NULL,
  book_rate DECIMAL(20,6) NOT NULL,
  settlement_rate DECIMAL(20,6) NOT NULL,
  base_amount DECIMAL(20,2) NOT NULL,
  gain_loss DECIMAL(20,2) NOT NULL,
  description VARCHAR(255) NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_fx_company_date(company_id,event_date),
  CONSTRAINT fk_fx_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_fx_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_fx_values CHECK(foreign_amount > 0 AND book_rate > 0 AND settlement_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE tax_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  tax_profile_id BIGINT UNSIGNED NOT NULL,
  tax_code VARCHAR(40) NULL,
  journal_id BIGINT UNSIGNED NULL,
  transaction_date DATE NOT NULL,
  filing_period CHAR(7) NULL,
  tax_base DECIMAL(20,2) NOT NULL,
  tax_amount DECIMAL(20,2) NOT NULL,
  direction ENUM('output','input','withholding') NOT NULL,
  external_ref VARCHAR(120) NULL,
  status ENUM('open','reported','paid','void') NOT NULL DEFAULT 'open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tax_company_period(company_id,transaction_date,direction),
  KEY idx_tax_filing(company_id,filing_period,status),
  CONSTRAINT fk_tax_tx_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_tax_tx_profile FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id),
  CONSTRAINT fk_tax_tx_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT chk_tax_tx_values CHECK(tax_base >= 0 AND tax_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE intercompany_eliminations (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period_key CHAR(7) NOT NULL,
  from_company_id BIGINT UNSIGNED NOT NULL,
  to_company_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'posted',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_elim_period(period_key,status),
  CONSTRAINT fk_elim_from FOREIGN KEY(from_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elim_to FOREIGN KEY(to_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elim_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_elimination_amount CHECK(amount > 0),
  CONSTRAINT chk_elimination_entities CHECK(from_company_id <> to_company_id)
) ENGINE=InnoDB;

CREATE TABLE consolidation_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period CHAR(7) NOT NULL,
  run_no VARCHAR(50) NOT NULL,
  status ENUM('draft','validated','posted','locked') NOT NULL DEFAULT 'draft',
  entity_count INT UNSIGNED NOT NULL DEFAULT 0,
  elimination_count INT UNSIGNED NOT NULL DEFAULT 0,
  scope_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  validated_by BIGINT UNSIGNED NULL,
  posted_at DATETIME NULL,
  locked_at DATETIME NULL,
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
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  reference VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  KEY idx_elimination_run(consolidation_run_id),
  CONSTRAINT fk_elimination_run FOREIGN KEY(consolidation_run_id) REFERENCES consolidation_runs(id),
  CONSTRAINT fk_elimination_entry_from FOREIGN KEY(from_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elimination_entry_to FOREIGN KEY(to_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elimination_entry_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_elimination_entry_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_elimination_entry_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT chk_elimination_line CHECK((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))
) ENGINE=InnoDB;

CREATE TABLE integration_staging (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source VARCHAR(80) NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  external_ref VARCHAR(160) NOT NULL,
  status ENUM('received','validated','posted','failed') NOT NULL DEFAULT 'received',
  amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  payload_json JSON NULL,
  error_message VARCHAR(500) NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_staging_source_ref(source,external_ref),
  KEY idx_staging_status(status,received_at),
  CONSTRAINT fk_staging_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE integration_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  source VARCHAR(80) NOT NULL,
  external_ref VARCHAR(150) NOT NULL,
  idempotency_key CHAR(64) NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  event_type VARCHAR(80) NOT NULL,
  event_version VARCHAR(20) NOT NULL DEFAULT '1',
  mapping_version VARCHAR(40) NULL,
  occurred_at DATETIME NULL,
  received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('received','validated','posted','failed','dead_letter') NOT NULL DEFAULT 'received',
  payload_json JSON NOT NULL,
  error_text TEXT NULL,
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  next_attempt_at DATETIME NULL,
  posted_journal_id BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_integration_idempotency(idempotency_key),
  KEY idx_integration_status(source,status,received_at),
  CONSTRAINT fk_integration_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_integration_posted_journal FOREIGN KEY(posted_journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE integration_outbox (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NULL,
  event_type VARCHAR(80) NOT NULL,
  aggregate_type VARCHAR(80) NOT NULL,
  aggregate_id VARCHAR(80) NOT NULL,
  idempotency_key CHAR(64) NULL,
  payload_json JSON NOT NULL,
  status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
  available_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  sent_at DATETIME NULL,
  last_error TEXT NULL,
  UNIQUE KEY uq_outbox_idempotency(idempotency_key),
  KEY idx_outbox_delivery(status,available_at),
  CONSTRAINT fk_outbox_company FOREIGN KEY(company_id) REFERENCES companies(id)
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
  CONSTRAINT fk_settlement_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT chk_settlement_values CHECK(gross_amount >= 0 AND fee_amount >= 0 AND net_amount >= 0 AND gross_amount - fee_amount = net_amount)
) ENGINE=InnoDB;


-- ===== R4 DELTA =====
-- NEXA Group Finance Enterprise R4
-- Adds end-to-end dimensions, counterparties, multicurrency, closing controls,
-- integration delivery controls, real reconciliation/import metadata, and report snapshots.







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


-- R5 finance operations
-- NEXA Enterprise R5: daily expense, opening balance, treasury, recurring, forecast, year-end close

CREATE TABLE expense_categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(40) NOT NULL,
  name VARCHAR(160) NOT NULL,
  expense_account_id BIGINT UNSIGNED NOT NULL,
  tax_profile_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_expense_category(company_id,code),
  CONSTRAINT fk_expense_category_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_expense_category_account FOREIGN KEY(expense_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_expense_category_tax FOREIGN KEY(tax_profile_id) REFERENCES tax_profiles(id)
) ENGINE=InnoDB;

CREATE TABLE opening_balance_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  opening_date DATE NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  total_debit DECIMAL(20,2) NOT NULL,
  total_credit DECIMAL(20,2) NOT NULL,
  status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  posted_at DATETIME NULL,
  UNIQUE KEY uq_opening_balance_year(company_id,fiscal_year),
  CONSTRAINT fk_opening_balance_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_opening_balance_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_opening_balance_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE opening_balance_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  CONSTRAINT fk_opening_line_batch FOREIGN KEY(batch_id) REFERENCES opening_balance_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_opening_line_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT chk_opening_nonnegative CHECK(debit >= 0 AND credit >= 0),
  CONSTRAINT chk_opening_one_side CHECK(NOT(debit > 0 AND credit > 0)),
  CONSTRAINT chk_opening_positive CHECK(debit > 0 OR credit > 0)
) ENGINE=InnoDB;

CREATE TABLE bank_transfers (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  transfer_date DATE NOT NULL,
  from_account_id BIGINT UNSIGNED NOT NULL,
  to_account_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  fee_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  fee_account_id BIGINT UNSIGNED NULL,
  reference VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  status ENUM('posted','reversed') NOT NULL DEFAULT 'posted',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_bank_transfer_company_date(company_id,transfer_date),
  CONSTRAINT fk_bank_transfer_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_transfer_from FOREIGN KEY(from_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_bank_transfer_to FOREIGN KEY(to_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_bank_transfer_fee FOREIGN KEY(fee_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_bank_transfer_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_bank_transfer_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_bank_transfer_amount CHECK(amount > 0 AND fee_amount >= 0),
  CONSTRAINT chk_bank_transfer_diff CHECK(from_account_id <> to_account_id)
) ENGINE=InnoDB;

CREATE TABLE recurring_journal_templates (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  name VARCHAR(160) NOT NULL,
  description VARCHAR(255) NOT NULL,
  frequency ENUM('weekly','monthly','quarterly','yearly') NOT NULL,
  next_run_date DATE NOT NULL,
  auto_reverse TINYINT(1) NOT NULL DEFAULT 0,
  reverse_after_days SMALLINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  last_run_at DATETIME NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_recurring_name(company_id,name),
  KEY idx_recurring_due(is_active,next_run_date),
  CONSTRAINT fk_recurring_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_recurring_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_recurring_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_recurring_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE recurring_journal_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  debit DECIMAL(20,2) NOT NULL DEFAULT 0,
  credit DECIMAL(20,2) NOT NULL DEFAULT 0,
  description VARCHAR(255) NULL,
  CONSTRAINT fk_recurring_line_template FOREIGN KEY(template_id) REFERENCES recurring_journal_templates(id) ON DELETE CASCADE,
  CONSTRAINT fk_recurring_line_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT chk_recurring_nonnegative CHECK(debit >= 0 AND credit >= 0),
  CONSTRAINT chk_recurring_one_side CHECK(NOT(debit > 0 AND credit > 0)),
  CONSTRAINT chk_recurring_positive CHECK(debit > 0 OR credit > 0)
) ENGINE=InnoDB;

CREATE TABLE recurring_journal_runs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  template_id BIGINT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  reversal_journal_id BIGINT UNSIGNED NULL,
  reversal_due_date DATE NULL,
  reversal_status ENUM('none','pending','posted','failed') NOT NULL DEFAULT 'none',
  status ENUM('posted','approval','failed','reversed') NOT NULL,
  error_text VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_recurring_run(template_id,scheduled_date),
  KEY idx_recurring_reversal(reversal_status,reversal_due_date),
  CONSTRAINT fk_recurring_run_template FOREIGN KEY(template_id) REFERENCES recurring_journal_templates(id),
  CONSTRAINT fk_recurring_run_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_recurring_run_reversal FOREIGN KEY(reversal_journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE cash_forecast_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  forecast_date DATE NOT NULL,
  direction ENUM('inflow','outflow') NOT NULL,
  source_type VARCHAR(50) NOT NULL DEFAULT 'manual',
  source_ref VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  probability_pct DECIMAL(5,2) NOT NULL DEFAULT 100,
  status ENUM('planned','realized','cancelled') NOT NULL DEFAULT 'planned',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cash_forecast_scope(company_id,forecast_date,status),
  CONSTRAINT fk_cash_forecast_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_cash_forecast_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_cash_forecast_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_cash_forecast_amount CHECK(amount > 0),
  CONSTRAINT chk_cash_forecast_probability CHECK(probability_pct >= 0 AND probability_pct <= 100)
) ENGINE=InnoDB;

CREATE TABLE year_end_closes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  fiscal_year SMALLINT UNSIGNED NOT NULL,
  close_date DATE NOT NULL,
  profit_loss_amount DECIMAL(20,2) NOT NULL,
  retained_earnings_account_id BIGINT UNSIGNED NOT NULL,
  journal_id BIGINT UNSIGNED NOT NULL,
  status ENUM('posted','reversed') NOT NULL DEFAULT 'posted',
  closed_by BIGINT UNSIGNED NULL,
  closed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_year_end_company(company_id,fiscal_year),
  CONSTRAINT fk_year_end_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_year_end_retained FOREIGN KEY(retained_earnings_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_year_end_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_year_end_user FOREIGN KEY(closed_by) REFERENCES users(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO role_permissions(role_code,permission_code) VALUES
('group_owner','expense.manage'),('group_owner','treasury.manage'),('group_owner','onboarding.manage'),('group_owner','recurring.manage'),('group_owner','forecast.manage'),('group_owner','year_end.close'),
('group_finance','expense.manage'),('group_finance','treasury.manage'),('group_finance','recurring.manage'),('group_finance','forecast.manage'),('group_finance','year_end.close'),
('entity_admin','expense.manage'),('entity_admin','treasury.manage'),('entity_admin','recurring.manage'),('entity_admin','forecast.manage');

INSERT IGNORE INTO chart_accounts(company_id,code,name,account_type,is_cash_bank,is_active) VALUES
(NULL,'5501','Biaya Administrasi Bank','expense',0,1),
(NULL,'3102','Saldo Laba Ditahan','equity',0,1),
(NULL,'1161','Beban Dibayar Dimuka','asset',0,1),
(NULL,'2201','Pendapatan Diterima Dimuka','liability',0,1);

-- NEXA Enterprise R6: bulk onboarding, AP payment batches, document evidence

CREATE TABLE onboarding_import_jobs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  import_type ENUM('coa','parties','opening_balance') NOT NULL,
  original_name VARCHAR(255) NULL,
  file_sha256 CHAR(64) NOT NULL,
  rows_total INT UNSIGNED NOT NULL DEFAULT 0,
  rows_success INT UNSIGNED NOT NULL DEFAULT 0,
  rows_failed INT UNSIGNED NOT NULL DEFAULT 0,
  status ENUM('processing','completed','completed_with_errors','failed') NOT NULL DEFAULT 'processing',
  summary_json JSON NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_import_company(company_id,created_at),
  CONSTRAINT fk_import_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_import_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE onboarding_import_errors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  job_id BIGINT UNSIGNED NOT NULL,
  row_no INT UNSIGNED NOT NULL,
  error_text VARCHAR(1000) NOT NULL,
  raw_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_import_error_job(job_id,row_no),
  CONSTRAINT fk_import_error_job FOREIGN KEY(job_id) REFERENCES onboarding_import_jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ap_payment_batches (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  payment_account_id BIGINT UNSIGNED NOT NULL,
  scheduled_date DATE NOT NULL,
  batch_no VARCHAR(60) NOT NULL,
  reference_no VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  total_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  status ENUM('planned','approval','posted','cancelled') NOT NULL DEFAULT 'planned',
  journal_id BIGINT UNSIGNED NULL,
  approval_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  posted_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  posted_at DATETIME NULL,
  UNIQUE KEY uq_ap_payment_batch_no(company_id,batch_no),
  KEY idx_ap_payment_batch_status(company_id,status,scheduled_date),
  CONSTRAINT fk_ap_batch_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_ap_batch_payment_account FOREIGN KEY(payment_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_ap_batch_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_ap_batch_approval FOREIGN KEY(approval_id) REFERENCES journal_approvals(id),
  CONSTRAINT fk_ap_batch_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_ap_batch_posted_by FOREIGN KEY(posted_by) REFERENCES users(id),
  CONSTRAINT chk_ap_batch_amount CHECK(total_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE ap_payment_batch_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  batch_id BIGINT UNSIGNED NOT NULL,
  invoice_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ap_batch_invoice(batch_id,invoice_id),
  KEY idx_ap_batch_line_invoice(invoice_id),
  CONSTRAINT fk_ap_batch_line_batch FOREIGN KEY(batch_id) REFERENCES ap_payment_batches(id) ON DELETE CASCADE,
  CONSTRAINT fk_ap_batch_line_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT chk_ap_batch_line_amount CHECK(amount > 0)
) ENGINE=InnoDB;

CREATE TABLE document_attachments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  entity_type ENUM('journal','invoice','expense','asset','payment_batch','other') NOT NULL,
  entity_id BIGINT UNSIGNED NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(190) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  file_size BIGINT UNSIGNED NOT NULL,
  sha256 CHAR(64) NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  uploaded_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_document_storage(stored_name),
  KEY idx_document_entity(company_id,entity_type,entity_id,is_active),
  KEY idx_document_sha(company_id,sha256),
  CONSTRAINT fk_document_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_document_user FOREIGN KEY(uploaded_by) REFERENCES users(id),
  CONSTRAINT chk_document_size CHECK(file_size > 0)
) ENGINE=InnoDB;

INSERT IGNORE INTO role_permissions(role_code,permission_code) VALUES
('group_owner','import.manage'),('group_owner','document.manage'),('group_owner','payment_batch.manage'),
('group_finance','import.manage'),('group_finance','document.manage'),('group_finance','payment_batch.manage'),
('entity_admin','import.manage'),('entity_admin','document.manage'),('entity_admin','payment_batch.manage'),
('auditor','document.view');
-- NEXA Enterprise R7: employee advances, financing, equity, budget scenarios, notifications

CREATE TABLE employee_advances (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  employee_name VARCHAR(160) NOT NULL,
  advance_date DATE NOT NULL,
  due_date DATE NULL,
  amount DECIMAL(20,2) NOT NULL,
  outstanding_amount DECIMAL(20,2) NOT NULL,
  advance_account_id BIGINT UNSIGNED NOT NULL,
  cash_account_id BIGINT UNSIGNED NOT NULL,
  issue_journal_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  status ENUM('open','partial','settled','cancelled') NOT NULL DEFAULT 'open',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_advance_company(company_id,status,due_date),
  CONSTRAINT fk_advance_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_advance_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_advance_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_advance_account FOREIGN KEY(advance_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_advance_cash FOREIGN KEY(cash_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_advance_journal FOREIGN KEY(issue_journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_advance_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_advance_amount CHECK(amount > 0 AND outstanding_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE employee_advance_settlements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  advance_id BIGINT UNSIGNED NOT NULL,
  settlement_date DATE NOT NULL,
  expense_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  refund_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  reimbursement_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  expense_account_id BIGINT UNSIGNED NOT NULL,
  cash_account_id BIGINT UNSIGNED NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_advance_settlement(advance_id,settlement_date),
  CONSTRAINT fk_advance_settlement_parent FOREIGN KEY(advance_id) REFERENCES employee_advances(id),
  CONSTRAINT fk_advance_settlement_expense FOREIGN KEY(expense_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_advance_settlement_cash FOREIGN KEY(cash_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_advance_settlement_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_advance_settlement_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_advance_settlement_values CHECK(expense_amount >= 0 AND refund_amount >= 0 AND reimbursement_amount >= 0)
) ENGINE=InnoDB;

CREATE TABLE loan_facilities (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  lender_name VARCHAR(180) NOT NULL,
  reference_no VARCHAR(120) NULL,
  start_date DATE NOT NULL,
  maturity_date DATE NOT NULL,
  principal DECIMAL(20,2) NOT NULL,
  outstanding_principal DECIMAL(20,2) NOT NULL,
  annual_interest_rate DECIMAL(9,4) NOT NULL DEFAULT 0,
  liability_account_id BIGINT UNSIGNED NOT NULL,
  cash_account_id BIGINT UNSIGNED NOT NULL,
  interest_expense_account_id BIGINT UNSIGNED NOT NULL,
  disbursement_journal_id BIGINT UNSIGNED NULL,
  status ENUM('active','paid','restructured','cancelled') NOT NULL DEFAULT 'active',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_loan_company(company_id,status,maturity_date),
  CONSTRAINT fk_loan_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_loan_liability FOREIGN KEY(liability_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_loan_cash FOREIGN KEY(cash_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_loan_interest FOREIGN KEY(interest_expense_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_loan_journal FOREIGN KEY(disbursement_journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_loan_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_loan_values CHECK(principal > 0 AND outstanding_principal >= 0 AND annual_interest_rate >= 0)
) ENGINE=InnoDB;

CREATE TABLE loan_payments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id BIGINT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  principal_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  interest_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  cash_account_id BIGINT UNSIGNED NOT NULL,
  reference_no VARCHAR(120) NULL,
  journal_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_loan_payment(loan_id,payment_date),
  CONSTRAINT fk_loan_payment_parent FOREIGN KEY(loan_id) REFERENCES loan_facilities(id),
  CONSTRAINT fk_loan_payment_cash FOREIGN KEY(cash_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_loan_payment_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_loan_payment_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_loan_payment_values CHECK(principal_amount >= 0 AND interest_amount >= 0 AND (principal_amount + interest_amount) > 0)
) ENGINE=InnoDB;

CREATE TABLE equity_transactions (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  transaction_type ENUM('capital_contribution','dividend_payment','owner_draw') NOT NULL,
  transaction_date DATE NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  cash_account_id BIGINT UNSIGNED NOT NULL,
  equity_account_id BIGINT UNSIGNED NOT NULL,
  reference_no VARCHAR(120) NULL,
  description VARCHAR(255) NOT NULL,
  journal_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_equity_tx(company_id,transaction_date,transaction_type),
  CONSTRAINT fk_equity_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_equity_cash FOREIGN KEY(cash_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_equity_account FOREIGN KEY(equity_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_equity_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_equity_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT chk_equity_amount CHECK(amount > 0)
) ENGINE=InnoDB;

CREATE TABLE budget_scenarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  fiscal_year SMALLINT NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  scenario_type ENUM('budget','forecast','best_case','base_case','worst_case') NOT NULL DEFAULT 'forecast',
  status ENUM('draft','approved','archived') NOT NULL DEFAULT 'draft',
  created_by BIGINT UNSIGNED NULL,
  approved_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME NULL,
  UNIQUE KEY uq_budget_scenario(company_id,fiscal_year,code),
  CONSTRAINT fk_budget_scenario_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_budget_scenario_user FOREIGN KEY(created_by) REFERENCES users(id),
  CONSTRAINT fk_budget_scenario_approver FOREIGN KEY(approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE budget_scenario_lines (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scenario_id BIGINT UNSIGNED NOT NULL,
  branch_id BIGINT UNSIGNED NULL,
  department_id BIGINT UNSIGNED NULL,
  period TINYINT UNSIGNED NOT NULL,
  account_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  branch_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(branch_id,0)) STORED,
  department_scope BIGINT UNSIGNED GENERATED ALWAYS AS (IFNULL(department_id,0)) STORED,
  UNIQUE KEY uq_budget_scenario_line(scenario_id,branch_scope,department_scope,period,account_id),
  CONSTRAINT fk_budget_scenario_line_parent FOREIGN KEY(scenario_id) REFERENCES budget_scenarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_budget_scenario_line_branch FOREIGN KEY(branch_id) REFERENCES branches(id),
  CONSTRAINT fk_budget_scenario_line_department FOREIGN KEY(department_id) REFERENCES departments(id),
  CONSTRAINT fk_budget_scenario_line_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),
  CONSTRAINT chk_budget_scenario_period CHECK(period BETWEEN 1 AND 12),
  CONSTRAINT chk_budget_scenario_amount CHECK(amount >= 0)
) ENGINE=InnoDB;

INSERT IGNORE INTO role_permissions(role_code,permission_code) VALUES
('group_owner','advance.manage'),('group_owner','financing.manage'),('group_owner','equity.manage'),('group_owner','planning.manage'),('group_owner','notification.view'),
('group_finance','advance.manage'),('group_finance','financing.manage'),('group_finance','equity.manage'),('group_finance','planning.manage'),('group_finance','notification.view'),
('entity_admin','advance.manage'),('entity_admin','financing.manage'),('entity_admin','planning.manage'),('entity_admin','notification.view'),
('auditor','notification.view'),('viewer','notification.view');
