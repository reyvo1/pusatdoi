CREATE TABLE companies(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,code VARCHAR(20) NOT NULL UNIQUE,name VARCHAR(160) NOT NULL,legal_name VARCHAR(180),business_type VARCHAR(80) NOT NULL,tax_id VARCHAR(64),base_currency CHAR(3) NOT NULL DEFAULT 'IDR',is_active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE branches(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,code VARCHAR(30) NOT NULL,name VARCHAR(160) NOT NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,UNIQUE KEY uq_branch(company_id,code),CONSTRAINT fk_branch_company FOREIGN KEY(company_id) REFERENCES companies(id)) ENGINE=InnoDB;
CREATE TABLE chart_accounts(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NULL,code VARCHAR(30) NOT NULL,name VARCHAR(160) NOT NULL,account_type ENUM('asset','liability','equity','revenue','expense') NOT NULL,parent_id BIGINT UNSIGNED NULL,is_cash_bank TINYINT(1) NOT NULL DEFAULT 0,is_active TINYINT(1) NOT NULL DEFAULT 1,INDEX ix_coa_company(company_id,code),CONSTRAINT fk_coa_parent FOREIGN KEY(parent_id) REFERENCES chart_accounts(id)) ENGINE=InnoDB;
CREATE TABLE journal_entries(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,branch_id BIGINT UNSIGNED NULL,journal_no VARCHAR(50) NOT NULL UNIQUE,journal_date DATE NOT NULL,description VARCHAR(255) NOT NULL,source_type VARCHAR(40) NOT NULL DEFAULT 'manual',source_ref VARCHAR(100),status ENUM('draft','posted','void') NOT NULL DEFAULT 'draft',posted_at DATETIME NULL,created_by BIGINT UNSIGNED NULL,reversal_of BIGINT UNSIGNED NULL,reversed_by BIGINT UNSIGNED NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_je_company FOREIGN KEY(company_id) REFERENCES companies(id),CONSTRAINT fk_je_branch FOREIGN KEY(branch_id) REFERENCES branches(id),INDEX ix_je_date(company_id,journal_date,status)) ENGINE=InnoDB;
CREATE TABLE journal_lines(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,journal_id BIGINT UNSIGNED NOT NULL,account_id BIGINT UNSIGNED NOT NULL,description VARCHAR(255),debit DECIMAL(20,2) NOT NULL DEFAULT 0,credit DECIMAL(20,2) NOT NULL DEFAULT 0,cost_center VARCHAR(80),profit_center VARCHAR(80),intercompany_company_id BIGINT UNSIGNED NULL,CONSTRAINT fk_jl_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id) ON DELETE CASCADE,CONSTRAINT fk_jl_account FOREIGN KEY(account_id) REFERENCES chart_accounts(id),CONSTRAINT chk_nonnegative CHECK(debit>=0 AND credit>=0),CONSTRAINT chk_one_side CHECK(NOT(debit>0 AND credit>0)),INDEX ix_jl_account(account_id),INDEX ix_jl_intercompany(intercompany_company_id)) ENGINE=InnoDB;
CREATE TABLE income_categories(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,code VARCHAR(30) NOT NULL,name VARCHAR(120) NOT NULL,revenue_account_id BIGINT UNSIGNED NOT NULL,sort_order INT NOT NULL DEFAULT 100,is_active TINYINT(1) NOT NULL DEFAULT 1,UNIQUE KEY uq_income_category(company_id,code),INDEX ix_income_category_company(company_id,is_active,sort_order),CONSTRAINT fk_income_category_company FOREIGN KEY(company_id) REFERENCES companies(id),CONSTRAINT fk_income_category_account FOREIGN KEY(revenue_account_id) REFERENCES chart_accounts(id)) ENGINE=InnoDB;
CREATE TABLE bank_accounts(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,account_id BIGINT UNSIGNED NOT NULL,bank_name VARCHAR(100) NOT NULL,account_name VARCHAR(120) NOT NULL,account_number VARCHAR(80) NOT NULL,is_active TINYINT(1) DEFAULT 1,CONSTRAINT fk_bank_company FOREIGN KEY(company_id) REFERENCES companies(id),CONSTRAINT fk_bank_coa FOREIGN KEY(account_id) REFERENCES chart_accounts(id)) ENGINE=InnoDB;
CREATE TABLE budgets(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,fiscal_year SMALLINT NOT NULL,period TINYINT NOT NULL,account_id BIGINT UNSIGNED NOT NULL,amount DECIMAL(20,2) NOT NULL DEFAULT 0,scenario VARCHAR(40) NOT NULL DEFAULT 'budget',UNIQUE KEY uq_budget(company_id,fiscal_year,period,account_id,scenario),CONSTRAINT fk_budget_company FOREIGN KEY(company_id) REFERENCES companies(id),CONSTRAINT fk_budget_coa FOREIGN KEY(account_id) REFERENCES chart_accounts(id)) ENGINE=InnoDB;
CREATE TABLE fixed_assets(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,company_id BIGINT UNSIGNED NOT NULL,branch_id BIGINT UNSIGNED NULL,asset_code VARCHAR(40) NOT NULL UNIQUE,name VARCHAR(160) NOT NULL,category VARCHAR(80) NOT NULL,acquisition_date DATE NOT NULL,acquisition_cost DECIMAL(20,2) NOT NULL,useful_life_months INT NOT NULL,residual_value DECIMAL(20,2) NOT NULL DEFAULT 0,status ENUM('active','disposed','sold') NOT NULL DEFAULT 'active',CONSTRAINT fk_asset_company FOREIGN KEY(company_id) REFERENCES companies(id)) ENGINE=InnoDB;
CREATE TABLE users(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL,email VARCHAR(190) NOT NULL UNIQUE,password_hash VARCHAR(255) NOT NULL,role ENUM('group_owner','group_finance','entity_admin','auditor','viewer') NOT NULL,company_id BIGINT UNSIGNED NULL,is_active TINYINT(1) DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_user_company FOREIGN KEY(company_id) REFERENCES companies(id)) ENGINE=InnoDB;
CREATE TABLE audit_logs(id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,user_id BIGINT UNSIGNED NULL,action VARCHAR(80) NOT NULL,entity_type VARCHAR(80) NOT NULL,entity_id VARCHAR(80),payload_json JSON,ip_address VARCHAR(64),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,INDEX ix_audit_created(created_at),CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id)) ENGINE=InnoDB;

-- Production hardening v2
CREATE TABLE fiscal_periods(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fiscal_year SMALLINT NOT NULL,
  period TINYINT NOT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  closed_at DATETIME NULL,
  closed_by BIGINT UNSIGNED NULL,
  UNIQUE KEY uq_fiscal_period(fiscal_year,period),
  CONSTRAINT fk_period_user FOREIGN KEY(closed_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE journal_approvals(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  journal_id BIGINT UNSIGNED NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  journal_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  requested_by BIGINT UNSIGNED NOT NULL,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  decided_at DATETIME NULL,
  decided_by BIGINT UNSIGNED NULL,
  payload_json JSON NULL,
  INDEX ix_approval_status(status,requested_at),
  CONSTRAINT fk_approval_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_approval_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_approval_requester FOREIGN KEY(requested_by) REFERENCES users(id),
  CONSTRAINT fk_approval_decider FOREIGN KEY(decided_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE invoices(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  invoice_type ENUM('receivable','payable') NOT NULL,
  invoice_no VARCHAR(60) NOT NULL,
  party_name VARCHAR(180) NOT NULL,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  paid_amount DECIMAL(20,2) NOT NULL DEFAULT 0,
  status ENUM('open','partial','paid','void') NOT NULL DEFAULT 'open',
  currency CHAR(3) NOT NULL DEFAULT 'IDR',
  UNIQUE KEY uq_invoice(company_id,invoice_no),
  INDEX ix_invoice_due(company_id,invoice_type,due_date,status),
  CONSTRAINT fk_invoice_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

CREATE TABLE bank_feed(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  bank_account_id BIGINT UNSIGNED NULL,
  txn_date DATE NOT NULL,
  description VARCHAR(255) NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  direction ENUM('in','out') NOT NULL,
  status ENUM('unmatched','matched','ignored') NOT NULL DEFAULT 'unmatched',
  journal_id BIGINT UNSIGNED NULL,
  external_ref VARCHAR(120),
  imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_bank_feed_match(company_id,status,txn_date),
  CONSTRAINT fk_bank_feed_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_bank_feed_account FOREIGN KEY(bank_account_id) REFERENCES bank_accounts(id),
  CONSTRAINT fk_bank_feed_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id)
) ENGINE=InnoDB;

CREATE TABLE currency_rates(
  rate_date DATE NOT NULL,
  currency_code CHAR(3) NOT NULL,
  rate_to_idr DECIMAL(20,6) NOT NULL,
  PRIMARY KEY(rate_date,currency_code)
) ENGINE=InnoDB;


-- Finance completion v3



CREATE TABLE tax_profiles(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  rate DECIMAL(9,4) NOT NULL DEFAULT 0,
  tax_type ENUM('input','output','withholding','none') NOT NULL DEFAULT 'none',
  input_account_id BIGINT UNSIGNED NULL,
  output_account_id BIGINT UNSIGNED NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  CONSTRAINT fk_tax_input_account FOREIGN KEY(input_account_id) REFERENCES chart_accounts(id),
  CONSTRAINT fk_tax_output_account FOREIGN KEY(output_account_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

CREATE TABLE invoice_payments(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  invoice_id BIGINT UNSIGNED NOT NULL,
  company_id BIGINT UNSIGNED NOT NULL,
  payment_date DATE NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  method VARCHAR(40) NOT NULL DEFAULT 'bank',
  reference_no VARCHAR(120),
  journal_id BIGINT UNSIGNED NOT NULL,
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_payment_invoice(invoice_id,payment_date),
  CONSTRAINT fk_payment_invoice FOREIGN KEY(invoice_id) REFERENCES invoices(id),
  CONSTRAINT fk_payment_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_payment_journal FOREIGN KEY(journal_id) REFERENCES journal_entries(id),
  CONSTRAINT fk_payment_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE intercompany_eliminations(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period_key CHAR(7) NOT NULL,
  from_company_id BIGINT UNSIGNED NOT NULL,
  to_company_id BIGINT UNSIGNED NOT NULL,
  amount DECIMAL(20,2) NOT NULL,
  description VARCHAR(255) NOT NULL,
  status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'posted',
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_elim_period(period_key,status),
  CONSTRAINT fk_elim_from FOREIGN KEY(from_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elim_to FOREIGN KEY(to_company_id) REFERENCES companies(id),
  CONSTRAINT fk_elim_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE fx_events(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  event_date DATE NOT NULL,
  currency CHAR(3) NOT NULL,
  foreign_amount DECIMAL(20,6) NOT NULL,
  book_rate DECIMAL(20,6) NOT NULL,
  settlement_rate DECIMAL(20,6) NOT NULL,
  base_amount DECIMAL(20,2) NOT NULL,
  gain_loss DECIMAL(20,2) NOT NULL,
  description VARCHAR(255),
  created_by BIGINT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX ix_fx_company_date(company_id,event_date),
  CONSTRAINT fk_fx_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_fx_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE integration_staging(
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
  INDEX ix_staging_status(status,received_at),
  CONSTRAINT fk_staging_company FOREIGN KEY(company_id) REFERENCES companies(id)
) ENGINE=InnoDB;

INSERT INTO tax_profiles(code,name,rate,tax_type)
VALUES ('PPN11','PPN Keluaran',11,'output'),('PPNIN11','PPN Masukan',11,'input'),('NONE','Non Pajak',0,'none')
ON DUPLICATE KEY UPDATE name=VALUES(name),rate=VALUES(rate),tax_type=VALUES(tax_type),is_active=1;

CREATE TABLE login_attempts(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX ix_login_attempt(identifier_hash,ip_address,attempted_at,success)
) ENGINE=InnoDB;

-- Production Final v4 hardening
ALTER TABLE bank_feed ADD UNIQUE KEY uq_bank_feed_external(company_id,external_ref);
ALTER TABLE journal_lines ADD CONSTRAINT chk_line_positive CHECK (debit > 0 OR credit > 0);
ALTER TABLE journal_entries
  ADD CONSTRAINT fk_je_reversal_of FOREIGN KEY(reversal_of) REFERENCES journal_entries(id),
  ADD CONSTRAINT fk_je_reversed_by FOREIGN KEY(reversed_by) REFERENCES journal_entries(id);
ALTER TABLE fixed_assets
  ADD CONSTRAINT chk_asset_life CHECK (useful_life_months > 0),
  ADD CONSTRAINT chk_asset_values CHECK (acquisition_cost >= 0 AND residual_value >= 0 AND residual_value <= acquisition_cost);
ALTER TABLE invoices
  ADD CONSTRAINT chk_invoice_amount CHECK (amount > 0),
  ADD CONSTRAINT chk_invoice_paid CHECK (paid_amount >= 0 AND paid_amount <= amount);
ALTER TABLE invoice_payments ADD CONSTRAINT chk_payment_amount CHECK (amount > 0);
ALTER TABLE fx_events ADD CONSTRAINT chk_fx_values CHECK (foreign_amount > 0 AND book_rate > 0 AND settlement_rate > 0);
ALTER TABLE intercompany_eliminations
  ADD CONSTRAINT chk_elimination_amount CHECK (amount > 0),
  ADD CONSTRAINT chk_elimination_entities CHECK (from_company_id <> to_company_id);
