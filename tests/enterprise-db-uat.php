<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../src/autoload.php';

$host=getenv('NEXA_DB_HOST')?:'127.0.0.1';
$port=getenv('NEXA_DB_PORT')?:'3306';
$name=getenv('NEXA_DB_NAME')?:'nexa_group_finance';
$user=getenv('NEXA_DB_USER')?:'root';
$pass=getenv('NEXA_DB_PASS')?:'';
$pdo=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$ok=0;$bad=0;
function edb(string $name,bool $pass):void{global $ok,$bad;echo($pass?'PASS':'FAIL')."  $name\n";$pass?$ok++:$bad++;}
function scalar(PDO $pdo,string $sql,array $args=[]):mixed{$s=$pdo->prepare($sql);$s->execute($args);return$s->fetchColumn();}

try{
    // Organization / cost-center hierarchy.
    $pdo->exec("INSERT INTO branches(company_id,code,name,timezone,is_active) VALUES(1,'UAT-R3','Enterprise UAT Branch','Asia/Makassar',1) ON DUPLICATE KEY UPDATE name=VALUES(name)");
    $branch=(int)scalar($pdo,"SELECT id FROM branches WHERE company_id=1 AND code='UAT-R3'");
    $pdo->prepare("INSERT INTO departments(company_id,branch_id,code,name,cost_center_code,profit_center_code,is_active) VALUES(1,?,'UATDEP','Enterprise UAT Dept','CC-UAT','PC-UAT',1) ON DUPLICATE KEY UPDATE branch_id=VALUES(branch_id),name=VALUES(name)")->execute([$branch]);
    $department=(int)scalar($pdo,"SELECT id FROM departments WHERE company_id=1 AND code='UATDEP'");
    edb('Branch and department persist with entity hierarchy',$branch>0&&$department>0);

    // Journal batch + branch/department journal linkage.
    $owner=(int)scalar($pdo,"SELECT id FROM users WHERE role='group_owner' ORDER BY id LIMIT 1");
    $pdo->prepare("INSERT INTO journal_batches(company_id,branch_id,batch_no,batch_date,source_type,status,total_debit,total_credit,created_by) VALUES(1,?,'BAT-UAT-R3','2026-09-25','uat','posted',123456,123456,?) ON DUPLICATE KEY UPDATE status='posted',total_debit=VALUES(total_debit),total_credit=VALUES(total_credit)")->execute([$branch,$owner]);
    $batch=(int)scalar($pdo,"SELECT id FROM journal_batches WHERE company_id=1 AND batch_no='BAT-UAT-R3'");
    $pdo->prepare("INSERT INTO journal_entries(company_id,branch_id,department_id,batch_id,journal_no,journal_date,description,source_type,source_ref,external_ref,status,posted_at,created_by) VALUES(1,?,?,?,'JRN-UAT-R3-DB','2026-09-25','Enterprise DB UAT','uat','DB-UAT','EXT-UAT-R3','posted',NOW(),?) ON DUPLICATE KEY UPDATE department_id=VALUES(department_id),batch_id=VALUES(batch_id),external_ref=VALUES(external_ref)")->execute([$branch,$department,$batch,$owner]);
    $journal=(int)scalar($pdo,"SELECT id FROM journal_entries WHERE journal_no='JRN-UAT-R3-DB'");
    if((int)scalar($pdo,"SELECT COUNT(*) FROM journal_lines WHERE journal_id=?",[$journal])===0){
        $st=$pdo->prepare("INSERT INTO journal_lines(journal_id,account_id,description,debit,credit) VALUES(?,?,?,?,?)");
        $st->execute([$journal,1,'Cash',123456,0]);$st->execute([$journal,6,'Revenue',0,123456]);
    }
    edb('Journal batch links branch + department + external reference',$journal>0&&(int)scalar($pdo,"SELECT COUNT(*) FROM journal_entries WHERE id=? AND branch_id=? AND department_id=? AND batch_id=?",[$journal,$branch,$department,$batch])===1);
    edb('Enterprise journal remains double-entry balanced',(float)scalar($pdo,"SELECT ABS(SUM(debit)-SUM(credit)) FROM journal_lines WHERE journal_id=?",[$journal])<0.01);

    // Invoice detail + payment allocation storage.
    $pdo->exec("INSERT INTO invoices(company_id,branch_id,invoice_type,invoice_no,party_name,issue_date,due_date,amount,paid_amount,status,currency,external_ref) VALUES(1,1,'receivable','UAT-R3-INV','Enterprise Customer','2026-09-25','2026-10-25',1110000,110000,'partial','IDR','ERP-INV-001') ON DUPLICATE KEY UPDATE external_ref=VALUES(external_ref)");
    $invoice=(int)scalar($pdo,"SELECT id FROM invoices WHERE invoice_no='UAT-R3-INV'");
    if((int)scalar($pdo,"SELECT COUNT(*) FROM invoice_lines WHERE invoice_id=?",[$invoice])===0){$pdo->prepare("INSERT INTO invoice_lines(invoice_id,account_id,description,quantity,unit_price,tax_profile_id,tax_amount,line_total) VALUES(?,6,'Enterprise service',1,1000000,1,110000,1110000)")->execute([$invoice]);}
    $pdo->prepare("INSERT INTO invoice_payments(company_id,invoice_id,payment_date,amount,method,reference_no,journal_id,created_by) VALUES(1,?,'2026-09-25',110000,'bank','UAT-R3-PAY',?,?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)")->execute([$invoice,$journal,$owner]);
    $payment=(int)scalar($pdo,"SELECT id FROM invoice_payments WHERE reference_no='UAT-R3-PAY'");
    $pdo->prepare("INSERT INTO payment_allocations(payment_id,invoice_id,allocated_amount,created_by) VALUES(?,?,110000,?) ON DUPLICATE KEY UPDATE allocated_amount=VALUES(allocated_amount)")->execute([$payment,$invoice,$owner]);
    edb('Invoice lines and payment allocation persist',(int)scalar($pdo,"SELECT COUNT(*) FROM invoice_lines WHERE invoice_id=?",[$invoice])===1&&(int)scalar($pdo,"SELECT COUNT(*) FROM payment_allocations WHERE payment_id=?",[$payment])===1);

    // Bank statement import + reconciliation session/match.
    $hash=hash('sha256','enterprise-r3-bank-import');
    $pdo->prepare("INSERT INTO bank_statement_imports(company_id,bank_account_id,file_name,file_sha256,row_count,imported_count,skipped_count,imported_by) VALUES(1,1,'uat-r3.csv',?,1,1,0,?) ON DUPLICATE KEY UPDATE imported_count=1")->execute([$hash,$owner]);
    $pdo->exec("INSERT INTO bank_feed(company_id,bank_account_id,txn_date,description,amount,direction,status,journal_id,external_ref) VALUES(1,1,'2026-09-25','ENTERPRISE UAT MATCH',123456,'in','matched',$journal,'UAT-R3-BANK') ON DUPLICATE KEY UPDATE journal_id=VALUES(journal_id),status='matched'");
    $feed=(int)scalar($pdo,"SELECT id FROM bank_feed WHERE external_ref='UAT-R3-BANK'");
    $pdo->prepare("INSERT INTO bank_reconciliation_sessions(company_id,bank_account_id,period,opening_balance,closing_balance,status,prepared_by,reviewed_by,closed_at) VALUES(1,1,'2026-09',0,123456,'closed',?,?,NOW()) ON DUPLICATE KEY UPDATE status='closed',closed_at=NOW()")->execute([$owner,$owner]);
    $session=(int)scalar($pdo,"SELECT id FROM bank_reconciliation_sessions WHERE company_id=1 AND bank_account_id=1 AND period='2026-09'");
    $pdo->prepare("INSERT INTO bank_reconciliation_matches(session_id,bank_feed_id,journal_id,matched_amount,match_type,confidence,matched_by) VALUES(?,?,?,123456,'auto',100,?) ON DUPLICATE KEY UPDATE journal_id=VALUES(journal_id),matched_amount=VALUES(matched_amount)")->execute([$session,$feed,$journal,$owner]);
    edb('Bank import and reconciliation session/match persist',$session>0&&(int)scalar($pdo,"SELECT COUNT(*) FROM bank_reconciliation_matches WHERE session_id=? AND bank_feed_id=?",[$session,$feed])===1);

    // Asset depreciation schedule.
    $asset=(int)scalar($pdo,"SELECT id FROM fixed_assets ORDER BY id LIMIT 1");
    $pdo->prepare("INSERT INTO asset_depreciation_schedule(asset_id,period,depreciation_amount,accumulated_amount,book_value,journal_id,status) VALUES(?,'2026-09',32500000,2600000000,5200000000,?,'posted') ON DUPLICATE KEY UPDATE journal_id=VALUES(journal_id),status='posted'")->execute([$asset,$journal]);
    edb('Asset depreciation schedule links to journal',(int)scalar($pdo,"SELECT COUNT(*) FROM asset_depreciation_schedule WHERE asset_id=? AND period='2026-09' AND journal_id=?",[$asset,$journal])===1);

    // Tax and FX detail stores.
    $pdo->prepare("INSERT INTO tax_transactions(company_id,tax_profile_id,journal_id,transaction_date,tax_base,tax_amount,direction,external_ref) VALUES(1,1,?,'2026-09-25',1000000,110000,'output','UAT-R3-TAX')")->execute([$journal]);
    $pdo->exec("INSERT INTO fx_rates(rate_date,base_currency,quote_currency,rate,source) VALUES('2026-09-25','USD','IDR',16850,'uat') ON DUPLICATE KEY UPDATE rate=VALUES(rate)");
    edb('Tax transaction and FX rate history persist',(int)scalar($pdo,"SELECT COUNT(*) FROM tax_transactions WHERE external_ref='UAT-R3-TAX'")>=1&&(float)scalar($pdo,"SELECT rate FROM fx_rates WHERE rate_date='2026-09-25' AND base_currency='USD' AND quote_currency='IDR' AND source='uat'")===16850.0);

    // Consolidation run + elimination entry.
    $pdo->prepare("INSERT INTO consolidation_runs(period,run_no,status,entity_count,elimination_count,created_by,validated_by) VALUES('2026-09','CONS-UAT-R3','posted',4,1,?,?) ON DUPLICATE KEY UPDATE status='posted',entity_count=4,elimination_count=1")->execute([$owner,$owner]);
    $run=(int)scalar($pdo,"SELECT id FROM consolidation_runs WHERE period='2026-09' AND run_no='CONS-UAT-R3'");
    if((int)scalar($pdo,"SELECT COUNT(*) FROM elimination_entries WHERE consolidation_run_id=? AND reference='UAT-R3-ELIM'",[$run])===0){$pdo->prepare("INSERT INTO elimination_entries(consolidation_run_id,from_company_id,to_company_id,account_id,debit,credit,reference,description) VALUES(?,1,2,9,100000,0,'UAT-R3-ELIM','Enterprise elimination')")->execute([$run]);}
    edb('Consolidation run and elimination entry persist',$run>0&&(int)scalar($pdo,"SELECT COUNT(*) FROM elimination_entries WHERE consolidation_run_id=?",[$run])>=1);

    // Integration inbox/outbox idempotency + settlement.
    $key=hash('sha256','uat-r3-integration-event');
    $pdo->prepare("INSERT INTO integration_events(source,external_ref,idempotency_key,company_id,event_type,occurred_at,status,payload_json) VALUES('uat','EXT-UAT-R3',?,1,'daily_income','2026-09-25 10:00:00','validated',JSON_OBJECT('amount',123456)) ON DUPLICATE KEY UPDATE status='validated'")->execute([$key]);
    $pdo->exec("INSERT INTO integration_outbox(event_type,aggregate_type,aggregate_id,payload_json,status,attempts) VALUES('journal.posted','journal','$journal',JSON_OBJECT('journal_id',$journal),'pending',0)");
    $pdo->prepare("INSERT INTO settlement_batches(company_id,settlement_date,channel,external_ref,gross_amount,fee_amount,net_amount,journal_id,status) VALUES(1,'2026-09-25','OTA','UAT-R3-SETTLE',1000000,25000,975000,?,'posted') ON DUPLICATE KEY UPDATE journal_id=VALUES(journal_id),status='posted'")->execute([$journal]);
    edb('Integration inbox preserves idempotency key',(int)scalar($pdo,"SELECT COUNT(*) FROM integration_events WHERE idempotency_key=?",[$key])===1);
    edb('Integration outbox stores pending delivery',(int)scalar($pdo,"SELECT COUNT(*) FROM integration_outbox WHERE aggregate_type='journal' AND aggregate_id=?",[(string)$journal])>=1);
    edb('Settlement batch enforces gross-fee=net',(int)scalar($pdo,"SELECT COUNT(*) FROM settlement_batches WHERE external_ref='UAT-R3-SETTLE' AND ABS(gross_amount-fee_amount-net_amount)<0.01")===1);

    // Permissions and login security tables are live.
    $pdo->exec("INSERT IGNORE INTO role_permissions(role_code,permission_code) VALUES('auditor','reports.view'),('group_owner','users.manage')");
    $pdo->exec("INSERT INTO login_attempts(email,ip_address,was_successful) VALUES('uat@nexa.local','127.0.0.1',0)");
    edb('Role permission and login security tables writable',(int)scalar($pdo,"SELECT COUNT(*) FROM role_permissions WHERE role_code='auditor'")>=1&&(int)scalar($pdo,"SELECT COUNT(*) FROM login_attempts WHERE email='uat@nexa.local'")>=1);

    // DB constraints: duplicate idempotency must fail.
    $duplicateBlocked=false;
    try{$pdo->prepare("INSERT INTO integration_events(source,external_ref,idempotency_key,company_id,event_type,status,payload_json) VALUES('uat','DUP',?,1,'daily_income','received',JSON_OBJECT())")->execute([$key]);}catch(PDOException){$duplicateBlocked=true;}
    edb('Database unique constraint blocks duplicate integration event',$duplicateBlocked);

    // Final relation/integrity summary.
    $orphans=(int)scalar($pdo,"SELECT COUNT(*) FROM journal_lines jl LEFT JOIN journal_entries je ON je.id=jl.journal_id WHERE je.id IS NULL");
    edb('No orphan journal lines',$orphans===0);

}catch(Throwable $e){
    fwrite(STDERR,'FATAL enterprise DB UAT: '.$e->getMessage()."\n");
    $bad++;
}

echo "\nEnterprise DB UAT: $ok passed, $bad failed\n";
exit($bad?1:0);
