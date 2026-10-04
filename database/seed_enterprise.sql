USE nexa_group_finance;

INSERT INTO companies(id,code,name,legal_name,business_type,base_currency,is_active) VALUES
(1,'HTL','PT Hotel Nusantara','PT Hotel Nusantara','Hotel','IDR',1),
(2,'RTL','CV Retail Sejahtera','CV Retail Sejahtera','Retail','IDR',1),
(3,'KOS','Properti Harmoni','Properti Harmoni','Kos & Properti','IDR',1),
(4,'FNB','PT Kuliner Bersama','PT Kuliner Bersama','F&B','IDR',1);

INSERT INTO branches(id,company_id,code,name,timezone,is_active) VALUES
(1,1,'HTL-MDO','Hotel Manado','Asia/Makassar',1),
(2,2,'RTL-MDO','Retail Manado','Asia/Makassar',1),
(3,3,'KOS-A','Kos Harmoni A','Asia/Makassar',1),
(4,4,'FNB-MDO','Kuliner Manado','Asia/Makassar',1);

INSERT INTO departments(id,company_id,branch_id,code,name,cost_center_code,profit_center_code,is_active) VALUES
(1,1,1,'ROOM','Rooms','CC-ROOM','PC-ROOM',1),(2,1,1,'FNB','Food & Beverage','CC-FNB','PC-FNB',1),
(3,2,2,'SALES','Sales','CC-SALES','PC-SALES',1),(4,3,3,'RENT','Rental','CC-RENT','PC-RENT',1);

INSERT INTO chart_accounts(id,company_id,code,name,account_type,is_cash_bank,is_active) VALUES
(1,NULL,'1101','Kas & Bank','asset',1,1),(2,NULL,'1102','Piutang Usaha','asset',0,1),(3,NULL,'1201','Aset Tetap','asset',0,1),
(4,NULL,'2101','Hutang Usaha','liability',0,1),(5,NULL,'3101','Modal & Saldo Laba','equity',0,1),(6,NULL,'4101','Pendapatan Usaha','revenue',0,1),
(7,NULL,'5101','Beban Operasional','expense',0,1),(8,NULL,'5201','Beban Utilitas','expense',0,1),(9,NULL,'1301','Piutang Antar Perusahaan','asset',0,1),
(10,NULL,'2301','Hutang Antar Perusahaan','liability',0,1),(11,NULL,'1191','Akumulasi Penyusutan','asset',0,1),(12,NULL,'5301','Beban Penyusutan','expense',0,1),
(13,NULL,'2111','PPN Keluaran','liability',0,1),(14,NULL,'1151','PPN Masukan','asset',0,1),(15,NULL,'7101','Laba/Rugi Selisih Kurs','revenue',0,1);

INSERT INTO users(id,name,email,password_hash,role,company_id,is_active) VALUES
(1,'Group Owner','owner@nexa.local','$2y$10$Z4hR6fO1sY9j5sGcEDHwJu71MzNk.qe0b7yNLfP7vgRFJbwAdHj7S','group_owner',NULL,1),
(2,'Finance Controller','finance@nexa.local','$2y$10$Z4hR6fO1sY9j5sGcEDHwJu71MzNk.qe0b7yNLfP7vgRFJbwAdHj7S','group_finance',NULL,1),
(3,'Entity Admin Hotel','hotel@nexa.local','$2y$10$Z4hR6fO1sY9j5sGcEDHwJu71MzNk.qe0b7yNLfP7vgRFJbwAdHj7S','entity_admin',1,1),
(4,'Internal Auditor','audit@nexa.local','$2y$10$Z4hR6fO1sY9j5sGcEDHwJu71MzNk.qe0b7yNLfP7vgRFJbwAdHj7S','auditor',NULL,1);

INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES
(2026,8,'closed'),(2026,9,'open');

INSERT INTO tax_profiles(id,code,name,rate,tax_type,input_account_id,output_account_id,is_active) VALUES
(1,'PPN11','PPN 11%',11,'output',14,13,1),(2,'NONE','Non Pajak',0,'none',NULL,NULL,1);

INSERT INTO income_categories(id,company_id,code,name,revenue_account_id,tax_profile_id,sort_order,is_active) VALUES
(1,1,'ROOM','Pendapatan Kamar',6,1,10),(2,1,'RESTO','Restoran / Mini Bar',6,1,20),(3,1,'LAUNDRY','Laundry',6,1,30),
(4,2,'SALES','Penjualan Barang',6,1,10),(5,2,'SERVICE','Pendapatan Jasa',6,1,20),
(6,3,'RENT','Sewa Kamar',6,2,10),(7,3,'UTILITY','Utilitas / Service',6,2,20),
(8,4,'DINE','Dine In',6,1,10),(9,4,'DELIVERY','Takeaway / Delivery',6,1,20);

INSERT INTO bank_accounts(id,company_id,account_id,bank_name,account_name,account_number,currency,is_active) VALUES
(1,1,1,'Bank Utama','PT Hotel Nusantara','1234567890','IDR',1),(2,2,1,'Bank Utama','CV Retail Sejahtera','2234567890','IDR',1),
(3,3,1,'Bank Utama','Properti Harmoni','3234567890','IDR',1),(4,4,1,'Bank Utama','PT Kuliner Bersama','4234567890','IDR',1);

INSERT INTO budgets(company_id,fiscal_year,period,account_id,amount,scenario) VALUES
(1,2026,9,7,1400000000,'budget'),(2,2026,9,7,1000000000,'budget'),(3,2026,9,7,390000000,'budget'),(4,2026,9,7,560000000,'budget');

INSERT INTO fixed_assets(id,company_id,branch_id,asset_code,name,category,acquisition_date,acquisition_cost,useful_life_months,residual_value,status) VALUES
(1,1,1,'AST-HTL-001','Gedung Hotel Utama','Bangunan','2020-01-01',7800000000,240,0,'active'),
(2,2,2,'AST-RTL-001','Ruko Retail A','Bangunan','2021-01-01',2600000000,240,0,'active'),
(3,3,3,'AST-KOS-001','Kompleks Kos Harmoni','Bangunan','2022-01-01',7400000000,240,0,'active'),
(4,4,4,'AST-FNB-001','Kendaraan Operasional','Kendaraan','2024-01-01',620000000,60,20000000,'active');

INSERT INTO invoices(id,company_id,branch_id,invoice_type,invoice_no,party_name,issue_date,due_date,amount,paid_amount,status,currency) VALUES
(1,1,1,'receivable','AR-HTL-260901','OTA Nusantara','2026-09-05','2026-10-05',42500000,17500000,'partial','IDR'),
(2,3,3,'receivable','AR-KOS-260917','Tenant Korporat A','2026-09-17','2026-09-30',18000000,0,'open','IDR'),
(3,4,4,'payable','AP-FNB-260920','Supplier Bahan Pangan','2026-09-20','2026-10-04',28750000,10000000,'partial','IDR'),
(4,2,2,'payable','AP-RTL-260925','Distributor Elektronik','2026-09-25','2026-10-25',53200000,0,'open','IDR');

INSERT INTO currency_rates(rate_date,currency_code,rate_to_idr,source) VALUES
('2026-09-25','IDR',1,'manual'),('2026-09-25','USD',16850,'manual'),('2026-09-25','SGD',13100,'manual');

INSERT INTO approval_policies(company_id,document_type,threshold_amount,min_approvers,roles_json,is_active) VALUES
(NULL,'journal',25000000,1,JSON_ARRAY('group_owner','group_finance'),1),
(NULL,'payment',25000000,1,JSON_ARRAY('group_owner','group_finance'),1);

INSERT INTO journal_entries(id,company_id,branch_id,journal_no,journal_date,description,source_type,status,posted_at,created_by) VALUES
(1,1,1,'JRN-202609-0001','2026-09-24','Pendapatan kamar & layanan','seed','posted','2026-09-24 23:00:00',1),
(2,2,2,'JRN-202609-0002','2026-09-24','Penjualan toko','seed','posted','2026-09-24 23:00:00',1),
(3,4,4,'JRN-202609-0003','2026-09-23','Pembelian bahan baku','seed','posted','2026-09-23 23:00:00',1),
(4,3,3,'JRN-202609-0004','2026-09-23','Penerimaan sewa kos','seed','posted','2026-09-23 23:00:00',1),
(5,1,1,'JRN-202609-0005','2026-09-22','Pembayaran listrik','seed','posted','2026-09-22 23:00:00',1),
(6,1,1,'JRN-202609-0006','2026-09-19','Pinjaman antar perusahaan ke Retail','seed','posted','2026-09-19 23:00:00',1),
(7,2,2,'JRN-202609-0007','2026-09-19','Terima pinjaman dari Hotel','seed','posted','2026-09-19 23:00:00',1);

INSERT INTO journal_lines(journal_id,account_id,description,debit,credit,intercompany_company_id) VALUES
(1,1,'Kas/Bank',48500000,0,NULL),(1,6,'Revenue',0,48500000,NULL),
(2,1,'Kas/Bank',31750000,0,NULL),(2,6,'Revenue',0,31750000,NULL),
(3,7,'Expense',12450000,0,NULL),(3,1,'Cash',0,12450000,NULL),
(4,1,'Cash',18600000,0,NULL),(4,6,'Revenue',0,18600000,NULL),
(5,8,'Utilities',9200000,0,NULL),(5,1,'Cash',0,9200000,NULL),
(6,9,'IC Receivable',100000000,0,2),(6,1,'Cash',0,100000000,2),
(7,1,'Cash',100000000,0,1),(7,10,'IC Payable',0,100000000,1);

INSERT INTO bank_feed(company_id,bank_account_id,txn_date,description,amount,direction,status,journal_id,external_ref) VALUES
(1,1,'2026-09-24','SETTLEMENT OTA 240926',48500000,'in','matched',1,'SEED-BANK-001'),
(1,1,'2026-09-22','PLN HOTEL SEPTEMBER',9200000,'out','matched',5,'SEED-BANK-002'),
(2,2,'2026-09-25','TRANSFER MASUK ECOMMERCE',14750000,'in','unmatched',NULL,'SEED-BANK-003'),
(4,4,'2026-09-25','DEBIT SUPPLIER 8921',6350000,'out','unmatched',NULL,'SEED-BANK-004');
