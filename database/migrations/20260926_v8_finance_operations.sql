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
