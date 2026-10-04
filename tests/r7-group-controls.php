<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=true');
require __DIR__.'/../lib/bootstrap.php';
$orig=is_file(storagePath())?file_get_contents(storagePath()):null;$pass=$fail=0;
function ok7(bool $c,string $m):void{global $pass,$fail;echo ($c?'PASS  ':'FAIL  ').$m."\n";$c?$pass++:$fail++;}
try{
    $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';saveStore(demoSeed());
    $before=count(loadStore()['entries']);
    $adv=r7IssueAdvance(['company_id'=>1,'branch_id'=>1,'department_id'=>1,'employee_name'=>'Budi Operasional','advance_date'=>'2026-09-26','due_date'=>'2026-09-30','amount'=>10000000,'advance_account_id'=>2,'cash_account_id'=>1,'description'=>'Advance operasional'],true);
    ok7(($adv['status']??'')==='open'&&!empty($adv['issue_journal_id']),'Employee advance issues posted journal');
    $settle=r7SettleAdvance(['advance_id'=>$adv['id'],'settlement_date'=>'2026-09-26','expense_amount'=>8000000,'refund_amount'=>2000000,'reimbursement_amount'=>0,'expense_account_id'=>7,'cash_account_id'=>1,'description'=>'Settlement advance']);
    ok7(($settle['status']??'')==='settled','Employee advance settlement clears outstanding');
    $loan=r7CreateLoan(['company_id'=>1,'lender_name'=>'Bank Test','reference_no'=>'LN-001','start_date'=>'2026-09-26','maturity_date'=>'2027-09-26','principal'=>20000000,'annual_interest_rate'=>8.5,'liability_account_id'=>4,'cash_account_id'=>1,'interest_expense_account_id'=>7],true);
    ok7(($loan['status']??'')==='active'&&(int)$loan['outstanding_principal']===20000000,'Loan disbursement posts facility and journal');
    $pay=r7PayLoan(['loan_id'=>$loan['id'],'payment_date'=>'2026-09-26','principal_amount'=>5000000,'interest_amount'=>1000000,'cash_account_id'=>1,'reference_no'=>'PAY-LN-01'],true);
    ok7((int)$pay['outstanding_principal']===15000000,'Loan payment reduces principal and posts interest');
    $eq=r7PostEquity(['company_id'=>1,'transaction_type'=>'capital_contribution','transaction_date'=>'2026-09-26','amount'=>10000000,'cash_account_id'=>1,'equity_account_id'=>5,'reference_no'=>'CAP-001','description'=>'Tambahan modal'],true);
    ok7(($eq['transaction_type']??'')==='capital_contribution'&&!empty($eq['journal_id']),'Capital contribution posts ledger movement');
    $sc=r7CreateBudgetScenario(['company_id'=>1,'fiscal_year'=>2027,'code'=>'BASE27','name'=>'Base 2027','scenario_type'=>'base_case']);
    $line=r7SaveBudgetScenarioLine(['scenario_id'=>$sc['id'],'account_id'=>6,'period'=>1,'amount'=>150000000]);
    ok7((int)$line['amount']===150000000,'Budget scenario line persists target');
    $cmp=(new \Nexa\Planning\BudgetScenarioService())->compare([['amount'=>100]],[['amount'=>120]]);ok7((int)$cmp['variance']===20,'Budget scenario comparison calculates variance');
    $s=loadStore();$notes=r7Notifications($s);ok7(count($notes)>=3,'Notification center derives live financial alerts');
    $k=r7ManagementKpis($s);ok7(array_key_exists('current_ratio',$k)&&array_key_exists('dso_days',$k),'Management KPI exposes liquidity and collection ratios');
    ok7(count($s['entries'])>=$before+5,'R7 workflows create multiple balanced ledger entries');
    $allBalanced=true;foreach($s['entries'] as$e)if(!entryBalanced($e['lines'])){$allBalanced=false;break;}ok7($allBalanced,'All R7 generated journals remain balanced');
    // Scope regression: create second entity equity, then assert entity admin cannot see it.
    r7PostEquity(['company_id'=>2,'transaction_type'=>'capital_contribution','transaction_date'=>'2026-09-26','amount'=>1000000,'cash_account_id'=>1,'equity_account_id'=>5,'description'=>'Retail capital'],true);
    $s=loadStore();$admin=null;foreach($s['users'] as$u)if((int)$u['id']===3)$admin=$u;$scoped=scopeStoreForUser($s,$admin);$bad=array_filter($scoped['equity_transactions']??[],fn($r)=>(int)$r['company_id']!==1);ok7(!$bad,'Entity admin scope filters R7 financial controls');
    $api=file_get_contents(__DIR__.'/../api.php');$js=file_get_contents(__DIR__.'/../assets/r7-ui.js');preg_match_all("/api\('([^']+)'/",$js,$ua);preg_match_all("/action==='([^']+)'/",$api,$aa);ok7(array_diff(array_unique($ua[1]),array_unique($aa[1]))===[],'Every R7 UI action has backend route');
    foreach(['r7-issue-advance','r7-settle-advance','r7-create-loan','r7-pay-loan','r7-post-equity','r7-create-budget-scenario','r7-save-budget-line'] as$a)ok7(str_contains($api,"action==='".$a."'"),'API route wired: '.$a);
}catch(Throwable $e){echo 'FATAL '.$e::class.': '.$e->getMessage()."\n";$fail++;}
finally{if($orig===null)@unlink(storagePath());else file_put_contents(storagePath(),$orig,LOCK_EX);}
echo "R7 group controls result: $pass passed, $fail failed\n";exit($fail?1:0);
