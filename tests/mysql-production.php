<?php
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../lib/bootstrap.php';
$pass=0;$fail=0;
function mt(string $name,bool $ok):void{global$pass,$fail;echo($ok?'PASS':'FAIL')."  $name\n";$ok?$pass++:$fail++;}
$pdo=db();
$cleanup=['audit_logs','invoice_payments','bank_feed','journal_approvals'];foreach($cleanup as $t)$pdo->exec('DELETE FROM `'.$t.'`');$pdo->exec('UPDATE journal_entries SET reversed_by=NULL,reversal_of=NULL');foreach(['journal_lines','journal_entries','invoices','budgets','fixed_assets','bank_accounts','integration_staging','fx_events','intercompany_eliminations','fiscal_periods','login_attempts','users'] as $t)$pdo->exec('DELETE FROM `'.$t.'`');
// Keep seeded companies/accounts/currencies/tax profiles, then establish users and an open period.
$hash=password_hash('ProductionTest!2026',PASSWORD_DEFAULT);
$st=$pdo->prepare("INSERT INTO users(name,email,password_hash,role,company_id,is_active) VALUES(?,?,?,?,?,1)");
$st->execute(['CI Owner','owner-ci@nexa.local',$hash,'group_owner',null]);$owner=(int)$pdo->lastInsertId();
$st->execute(['CI Entity','entity-ci@nexa.local',$hash,'entity_admin',1]);$entity=(int)$pdo->lastInsertId();
$pdo->exec("INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES(2026,9,'open') ON DUPLICATE KEY UPDATE status='open',closed_at=NULL,closed_by=NULL");
$_SESSION['user_id']=$owner;$_SESSION['user_name']='CI Owner';
$s=loadStore();mt('Production DB loads companies',count($s['companies'])>=4);mt('Production DB loads accounts',count($s['accounts'])>=10);
$cash=(int)accountIdByCode($s,1,'1101');$rev=(int)accountIdByCode($s,1,'4101');$exp=(int)accountIdByCode($s,1,'5101');
$small=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'CI direct revenue','amount'=>1000000,'debit_account_id'=>$cash,'credit_account_id'=>$rev]);mt('Production journal posts',empty($small['approval_required'])&&!empty($small['id']));
$large=postJournal(['company_id'=>1,'date'=>'2026-09-25','description'=>'CI approval revenue','amount'=>30000000,'debit_account_id'=>$cash,'credit_account_id'=>$rev,'approved_override'=>1]);mt('External override still queues approval',!empty($large['approval_required']));
$dec=decideApproval((int)$large['approval']['id'],'approved');mt('Approval posts journal atomically',!empty($dec['posted_entry']['id']));
$pdo->prepare("INSERT INTO invoices(company_id,invoice_type,invoice_no,party_name,issue_date,due_date,amount,paid_amount,status,currency) VALUES(1,'receivable','CI-AR-001','CI Customer','2026-09-25','2026-10-25',5000000,0,'open','IDR')")->execute();$invoice=(int)$pdo->lastInsertId();
$pay=allocateInvoicePayment($invoice,1000000,'2026-09-25','bank','CI-PAY-001');mt('Production AR allocation posts atomically',!empty($pay['journal_id']));
$revEntry=reverseJournal((int)$small['id'],'CI reversal');mt('Production reversal preserves audit chain',(int)$revEntry['reversal_of']===(int)$small['id']);
$company=createCompany(['code'=>'CI5','name'=>'CI Fifth Entity','business_type'=>'Testing']);mt('Production company CRUD',!empty($company['id']));
$user=createUserAccount(['name'=>'CI Viewer','email'=>'viewer-ci@nexa.local','role'=>'viewer','password'=>'ProductionViewer!2026']);mt('Production user CRUD',!empty($user['id']));
$budget=createBudget(['company_id'=>1,'year'=>2026,'period'=>9,'account_id'=>$exp,'amount'=>5000000]);mt('Production budget CRUD',(float)$budget['amount']===5000000.0);
$asset=createAsset(['company_id'=>1,'name'=>'CI Generator','category'=>'Equipment','acquisition_date'=>'2026-09-25','cost'=>12000000,'useful_life_months'=>36,'residual_value'=>0]);mt('Production asset CRUD',!empty($asset['id']));
$bank=createBankAccount(['company_id'=>1,'account_id'=>$cash,'bank_name'=>'CI Bank','account_name'=>'CI Hotel','account_number'=>'9876543210']);mt('Production bank CRUD',!empty($bank['id']));
$constraint=false;try{$pdo->prepare("INSERT INTO journal_lines(journal_id,account_id,debit,credit) VALUES(?,?,0,0)")->execute([(int)$dec['posted_entry']['id'],$cash]);}catch(Throwable $e){$constraint=true;}mt('DB constraint rejects zero-value journal line',$constraint);
$_SESSION['user_id']=$entity;$_SESSION['user_name']='CI Entity';$scoped=dashboardData();mt('Entity admin company scope enforced',count($scoped['companies'])===1&&(int)$scoped['companies'][0]['id']===1);mt('Entity admin user directory restricted',count($scoped['users'])===1);
$_SESSION['user_id']=$owner;$_SESSION['user_name']='CI Owner';$dash=dashboardData();$last=end($dash['trend']);mt('Production charts derive from ledger',abs(($last['revenue']??0)-($dash['metrics']['revenue']/1000000))<0.01);
echo "\nResult: $pass passed, $fail failed\n";exit($fail?1:0);
