USE nexa_group_finance;

ALTER TABLE users
  ADD COLUMN company_id BIGINT UNSIGNED NULL AFTER role,
  ADD CONSTRAINT fk_user_company FOREIGN KEY(company_id) REFERENCES companies(id);

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

INSERT INTO fiscal_periods(fiscal_year,period,status)
VALUES (2026,9,'open')
ON DUPLICATE KEY UPDATE status=VALUES(status);

INSERT INTO currency_rates(rate_date,currency_code,rate_to_idr)
VALUES (CURRENT_DATE,'IDR',1)
ON DUPLICATE KEY UPDATE rate_to_idr=VALUES(rate_to_idr);
