-- NEXA Group Finance R2: dynamic daily income
CREATE TABLE IF NOT EXISTS income_categories(
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  company_id BIGINT UNSIGNED NOT NULL,
  code VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  revenue_account_id BIGINT UNSIGNED NOT NULL,
  sort_order INT NOT NULL DEFAULT 100,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uq_income_category(company_id,code),
  INDEX ix_income_category_company(company_id,is_active,sort_order),
  CONSTRAINT fk_income_category_company FOREIGN KEY(company_id) REFERENCES companies(id),
  CONSTRAINT fk_income_category_account FOREIGN KEY(revenue_account_id) REFERENCES chart_accounts(id)
) ENGINE=InnoDB;

INSERT IGNORE INTO income_categories(company_id,code,name,revenue_account_id,sort_order)
SELECT c.id,'MAIN','Pendapatan Utama',a.id,10 FROM companies c JOIN chart_accounts a ON a.code='4101' AND a.is_active=1 WHERE a.company_id IS NULL OR a.company_id=c.id;
