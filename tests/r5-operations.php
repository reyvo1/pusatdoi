<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=true');
require __DIR__.'/../lib/bootstrap.php';
$orig=is_file(storagePath())?file_get_contents(storagePath()):null;$pass=$fail=0;
function ok5(bool $c,string $m):void{global $pass,$fail;echo ($c?'PASS  ':'FAIL  ').$m."\n";$c?$pass++:$fail++;}
try{
    $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';saveStore(demoSeed());
    $s=loadStore();
    ok5(isset($s['expense_categories'],$s['cash_forecast_items'],$s['recurring_journal_templates']),'R5 seed exposes finance operations modules');
    $cat=r5CreateExpenseCategory(['company_id'=>1,'code'=>'TRAVEL','name'=>'Perjalanan Dinas','expense_account_id'=>7,'tax_profile_id'=>'']);ok5(($cat['code']??'')==='TRAVEL','Expense category is runtime-managed');
    $before=count(loadStore()['entries']);
    $expense=r5PostDailyExpense(['company_id'=>1,'branch_id'=>1,'department_id'=>1,'date'=>'2026-09-26','description'=>'R5 UAT daily expense','currency'=>'IDR','exchange_rate'=>1,'expense_lines'=>[['category_id'=>1,'amount'=>1000000,'tax_profile_id'=>2,'tax_mode'=>'exclusive','description'=>'Utilitas']], 'payment_lines'=>[['account_id'=>1,'amount'=>1110000,'method'=>'bank','reference'=>'EXP-R5-001']]],true);
    $s=loadStore();ok5(count($s['entries'])===$before+1&&($expense['amount']??0)===1110000,'Daily expense posts balanced expense + input tax + payment');
    ok5(count(array_filter($s['tax_transactions'],fn($r)=>($r['direction']??'')==='input'))>=1,'Daily expense writes input-tax transaction');
    $cash2=r4CreateAccount(['company_id'=>1,'code'=>'1103','name'=>'Kas Operasional Hotel','type'=>'asset','is_cash_bank'=>1]);
    $tr=r5BankTransfer(['company_id'=>1,'from_account_id'=>1,'to_account_id'=>$cash2['id'],'date'=>'2026-09-26','amount'=>500000,'fee_amount'=>10000,'fee_account_id'=>7,'reference'=>'TRF-R5-001','description'=>'Pemindahan dana kas'],true);
    ok5(($tr['status']??'')==='posted'&&!empty($tr['journal_id']),'Bank transfer posts one balanced journal with optional fee');
    $ob=r5PostOpeningBalance(['company_id'=>4,'opening_date'=>'2025-01-01','lines'=>[['account_id'=>1,'debit'=>1500000,'credit'=>0,'description'=>'Kas awal'],['account_id'=>5,'debit'=>0,'credit'=>1500000,'description'=>'Modal awal']]]);ok5(($ob['status']??'')==='posted','Opening balance posts and registers yearly batch');
    $dup=false;try{r5PostOpeningBalance(['company_id'=>4,'opening_date'=>'2025-01-01','lines'=>[['account_id'=>1,'debit'=>1,'credit'=>0],['account_id'=>5,'debit'=>0,'credit'=>1]]]);}catch(Throwable){$dup=true;}ok5($dup,'Duplicate opening balance for same fiscal year is blocked');
    $tpl=r5CreateRecurringTemplate(['company_id'=>2,'name'=>'Sewa bulanan gudang','description'=>'Recurring sewa gudang','frequency'=>'monthly','next_run_date'=>'2026-09-26','auto_reverse'=>1,'reverse_after_days'=>1,'lines'=>[['account_id'=>7,'debit'=>200000,'credit'=>0,'description'=>'Beban sewa'],['account_id'=>1,'debit'=>0,'credit'=>200000,'description'=>'Kas']]]);$rr=r5RunRecurring((int)$tpl['id']);ok5(!empty($rr['id'])&&($rr['reversal_due_date']??'')==='2026-09-27','Recurring template generates posted journal and reversal schedule');$rev=r5ProcessRecurringReversals('2026-09-27',true);ok5(($rev['processed']??0)===1,'Recurring auto-reversal worker posts scheduled reversal');
    $fc=r5CreateForecast(['company_id'=>1,'branch_id'=>1,'forecast_date'=>'2026-10-20','direction'=>'outflow','description'=>'Renovasi minor','amount'=>25000000,'probability_pct'=>80]);$summary=(new \Nexa\Treasury\CashForecastService())->summarize(loadStore()['cash_forecast_items'],'2026-10-01','2026-10-31');ok5(!empty($fc['id'])&&$summary['weighted_outflow']>0,'Cash forecast tracks probability-weighted liquidity plan');
    $beforeMetrics=metrics(loadStore(),3);$beforeBs=(new \Nexa\Legacy\EnterpriseFacade())->balanceSheet(loadStore(),null,3);$ye=r5YearEndClose(['company_id'=>3,'fiscal_year'=>2026,'retained_earnings_account_id'=>5]);$afterMetrics=metrics(loadStore(),3);ok5(($ye['status']??'')==='posted'&&!empty($ye['journal_id']),'Year-end close transfers P&L to retained earnings');ok5(abs($beforeMetrics['profit']-$afterMetrics['profit'])<0.01,'Historical P&L remains visible after year-end closing');$afterBs=(new \Nexa\Legacy\EnterpriseFacade())->balanceSheet(loadStore(),null,3);ok5(abs($beforeBs['equity']-$afterBs['equity'])<0.01,'Year-end close does not double-count retained earnings in balance sheet');
    $api=file_get_contents(__DIR__.'/../api.php');$js=file_get_contents(__DIR__.'/../assets/r5-ui.js');preg_match_all("/api\('([^']+)'/",$js,$uiActions);preg_match_all("/action==='([^']+)'/",$api,$apiActions);ok5(array_diff(array_unique($uiActions[1]),array_unique($apiActions[1]))===[],'Every R5 UI action has backend route');foreach(['r5-post-daily-expense','r5-opening-balance','r5-bank-transfer','r5-create-forecast','r5-create-recurring','r5-run-recurring','r5-process-recurring-reversals','r5-year-end-close'] as$a)ok5(str_contains($api,"action==='".$a."'"),'API route wired: '.$a);
}catch(Throwable $e){echo 'FATAL '.$e::class.': '.$e->getMessage()."\n";$fail++;}
finally{if($orig===null)@unlink(storagePath());else file_put_contents(storagePath(),$orig,LOCK_EX);}
echo "R5 operations result: $pass passed, $fail failed\n";exit($fail?1:0);
