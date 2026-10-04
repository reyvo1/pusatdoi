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
