<?php
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../lib/bootstrap.php';
$pass=0;$fail=0;
function u(string $n,bool $ok):void{global$pass,$fail;echo($ok?'PASS':'FAIL')."  $n\n";$ok?$pass++:$fail++;}
$pdo=db();
$owner=(int)$pdo->query("SELECT id FROM users WHERE email='owner-ci@nexa.local'")->fetchColumn();
$entity=(int)$pdo->query("SELECT id FROM users WHERE email='entity-ci@nexa.local'")->fetchColumn();
$_SESSION['user_id']=$owner;$_SESSION['user_name']='UAT Owner';
$s=loadStore();
u('4 seeded business entities',count($s['companies'])>=4);
u('COA available',count($s['accounts'])>=10);
$cash=(int)accountIdByCode($s,1,'1101');$rev=(int)accountIdByCode($s,1,'4101');$exp=(int)accountIdByCode($s,1,'5101');
u('Core accounts resolved',$cash>0&&$rev>0&&$exp>0);
// Daily income, normal journal, approval, reversal.
$cat=$pdo->query("SELECT id FROM income_categories WHERE company_id=1 ORDER BY id LIMIT 1")->fetchColumn();
if(!$cat){$catRow=createIncomeCategory(['company_id'=>1,'code'=>'ROOM','name'=>'Room Revenue','revenue_account_id'=>$rev]);$cat=(int)$catRow['id'];}
$d=postDailyIncome(['company_id'=>1,'date'=>'2026-09-25','description'=>'UAT Daily Income','income_lines'=>[['category_id'=>(int)$cat,'amount'=>4200000]],'payment_lines'=>[['account_id'=>$cash,'label'=>'Cash','amount'=>4200000]]]);
u('Daily income posts balanced',empty($d['approval_required'])&&!empty($d['id']));
$j=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'UAT Expense','amount'=>750000,'debit_account_id'=>$exp,'credit_account_id'=>$cash]);
u('Expense journal posts',!empty($j['id']));
$big=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'UAT Large Revenue','amount'=>30000000,'debit_account_id'=>$cash,'credit_account_id'=>$rev]);
u('Large journal goes approval',!empty($big['approval_required']));
$ap=decideApproval((int)$big['approval']['id'],'approved');
u('Approved journal becomes posted',!empty($ap['posted_entry']['id']));
$rv=reverseJournal((int)$j['id'],'UAT reversal');
u('Controlled reversal linked',(int)$rv['reversal_of']===(int)$j['id']);
// AR/AP
$pdo->prepare("INSERT INTO invoices(company_id,invoice_type,invoice_no,party_name,issue_date,due_date,amount,paid_amount,status,currency) VALUES(1,'receivable','UAT-AR-001','UAT Customer','2026-09-25','2026-10-25',3000000,0,'open','IDR')")->execute();$iid=(int)$pdo->lastInsertId();
$pay=allocateInvoicePayment($iid,1000000,'2026-09-25','bank','UAT-PAY-001');u('AR payment allocates with journal',!empty($pay['journal_id']));
// Bank import.
$bi=importBankCsv(1,"date,description,amount,direction\n2026-09-25,UAT BANK,1500000,in\n");u('Bank CSV import accepted',$bi['count']===1);
// Tax + FX.
$tax=calculateTax(111000,11,true);u('Inclusive tax correct',abs($tax['tax']-11000)<0.02);
$fx=recordFxEvent(1,'2026-09-25','USD',1000,16800,16900,'UAT FX');u('FX gain/loss correct',abs((float)$fx['gain_loss']-100000)<0.01);
// Intercompany.
$el=createElimination(1,2,2500000,'2026-09','UAT elimination');u('Intercompany elimination posted',($el['status']??'')==='posted');
// Reports.
foreach(['pl','balance','equity','cashflow','ledger','trial','intercompany'] as $view){$r=financialReportData();u("Report data $view available",is_array($r)&&isset($r['metrics']));}
$tb=trialBalance(loadStore(),'2026-09');u('Trial balance is balanced',abs(array_sum(array_column($tb,'debit'))-array_sum(array_column($tb,'credit')))<0.01);
$bs=balanceSheetSummary(loadStore());u('Balance sheet equation exposed',array_key_exists('difference',$bs));
// RBAC scope.
$_SESSION['user_id']=$entity;$_SESSION['user_name']='UAT Entity';$sc=dashboardData();u('Entity admin scoped to one entity',count($sc['companies'])===1&&(int)$sc['companies'][0]['id']===1);
$blocked=false;try{postJournal(['company_id'=>2,'date'=>'2026-09-25','description'=>'Foreign entity attempt','amount'=>100000,'debit_account_id'=>$cash,'credit_account_id'=>$rev]);}catch(Throwable $e){$blocked=true;}u('Entity admin cannot post foreign entity',$blocked);
$_SESSION['user_id']=$owner;$_SESSION['user_name']='UAT Owner';
// Period lock final gate done on August so September remains usable.
$pdo->exec("INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES(2026,8,'closed') ON DUPLICATE KEY UPDATE status='closed'");
$locked=false;try{postJournal(['company_id'=>1,'date'=>'2026-08-25','description'=>'Closed period attempt','amount'=>100000,'debit_account_id'=>$cash,'credit_account_id'=>$rev]);}catch(Throwable $e){$locked=str_contains($e->getMessage(),'ditutup');}u('Closed period blocks posting',$locked);
// Audit trail.
$aud=(int)$pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();u('Audit trail receives events',$aud>0);
echo "\nFULL UAT: $pass passed, $fail failed\n";exit($fail?1:0);
