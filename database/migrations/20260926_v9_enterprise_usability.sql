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
