USE nexa_group_finance;
INSERT INTO companies(code,name,business_type) VALUES ('HTL','PT Hotel Nusantara','Hotel'),('RTL','CV Retail Sejahtera','Retail'),('KOS','Properti Harmoni','Kos & Properti'),('FNB','PT Kuliner Bersama','F&B');
INSERT INTO chart_accounts(code,name,account_type,is_cash_bank) VALUES ('1101','Kas & Bank','asset',1),('1102','Piutang Usaha','asset',0),('1201','Aset Tetap','asset',0),('2101','Hutang Usaha','liability',0),('3101','Modal & Saldo Laba','equity',0),('4101','Pendapatan Usaha','revenue',0),('5101','Beban Operasional','expense',0),('5201','Beban Utilitas','expense',0),('1301','Piutang Antar Perusahaan','asset',0),('2301','Hutang Antar Perusahaan','liability',0);

INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES
(2026,8,'closed'),(2026,9,'open')
ON DUPLICATE KEY UPDATE status=VALUES(status);

INSERT INTO currency_rates(rate_date,currency_code,rate_to_idr) VALUES
('2026-09-25','IDR',1),('2026-09-25','USD',16850),('2026-09-25','SGD',13120)
ON DUPLICATE KEY UPDATE rate_to_idr=VALUES(rate_to_idr);

-- Dynamic daily-income defaults
INSERT IGNORE INTO income_categories(company_id,code,name,revenue_account_id,sort_order)
SELECT c.id,'MAIN','Pendapatan Utama',a.id,10 FROM companies c JOIN chart_accounts a ON a.code='4101' AND a.is_active=1 WHERE a.company_id IS NULL OR a.company_id=c.id;
