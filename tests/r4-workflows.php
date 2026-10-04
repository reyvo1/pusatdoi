<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=true');
require __DIR__.'/../lib/bootstrap.php';
$orig=is_file(storagePath())?file_get_contents(storagePath()):null;
$pass=$fail=0;
function ok4(bool $c,string $m):void{global $pass,$fail;echo ($c?'PASS  ':'FAIL  ').$m."\n";$c?$pass++:$fail++;}
try{
    $_SESSION['demo_user_id']=1;
    saveStore(demoSeed());
    $s=loadStore();
    // P0 scope fail-closed
    $u=['role'=>'entity_admin','company_id'=>null];$sc=scopeStoreForUser($s,$u);ok4(count($sc['companies'])===0&&count($sc['entries'])===0,'Entity admin without scope fails closed');
    // UI->API contract static
    $js=file_get_contents(__DIR__.'/../assets/r4-ui.js');$api=file_get_contents(__DIR__.'/../api.php');preg_match_all("/api\\('([^']+)'/",$js,$m1);preg_match_all("/action==='([^']+)'/",$api,$m2);$missing=array_values(array_diff(array_unique($m1[1]),array_unique($m2[1])));ok4($missing===[],'Every R4 UI action has backend route');
    ok4(strpos($js,'prompt(')===false,'R4 UI has no browser prompt workflow');
    ok4(strpos($js,"field('Exchange Rate ke Base','exchange_rate'")!==false&&strpos($js,"field('Foreign Amount','foreign_amount'")!==false,'Journal UI exposes FX rate and foreign amount fields');
    $fxNorm=r4ValidateJournalLines($s,1,1,1,[
      ['account_id'=>1,'debit'=>1600000,'credit'=>0,'transaction_currency'=>'USD','foreign_amount'=>100,'exchange_rate'=>16000],
      ['account_id'=>6,'debit'=>0,'credit'=>1600000,'transaction_currency'=>'USD','foreign_amount'=>100,'exchange_rate'=>16000],
    ]);
    ok4(($fxNorm['total']??0)===1600000&&($fxNorm['lines'][0]['foreign_amount']??0)===100,'Foreign-currency journal validates foreign amount × exchange rate against base ledger');
    $blockedFx=false;try{r4ValidateJournalLines($s,1,1,1,[['account_id'=>1,'debit'=>100,'credit'=>0,'transaction_currency'=>'USD','exchange_rate'=>1],['account_id'=>6,'debit'=>0,'credit'=>100,'transaction_currency'=>'USD','exchange_rate'=>1]]);}catch(InvalidArgumentException $e){$blockedFx=str_contains($e->getMessage(),'Foreign amount');}
    ok4($blockedFx,'Backend rejects non-base journal without foreign amount');
    $blockedBaseRate=false;try{r4ValidateJournalLines($s,1,1,1,[['account_id'=>1,'debit'=>100,'credit'=>0,'transaction_currency'=>'IDR','foreign_amount'=>100,'exchange_rate'=>2],['account_id'=>6,'debit'=>0,'credit'=>100,'transaction_currency'=>'IDR','foreign_amount'=>100,'exchange_rate'=>2]]);}catch(InvalidArgumentException $e){$blockedBaseRate=str_contains($e->getMessage(),'harus 1');}
    ok4($blockedBaseRate,'Backend rejects non-1 exchange rate for company base currency');

    $before=count(loadStore()['entries']);
    $di=r4PostDailyIncome(['company_id'=>1,'branch_id'=>1,'department_id'=>1,'date'=>'2026-09-26','description'=>'R4 UAT daily income','currency'=>'IDR','exchange_rate'=>1,'income_lines'=>[['category_id'=>1,'amount'=>1110000,'tax_profile_id'=>1,'tax_mode'=>'inclusive']],'payment_lines'=>[['account_id'=>1,'gross_amount'=>1110000,'fee_amount'=>10000,'fee_account_id'=>7,'channel'=>'QRIS','external_ref'=>'R4-UAT-SETTLE-1']]],true);
    $s=loadStore();ok4(count($s['entries'])===$before+1&&($di['gross_amount']??0)===1110000,'Daily income posts dimensional balanced journal');
    $e=end($s['entries']);ok4((int)($e['branch_id']??0)===1&&(int)($e['department_id']??0)===1,'Journal preserves branch and department');
    ok4(count($s['tax_transactions'])===1&&count($s['settlement_batches'])===1,'Daily income writes tax and settlement records');

    $inv=r4CreateInvoice(['company_id'=>1,'branch_id'=>1,'department_id'=>1,'invoice_type'=>'receivable','party_id'=>1,'issue_date'=>'2026-09-26','due_date'=>'2026-10-10','currency'=>'IDR','exchange_rate'=>1,'lines'=>[['account_id'=>6,'description'=>'Corporate room invoice','quantity'=>1,'unit_price'=>1000000,'tax_profile_id'=>1]]],true);
    ok4(!empty($inv['id'])&&($inv['amount']??0)===1110000,'Invoice AR with tax posts to ledger');
    $pay=r4AllocatePayment(['invoice_id'=>$inv['id'],'amount'=>500000,'date'=>'2026-09-26','method'=>'bank','reference'=>'PAY-R4-001','currency'=>'IDR','exchange_rate'=>1],true);
    $s=loadStore();$ii=null;foreach($s['invoices'] as $x)if((int)$x['id']===(int)$inv['id'])$ii=$x;ok4(($ii['status']??'')==='partial'&&(int)$ii['paid']===500000,'Partial payment allocates against invoice');
    ok4(count($s['payment_allocations'])>=1,'Payment allocation record created');

    $asset=r4AcquireAsset(['company_id'=>1,'branch_id'=>1,'department_id'=>1,'asset_code'=>'R4UAT-ASSET','name'=>'R4 UAT Asset','category'=>'Equipment','acquisition_date'=>'2026-09-26','acquisition_cost'=>10000000,'useful_life_months'=>60,'residual_value'=>0,'asset_account_id'=>3,'accumulated_depreciation_account_id'=>11,'depreciation_expense_account_id'=>12,'payment_account_id'=>1],true);
    ok4(!empty($asset['id'])&&($asset['status']??'')==='active','Asset acquisition posts asset and journal');
    $tr=r4TransferAsset(['asset_id'=>$asset['id'],'branch_id'=>1,'department_id'=>2,'date'=>'2026-09-26','note'=>'Move to FNB']);ok4((int)$tr['department_id']===2,'Asset transfer updates dimension');
    $imp=r4ImpairAsset(['asset_id'=>$asset['id'],'date'=>'2026-09-26','amount'=>1000000,'note'=>'UAT impairment'],true);ok4(($imp['amount']??0)===1000000,'Asset impairment posts journal');
    $disp=r4DisposeAsset(['asset_id'=>$asset['id'],'date'=>'2026-09-26','proceeds'=>8000000,'cash_account_id'=>1,'note'=>'UAT disposal'],true);ok4(isset($disp['journal_id']),'Asset disposal posts balanced journal');

    $profile=r4CreateBankImportProfile(['company_id'=>1,'bank_account_id'=>1,'name'=>'UAT CSV','delimiter_char'=>',','date_column'=>'date','description_column'=>'description','amount_column'=>'amount','reference_column'=>'ref','date_format'=>'Y-m-d']);
    $csv="date,description,amount,ref\n2026-09-26,UAT Bank Inflow,1250000,UAT-BANK-1\n";$bi=r4ImportBankStatement(['profile_id'=>$profile['id']],$csv,'uat.csv');ok4(($bi['imported']??0)===1,'Bank import profile imports statement');

    $run=r4CreateConsolidationRun(['period'=>'2026-09','entries'=>[['from_company_id'=>1,'to_company_id'=>2,'account_id'=>9,'debit'=>1000000,'credit'=>0,'description'=>'Eliminate IC receivable'],['from_company_id'=>2,'to_company_id'=>1,'account_id'=>10,'debit'=>0,'credit'=>1000000,'description'=>'Eliminate IC payable']]]);
    $s=loadStore();$adj=r4ConsolidationAdjustments($s,'2026-09');ok4(!empty($run['id'])&&count($adj['lines'])===2,'Consolidation run creates report-layer elimination entries');
    $prevGet=$_GET;$_GET['period']='2026-09';$rep=financialReportData(null);$_GET=$prevGet;ok4(isset($rep['consolidation_adjustments'])&&count($rep['consolidation_adjustments']['lines'])>=2,'Group report applies consolidation adjustments');

    $pol=r4CreateApprovalPolicy(['company_id'=>1,'document_type'=>'journal','level_no'=>1,'threshold_amount'=>20000000,'min_approvers'=>1,'roles'=>['group_owner','group_finance']]);ok4(!empty($pol['id']),'Approval policy is runtime-managed');

} catch(Throwable $e){echo 'FATAL '.$e::class.': '.$e->getMessage()."\n";$fail++;}
finally{if($orig===null)@unlink(storagePath());else file_put_contents(storagePath(),$orig,LOCK_EX);}
echo "R4 workflow result: $pass passed, $fail failed\n";exit($fail?1:0);
