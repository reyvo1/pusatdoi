<?php
require __DIR__.'/../lib/bootstrap.php';
$pass=0;$fail=0;
function t(string $name,bool $ok):void{global$pass,$fail;echo($ok?'PASS':'FAIL')."  $name\n";$ok?$pass++:$fail++;}
$original=is_file(storagePath())?file_get_contents(storagePath()):null;
try{
  $s=demoSeed();
  t('Seed has companies',count($s['companies'])===4);
  t('All seeded journals balance',array_reduce($s['entries'],fn($c,$e)=>$c&&entryBalanced($e['lines']),true));
  $m=metrics($s);t('Revenue positive',$m['revenue']>0);t('Expense positive',$m['expense']>0);t('Profit equals revenue-expense',abs($m['profit']-($m['revenue']-$m['expense']))<0.01);t('Cash derived from ledger',is_numeric($m['cash']));
  t('Intercompany detected',count(intercompanySummary($s))>=2);
  t('Transaction limit enforced',count(transactions($s,5))===5);
  t('Unbalanced rejected',entryBalanced([['debit'=>100,'credit'=>0],['debit'=>0,'credit'=>90]])===false);
  t('Balanced accepted',entryBalanced([['debit'=>100,'credit'=>0],['debit'=>0,'credit'=>100]])===true);
  t('August is closed',periodStatus($s,'2026-08-20')==='closed');
  t('September is open',periodStatus($s,'2026-09-20')==='open');
  t('Owner can approve',roleCan('group_owner','journal.approve'));
  t('Entity admin cannot approve',!roleCan('entity_admin','journal.approve'));
  $entityUser=['id'=>3,'name'=>'Entity Admin Hotel','role'=>'entity_admin','company_id'=>1];$scoped=scopeStoreForUser($s,$entityUser);t('Entity admin dataset scoped',count($scoped['companies'])===1 && (int)$scoped['companies'][0]['id']===1);
  t('Auditor can view report',roleCan('auditor','report.view'));
  $ar=arapSummary($s);t('AR summary positive',$ar['receivable']>0);t('AP summary positive',$ar['payable']>0);t('Aging buckets complete',count($ar['aging'])===5);
  $br=bankReconciliationSummary($s);t('Bank feed has unmatched',$br['unmatched']>0);t('Bank match rate bounded',$br['match_rate']>=0&&$br['match_rate']<=100);
  $ap=approvalSummary($s);t('Approval queue seeded',$ap['count']>=1);t('Approval threshold configured',($s['meta']['approval_threshold']??0)===25000000);
  t('Currency base IDR exists',($s['currencies'][0]['code']??'')==='IDR');
  // Stateful gates using isolated demo store and automatic restore.
  saveStore($s);$_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';
  $before=count(loadStore()['entries']);
  $small=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA small journal','amount'=>1000000,'debit_account_id'=>1,'credit_account_id'=>6]);
  t('Small journal posts directly',empty($small['approval_required'])&&count(loadStore()['entries'])===$before+1);
  $large=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA large journal','amount'=>30000000,'debit_account_id'=>1,'credit_account_id'=>6]);
  t('Large journal requires approval',!empty($large['approval_required']));
  $aid=(int)($large['approval']['id']??0);$decided=decideApproval($aid,'approved');t('Approved request creates posted journal',!empty($decided['posted_entry']['id']));
  $closedBlocked=false;try{postJournal(['company_id'=>1,'date'=>'2026-08-25','description'=>'QA closed period','amount'=>1000000,'debit_account_id'=>1,'credit_account_id'=>6]);}catch(Throwable $e){$closedBlocked=str_contains($e->getMessage(),'ditutup');}
  t('Closed period blocks posting',$closedBlocked);
  $_SESSION['user_id']=4;$_SESSION['user_name']='Internal Auditor';
  $roleBlocked=false;try{postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA unauthorized','amount'=>1000000,'debit_account_id'=>1,'credit_account_id'=>6]);}catch(Throwable $e){$roleBlocked=str_contains($e->getMessage(),'Akses ditolak');}
  t('Auditor blocked from posting',$roleBlocked);
  $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';
  $s=loadStore();$origCount=count($s['entries']);$rev=reverseJournal(1,'QA reversal');$after=loadStore();
  t('Controlled reversal creates opposite journal',!empty($rev['reversal_of'])&&count($after['entries'])===$origCount+1);
  $orig=findEntry($after,1);t('Original journal linked to reversal',!empty($orig['reversed_by']));
  $doubleReverse=false;try{reverseJournal(1,'QA double reversal');}catch(Throwable $e){$doubleReverse=str_contains($e->getMessage(),'sudah direversal');}
  t('Double reversal blocked',$doubleReverse);
  $beforeInv=arapSummary(loadStore())['receivable'];$pay=allocateInvoicePayment(1,1000000,'2026-09-25','bank','QA-PAY');$afterInv=arapSummary(loadStore())['receivable'];
  t('AR payment allocation reduces receivable',!empty($pay['journal_id'])&&abs(($beforeInv-$afterInv)-1000000)<0.01);
  $tmp=loadStore();$tmp['invoices'][]=['id'=>99,'company_id'=>1,'type'=>'receivable','number'=>'AR-QA-LARGE','party'=>'QA Large','issue_date'=>'2026-09-25','due_date'=>'2026-10-25','amount'=>40000000,'paid'=>0,'status'=>'open'];saveStore($tmp);$_SESSION['user_id']=3;$_SESSION['user_name']='Entity Admin Hotel';$blockedLargePay=false;try{allocateInvoicePayment(99,30000000,'2026-09-25','bank','QA-BIG');}catch(Throwable $e){$blockedLargePay=str_contains($e->getMessage(),'batas approval');}
  t('Entity admin cannot bypass payment approval',$blockedLargePay);
  $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';
  $tax=calculateTax(111000,11,true);t('Inclusive tax calculation correct',abs($tax['tax']-11000)<0.02&&abs($tax['base']-100000)<0.02);
  $elim=createElimination(1,2,100000000,'2026-09','QA intercompany elimination');t('Intercompany elimination recorded',($elim['status']??'')==='posted'&&eliminationSummary(loadStore())['count']>=1);
  $fx=recordFxEvent(1,'2026-09-25','USD',1000,16800,16900,'QA FX');t('FX gain/loss calculated',abs((float)$fx['gain_loss']-100000)<0.01);
  $imp=importBankCsv(1,"date,description,amount,direction\n2026-09-25,QA BANK IMPORT,1250000,in\n");t('Bank CSV import validated',$imp['count']===1&&($imp['rows'][0]['status']??'')==='unmatched');
  $bypass=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA external override attempt','amount'=>30000000,'debit_account_id'=>1,'credit_account_id'=>6,'approved_override'=>1]);t('External approval override cannot bypass queue',!empty($bypass['approval_required']));
  $badBank=false;try{reconcileBank(3,2);}catch(Throwable $e){$badBank=str_contains($e->getMessage(),'Nominal/direction')||str_contains($e->getMessage(),'badan usaha');}t('Bank reconciliation rejects mismatched journal',$badBank);
  $dup1=importBankCsv(1,"date,description,amount,direction\n2026-09-25,QA DUP BANK,222000,in\n");$dup2=importBankCsv(1,"date,description,amount,direction\n2026-09-25,QA DUP BANK,222000,in\n");t('Bank CSV duplicate is skipped',$dup1['count']===1&&$dup2['count']===0&&$dup2['skipped']===1);
  $elimClosed=false;try{createElimination(1,2,5000000,'2026-08','QA closed elimination');}catch(Throwable $e){$elimClosed=str_contains($e->getMessage(),'ditutup');}t('Closed period blocks intercompany elimination',$elimClosed);
  $badFx=false;try{recordFxEvent(1,'2026-09-25','XYZ',100,100,101,'QA unknown FX');}catch(Throwable $e){$badFx=str_contains($e->getMessage(),'tidak valid');}t('Unknown FX currency rejected',$badFx);
  $closePending=false;try{closePeriod(2026,9);}catch(Throwable $e){$closePending=str_contains($e->getMessage(),'approval jurnal pending');}t('Period close blocked by pending approval',$closePending);
  $dash=dashboardData();$lastTrend=end($dash['trend']);t('Dashboard trend is ledger-derived',abs(($lastTrend['revenue']??0)-($dash['metrics']['revenue']/1000000))<0.01);
  $sc=scopeStoreForUser(loadStore(),['id'=>3,'name'=>'Entity Admin Hotel','role'=>'entity_admin','company_id'=>1]);$foreignLeak=array_filter($sc['fx_events']??[],fn($r)=>(int)$r['company_id']!==1);t('Entity scope covers FX data',count($foreignLeak)===0);
  $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';
  $company=createCompany(['code'=>'QA5','name'=>'QA Fifth Entity','business_type'=>'Testing']);t('Company CRUD creates entity',!empty($company['id'])&&$company['code']==='QA5');
  $user=createUserAccount(['name'=>'QA Viewer User','email'=>'qa-viewer@nexa.local','role'=>'viewer','password'=>'StrongPassword!2026']);t('User CRUD creates hashed account',!empty($user['id'])&&$user['role']==='viewer');
  $budget=createBudget(['company_id'=>1,'year'=>2026,'period'=>9,'account_id'=>7,'amount'=>12345678]);t('Budget CRUD persists amount',(float)($budget['budget']??$budget['amount']??0)===12345678.0);
  $asset=createAsset(['company_id'=>1,'name'=>'QA Future Asset','category'=>'Peralatan','acquisition_date'=>'2027-01-01','cost'=>12000000,'useful_life_months'=>24,'residual_value'=>0]);t('Future asset has no premature depreciation',abs((float)$asset['book']-12000000)<0.01);
  $bank=createBankAccount(['company_id'=>1,'account_id'=>1,'bank_name'=>'QA Bank','account_name'=>'QA Hotel','account_number'=>'123456789012']);t('Bank account CRUD persists mapping',!empty($bank['id'])&&(int)$bank['account_id']===1);
  $masked=maskAccountNumber('123456789012');t('Bank account masking hides leading digits',!str_contains($masked,'12345678')&&str_ends_with($masked,'9012'));
  $tb=trialBalance(loadStore(),'2026-09');$td=array_sum(array_column($tb,'debit'));$tc=array_sum(array_column($tb,'credit'));t('Trial balance debit equals credit',abs($td-$tc)<0.01);
  $bs=balanceSheetSummary(loadStore());t('Balance sheet exposes equation difference',array_key_exists('difference',$bs));
  $_SESSION['user_id']=3;$_SESSION['user_name']='Entity Admin Hotel';$limited=dashboardData();t('Entity admin does not receive all user directory',count($limited['users'])===1);
  $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';saveStore(demoSeed());$seed2=loadStore();$beforeDaily=count($seed2['entries']);$beforeDailyMetrics=metrics($seed2,1);$daily=postDailyIncome(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA pendapatan harian','income_lines'=>[['category_id'=>1,'amount'=>8000000],['category_id'=>2,'amount'=>2000000]],'payment_lines'=>[['account_id'=>1,'label'=>'Kas & Bank','amount'=>10000000]]]);$afterDaily=loadStore();$lastEntry=end($afterDaily['entries']);t('Daily income posts multi-line balanced journal',empty($daily['approval_required'])&&count($afterDaily['entries'])===$beforeDaily+1&&entryBalanced($lastEntry['lines'])&&($lastEntry['source']??'')==='daily_income');
  $dm=metricsForPeriod($afterDaily,'2026-09',1);$afterDailyMetrics=metrics($afterDaily,1);t('Daily income updates revenue and cash',abs(($afterDailyMetrics['revenue']-$beforeDailyMetrics['revenue'])-10000000)<0.01&&abs(($afterDailyMetrics['cash']-$beforeDailyMetrics['cash'])-10000000)<0.01);
  $badDaily=false;try{postDailyIncome(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA bad daily','income_lines'=>[['category_id'=>1,'amount'=>1000000]],'payment_lines'=>[['account_id'=>1,'label'=>'Kas','amount'=>900000]]]);}catch(Throwable $e){$badDaily=str_contains($e->getMessage(),'harus sama');}t('Daily income rejects unbalanced totals',$badDaily);
  $bigDaily=postDailyIncome(['company_id'=>1,'date'=>'2026-09-25','description'=>'QA daily approval','income_lines'=>[['category_id'=>1,'amount'=>30000000]],'payment_lines'=>[['account_id'=>1,'label'=>'Bank','amount'=>30000000]]]);t('Daily income respects approval threshold',!empty($bigDaily['approval_required']));
  $dailyApproval=decideApproval((int)$bigDaily['approval']['id'],'approved');t('Daily income approval creates posted journal',($dailyApproval['posted_entry']['source']??'')==='daily_income');
  $cat=createIncomeCategory(['company_id'=>1,'code'=>'SPA','name'=>'Spa & Wellness','revenue_account_id'=>6]);t('Dynamic income category persists',!empty($cat['id'])&&$cat['name']==='Spa & Wellness');
  $recent=dailyIncomeRecent(loadStore(),10);t('Daily income recent list available',count($recent)>=2);
}finally{
  if($original!==null)file_put_contents(storagePath(),$original,LOCK_EX); else @unlink(storagePath());
}
echo"\nResult: $pass passed, $fail failed\n";exit($fail?1:0);
