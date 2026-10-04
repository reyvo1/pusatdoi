USE nexa_group_finance;

ALTER TABLE journal_entries
  ADD COLUMN created_by BIGINT UNSIGNED NULL AFTER posted_at,
  ADD COLUMN reversal_of BIGINT UNSIGNED NULL AFTER created_by,
  ADD COLUMN reversed_by BIGINT UNSIGNED NULL AFTER reversal_of,
  ADD INDEX ix_je_reversal(reversal_of,reversed_by),
  ADD CONSTRAINT fk_je_created_by FOREIGN KEY(created_by) REFERENCES users(id),
  ADD CONSTRAINT fk_je_reversal_of FOREIGN KEY(reversal_of) REFERENCES journal_entries(id),
  ADD CONSTRAINT fk_je_reversed_by FOREIGN KEY(reversed_by) REFERENCES journal_entries(id);

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
