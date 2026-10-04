<?php
require __DIR__.'/lib/bootstrap.php';
requireLogin(true);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
try {
    $action=$_GET['action']??'';
    if($action==='dashboard') jsonOut(['ok'=>true,'data'=>dashboardData()]);
    if($action==='report-data'){ $cid=max(0,(int)($_GET['company_id']??0)); jsonOut(['ok'=>true,'data'=>financialReportData($cid?:null)]); }

    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-company'){
        verifyCsrf();$row=createCompany($_POST);jsonOut(['ok'=>true,'message'=>'Badan usaha berhasil dibuat.','company'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-branch'){
        verifyCsrf();$row=createBranch($_POST);jsonOut(['ok'=>true,'message'=>'Cabang berhasil disimpan.','branch'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-department'){
        verifyCsrf();$row=createDepartment($_POST);jsonOut(['ok'=>true,'message'=>'Departemen / cost center berhasil disimpan.','department'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-user'){
        verifyCsrf();$row=createUserAccount($_POST);jsonOut(['ok'=>true,'message'=>'User berhasil dibuat.','user'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-budget'){
        verifyCsrf();$row=createBudget($_POST);jsonOut(['ok'=>true,'message'=>'Budget berhasil disimpan.','budget'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-asset'){
        verifyCsrf();$row=createAsset($_POST);jsonOut(['ok'=>true,'message'=>'Aset berhasil ditambahkan.','asset'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-bank-account'){
        verifyCsrf();$row=createBankAccount($_POST);jsonOut(['ok'=>true,'message'=>'Rekening bank berhasil ditambahkan.','bank_account'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='post-daily-income'){
        verifyCsrf();$row=postDailyIncome($_POST);
        if(!empty($row['approval_required'])) jsonOut(['ok'=>true,'pending_approval'=>true,'message'=>'Pendapatan harian melewati batas approval dan masuk antrian persetujuan.','entry'=>$row,'dashboard'=>dashboardData()]);
        jsonOut(['ok'=>true,'message'=>'Pendapatan harian berhasil diposting ke ledger.','entry'=>$row,'dashboard'=>dashboardData()]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='create-income-category'){
        verifyCsrf();$row=createIncomeCategory($_POST);jsonOut(['ok'=>true,'message'=>'Kategori pendapatan berhasil ditambahkan.','category'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='post-journal'){
        verifyCsrf();
        $entry=postJournal($_POST);
        if(!empty($entry['approval_required'])) jsonOut(['ok'=>true,'pending_approval'=>true,'message'=>'Transaksi melewati batas approval dan masuk antrian persetujuan.','entry'=>$entry,'dashboard'=>dashboardData()]);
        jsonOut(['ok'=>true,'message'=>'Jurnal berhasil diposting dan balance.','entry'=>$entry,'dashboard'=>dashboardData()]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='approval-decision'){
        verifyCsrf();
        $row=decideApproval((int)($_POST['id']??0),(string)($_POST['decision']??''));
        jsonOut(['ok'=>true,'message'=>'Approval diperbarui menjadi '.$row['status'].'.','approval'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='close-period'){
        verifyCsrf();
        $row=closePeriod((int)($_POST['year']??0),(int)($_POST['month']??0));
        jsonOut(['ok'=>true,'message'=>'Periode berhasil ditutup.','period'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='reconcile-bank'){
        verifyCsrf();
        $row=reconcileBank((int)($_POST['bank_id']??0),(int)($_POST['journal_id']??0));
        jsonOut(['ok'=>true,'message'=>'Mutasi bank berhasil direkonsiliasi.','bank'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='reverse-journal'){
        verifyCsrf();
        $row=reverseJournal((int)($_POST['journal_id']??0),(string)($_POST['reason']??''));
        jsonOut(['ok'=>true,'message'=>'Jurnal reversal berhasil dibuat tanpa menghapus jejak transaksi asli.','entry'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='allocate-payment'){
        verifyCsrf();
        $row=allocateInvoicePayment((int)($_POST['invoice_id']??0),(float)($_POST['amount']??0),(string)($_POST['date']??date('Y-m-d')),(string)($_POST['method']??'bank'),(string)($_POST['reference']??''));
        jsonOut(['ok'=>true,'message'=>'Pembayaran berhasil dialokasikan dan jurnal kas/bank dibuat.','payment'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='intercompany-eliminate'){
        verifyCsrf();
        $row=createElimination((int)($_POST['from_company_id']??0),(int)($_POST['to_company_id']??0),(float)($_POST['amount']??0),(string)($_POST['period']??date('Y-m')),(string)($_POST['description']??''));
        jsonOut(['ok'=>true,'message'=>'Eliminasi intercompany berhasil diposting ke consolidation layer.','elimination'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='calculate-tax'){
        verifyCsrf();
        $row=calculateTax((float)($_POST['base']??0),(float)($_POST['rate']??0),!empty($_POST['inclusive']));
        jsonOut(['ok'=>true,'tax'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='record-fx'){
        verifyCsrf();
        $row=recordFxEvent((int)($_POST['company_id']??0),(string)($_POST['date']??date('Y-m-d')),(string)($_POST['currency']??''),(float)($_POST['foreign_amount']??0),(float)($_POST['book_rate']??0),(float)($_POST['settlement_rate']??0),(string)($_POST['description']??''));
        jsonOut(['ok'=>true,'message'=>'FX settlement dicatat dan gain/loss dihitung.','fx'=>$row]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='import-bank-csv'){
        verifyCsrf();
        $csv=(string)($_POST['csv']??'');
        if(isset($_FILES['file'])&&is_uploaded_file($_FILES['file']['tmp_name']))$csv=(string)file_get_contents($_FILES['file']['tmp_name']);
        $row=importBankCsv((int)($_POST['company_id']??0),$csv);
        jsonOut(['ok'=>true,'message'=>$row['count'].' mutasi bank berhasil diimpor sebagai unmatched.','import'=>$row]);
    }

    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-create-account'){verifyCsrf();$row=r4CreateAccount($_POST);jsonOut(['ok'=>true,'message'=>'Chart of Account berhasil dibuat.','account'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-post-journal'){verifyCsrf();$row=r4PostMultiLineJournal($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Jurnal masuk approval queue.':'Jurnal multi-line berhasil diposting.','entry'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-create-party'){verifyCsrf();$row=r4CreateParty($_POST);jsonOut(['ok'=>true,'message'=>'Customer/vendor berhasil dibuat.','party'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-create-invoice'){verifyCsrf();$row=r4CreateInvoice($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Invoice masuk approval queue.':'Invoice dan jurnal AR/AP berhasil dibuat.','invoice'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-allocate-payment'){verifyCsrf();$row=r4AllocatePayment($_POST);jsonOut(['ok'=>true,'message'=>'Pembayaran berhasil dialokasikan ke invoice dan ledger.','payment'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-approval-decision'){verifyCsrf();$row=r4DecideApproval((int)($_POST['id']??0),(string)($_POST['decision']??''),(string)($_POST['note']??''));jsonOut(['ok'=>true,'message'=>'Keputusan approval berhasil diproses.','approval'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-open-reconciliation'){verifyCsrf();$row=r4OpenReconciliation($_POST);jsonOut(['ok'=>true,'message'=>'Session rekonsiliasi dibuka.','session'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-match-reconciliation'){verifyCsrf();$row=r4ReconciliationMatch($_POST);jsonOut(['ok'=>true,'message'=>'Mutasi bank berhasil dimatch dalam session.','match'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-close-reconciliation'){verifyCsrf();$row=r4CloseReconciliation((int)($_POST['id']??0),false);jsonOut(['ok'=>true,'message'=>'Session rekonsiliasi ditutup.','session'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-reopen-reconciliation'){verifyCsrf();$row=r4CloseReconciliation((int)($_POST['id']??0),true);jsonOut(['ok'=>true,'message'=>'Session rekonsiliasi dibuka kembali.','session'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-consolidate'){verifyCsrf();$row=r4CreateConsolidationRun($_POST);jsonOut(['ok'=>true,'message'=>'Consolidation run dan elimination entries berhasil diposting.','run'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-post-depreciation'){verifyCsrf();$row=r4GenerateDepreciation($_POST);jsonOut(['ok'=>true,'message'=>'Depresiasi periode berhasil diposting.','depreciation'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-save-fx-rate'){verifyCsrf();$row=r4SaveFxRate($_POST);jsonOut(['ok'=>true,'message'=>'FX rate berhasil disimpan.','rate'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-fx-revaluation'){verifyCsrf();$row=r4PostFxRevaluation($_POST);jsonOut(['ok'=>true,'message'=>'FX revaluation berhasil diposting ke ledger.','revaluation'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-close-period'){verifyCsrf();$row=r4ClosePeriod($_POST);jsonOut(['ok'=>true,'message'=>'Periode berhasil ditutup setelah checklist enterprise PASS.','period'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-post-daily-income'){verifyCsrf();$row=r4PostDailyIncome($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Pendapatan harian masuk approval queue.':'Pendapatan harian dimensional berhasil diposting.','entry'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-acquire-asset'){verifyCsrf();$row=r4AcquireAsset($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Akuisisi aset masuk approval queue.':'Akuisisi aset dan jurnal berhasil dibuat.','asset'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-transfer-asset'){verifyCsrf();$row=r4TransferAsset($_POST);jsonOut(['ok'=>true,'message'=>'Transfer aset berhasil dicatat.','asset'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-impair-asset'){verifyCsrf();$row=r4ImpairAsset($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Impairment aset masuk approval queue.':'Impairment aset berhasil diposting.','asset'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-dispose-asset'){verifyCsrf();$row=r4DisposeAsset($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Disposal aset masuk approval queue.':'Disposal aset berhasil diposting.','asset'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-create-approval-policy'){verifyCsrf();$row=r4CreateApprovalPolicy($_POST);jsonOut(['ok'=>true,'message'=>'Approval policy berhasil disimpan.','policy'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-create-bank-profile'){verifyCsrf();$row=r4CreateBankImportProfile($_POST);jsonOut(['ok'=>true,'message'=>'Bank import profile berhasil disimpan.','profile'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-import-bank-statement'){verifyCsrf();$csv=(string)($_POST['csv']??'');$name='statement.csv';if(isset($_FILES['file'])&&is_uploaded_file($_FILES['file']['tmp_name'])){$csv=(string)file_get_contents($_FILES['file']['tmp_name']);$name=(string)($_FILES['file']['name']??$name);} $row=r4ImportBankStatement($_POST,$csv,$name);jsonOut(['ok'=>true,'message'=>$row['imported'].' mutasi berhasil diimpor; '.$row['skipped'].' dilewati.','import'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-tax-status'){verifyCsrf();$row=r4SetTaxStatus($_POST);jsonOut(['ok'=>true,'message'=>'Status filing pajak berhasil diperbarui.','tax'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r4-adjust-invoice'){verifyCsrf();$row=r4AdjustInvoice($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Adjustment invoice masuk approval queue.':'Adjustment invoice dan ledger berhasil diposting.','adjustment'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-create-expense-category'){verifyCsrf();$row=r5CreateExpenseCategory($_POST);jsonOut(['ok'=>true,'message'=>'Kategori pengeluaran berhasil disimpan.','category'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-post-daily-expense'){verifyCsrf();$row=r5PostDailyExpense($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Pengeluaran masuk approval queue.':'Pengeluaran harian berhasil diposting.','entry'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-opening-balance'){verifyCsrf();$row=r5PostOpeningBalance($_POST);jsonOut(['ok'=>true,'message'=>'Saldo awal berhasil diposting dan dikunci per tahun fiskal.','opening_balance'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-bank-transfer'){verifyCsrf();$row=r5BankTransfer($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Transfer masuk approval queue.':'Transfer antar rekening berhasil diposting.','transfer'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-create-forecast'){verifyCsrf();$row=r5CreateForecast($_POST);jsonOut(['ok'=>true,'message'=>'Cash forecast berhasil ditambahkan.','forecast'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-create-recurring'){verifyCsrf();$row=r5CreateRecurringTemplate($_POST);jsonOut(['ok'=>true,'message'=>'Template jurnal berulang berhasil dibuat.','template'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-run-recurring'){verifyCsrf();$row=r5RunRecurring((int)($_POST['id']??0),($_POST['run_date']??'')?:null);jsonOut(['ok'=>true,'message'=>'Template recurring berhasil dijalankan.','run'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-process-recurring-reversals'){verifyCsrf();$row=r5ProcessRecurringReversals(($_POST['as_of']??'')?:null,false);jsonOut(['ok'=>true,'message'=>$row['processed'].' auto-reversal recurring berhasil diproses.','result'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r5-year-end-close'){verifyCsrf();$row=r5YearEndClose($_POST);jsonOut(['ok'=>true,'message'=>'Year-end closing berhasil diposting ke retained earnings.','year_end'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r6-bulk-import'){verifyCsrf();[$csv,$name]=r6CsvFromRequest($_POST);$row=r6BulkImport($_POST,$csv,$name);jsonOut(['ok'=>true,'message'=>'Import selesai: '.$row['rows_success'].' berhasil, '.$row['rows_failed'].' gagal.','import'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r6-create-payment-batch'){verifyCsrf();$row=r6CreatePaymentBatch($_POST);jsonOut(['ok'=>true,'message'=>'AP payment batch berhasil direncanakan.','batch'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r6-post-payment-batch'){verifyCsrf();$row=r6PostPaymentBatch((int)($_POST['id']??0),false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Payment batch masuk approval queue.':'Payment batch berhasil diposting ke ledger.','batch'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r6-upload-document'){verifyCsrf();if(!isset($_FILES['file']))throw new InvalidArgumentException('File bukti wajib dipilih.');$row=r6UploadDocument($_POST,$_FILES['file']);jsonOut(['ok'=>true,'message'=>'Dokumen bukti berhasil disimpan.','document'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-issue-advance'){verifyCsrf();$row=r7IssueAdvance($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Uang muka masuk approval queue.':'Uang muka berhasil dicairkan.','advance'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-settle-advance'){verifyCsrf();$row=r7SettleAdvance($_POST);jsonOut(['ok'=>true,'message'=>'Pertanggungjawaban uang muka berhasil diposting.','advance'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-create-loan'){verifyCsrf();$row=r7CreateLoan($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Pinjaman masuk approval queue.':'Pencairan pinjaman berhasil diposting.','loan'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-pay-loan'){verifyCsrf();$row=r7PayLoan($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Pembayaran pinjaman masuk approval queue.':'Pembayaran pinjaman berhasil diposting.','loan'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-post-equity'){verifyCsrf();$row=r7PostEquity($_POST,false);jsonOut(['ok'=>true,'pending_approval'=>!empty($row['approval_required']),'message'=>!empty($row['approval_required'])?'Transaksi equity masuk approval queue.':'Transaksi equity berhasil diposting.','equity'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-create-budget-scenario'){verifyCsrf();$row=r7CreateBudgetScenario($_POST);jsonOut(['ok'=>true,'message'=>'Budget scenario berhasil dibuat.','scenario'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='r7-save-budget-line'){verifyCsrf();$row=r7SaveBudgetScenarioLine($_POST);jsonOut(['ok'=>true,'message'=>'Target scenario berhasil disimpan.','line'=>$row]);}
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='switch-demo-user'){
        verifyCsrf(); global $config;
        if(!$config['demo_mode']) throw new RuntimeException('Pergantian user cepat hanya tersedia pada Demo Mode.');
        $s=loadStore();$id=(int)($_POST['user_id']??0);$found=null;foreach(($s['users']??[]) as $u)if((int)$u['id']===$id)$found=$u;
        if(!$found)throw new InvalidArgumentException('User demo tidak ditemukan.');
        $_SESSION['user_id']=$id;$_SESSION['user_name']=$found['name'];writeAudit('session.user_switched',['user_id'=>$id,'role'=>$found['role']]);
        jsonOut(['ok'=>true,'message'=>'Role aktif: '.$found['role']]);
    }
    if($_SERVER['REQUEST_METHOD']==='POST'&&$action==='reset-demo'){
        verifyCsrf(); global $config;if(!$config['demo_mode'])throw new RuntimeException('Reset hanya tersedia di Demo Mode.');saveStore(demoSeed());writeAudit('demo.reset');jsonOut(['ok'=>true]);
    }
    jsonOut(['ok'=>false,'message'=>'Endpoint tidak ditemukan.'],404);
} catch(PDOException $e){ error_log('NEXA API database error: '.$e->getMessage()); jsonOut(['ok'=>false,'message'=>'Operasi database gagal. Periksa log server dengan correlation time '.date('c').'.'],500); }
catch(Throwable $e){ jsonOut(['ok'=>false,'message'=>$e->getMessage()],422); }
