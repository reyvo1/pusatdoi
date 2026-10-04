-- NEXA Group Finance v4 Production Final hardening
-- Apply after v3 on an existing RC2 database.

CREATE TABLE IF NOT EXISTS login_attempts(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier_hash CHAR(64) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  success TINYINT(1) NOT NULL DEFAULT 0,
  INDEX ix_login_attempt(identifier_hash,ip_address,attempted_at,success)
) ENGINE=InnoDB;

-- De-duplicate imported bank rows before enforcing idempotency.
DELETE b1 FROM bank_feed b1
JOIN bank_feed b2
  ON b1.company_id=b2.company_id
 AND b1.external_ref=b2.external_ref
 AND b1.id>b2.id
WHERE b1.external_ref IS NOT NULL AND b1.external_ref<>'';

ALTER TABLE bank_feed
  ADD UNIQUE KEY uq_bank_feed_external(company_id,external_ref);

ALTER TABLE journal_lines
  ADD CONSTRAINT chk_line_positive CHECK (debit > 0 OR credit > 0);

ALTER TABLE journal_entries
  ADD CONSTRAINT fk_je_reversal_of FOREIGN KEY(reversal_of) REFERENCES journal_entries(id),
  ADD CONSTRAINT fk_je_reversed_by FOREIGN KEY(reversed_by) REFERENCES journal_entries(id);

ALTER TABLE fixed_assets
  ADD CONSTRAINT chk_asset_life CHECK (useful_life_months > 0),
  ADD CONSTRAINT chk_asset_values CHECK (acquisition_cost >= 0 AND residual_value >= 0 AND residual_value <= acquisition_cost);

ALTER TABLE invoices
  ADD CONSTRAINT chk_invoice_amount CHECK (amount > 0),
  ADD CONSTRAINT chk_invoice_paid CHECK (paid_amount >= 0 AND paid_amount <= amount);

ALTER TABLE invoice_payments
  ADD CONSTRAINT chk_payment_amount CHECK (amount > 0);

ALTER TABLE fx_events
  ADD CONSTRAINT chk_fx_values CHECK (foreign_amount > 0 AND book_rate > 0 AND settlement_rate > 0);

ALTER TABLE intercompany_eliminations
  ADD CONSTRAINT chk_elimination_amount CHECK (amount > 0),
  ADD CONSTRAINT chk_elimination_entities CHECK (from_company_id <> to_company_id);
