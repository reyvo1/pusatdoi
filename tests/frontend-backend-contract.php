<?php
declare(strict_types=1);

$root=dirname(__DIR__);$pass=$fail=0;
function c(bool $ok,string $label):void{global $pass,$fail;echo ($ok?'PASS  ':'FAIL  ').$label."\n";$ok?$pass++:$fail++;}
function hasAll(string $haystack,array $needles):bool{foreach($needles as$n)if(strpos($haystack,$n)===false)return false;return true;}
function tableBody(string $sql,string $table):string{if(!preg_match('/CREATE TABLE\s+'.preg_quote($table,'/').'\s*\((.*?)\)\s*ENGINE=InnoDB;/si',$sql,$m))return '';return$m[1];}

$api=file_get_contents($root.'/api.php');
$bootstrap=file_get_contents($root.'/lib/bootstrap.php');
$r4=file_get_contents($root.'/lib/r4_runtime.php');
$r5=file_get_contents($root.'/lib/r5_runtime.php');
$r6=file_get_contents($root.'/lib/r6_runtime.php');
$r7=file_get_contents($root.'/lib/r7_runtime.php');
$schema=file_get_contents($root.'/database/schema_enterprise_r7.sql');
$uiFiles=['assets/app.js','assets/r4-ui.js','assets/r5-ui.js','assets/r6-ui.js','assets/r7-ui.js'];$ui='';foreach($uiFiles as$f)$ui.="\n".file_get_contents($root.'/'.$f);

preg_match_all("/api\\('([^']+)'/",$ui,$um);$uiActions=array_values(array_unique($um[1]));
preg_match_all("/action==='([^']+)'/",$api,$am);$apiActions=array_values(array_unique($am[1]));
$missing=array_values(array_diff($uiActions,$apiActions));
c($missing===[],'Every frontend API action has an api.php route'.($missing?' ['.implode(',',$missing).']':''));

// Journal: UI -> API -> runtime -> database field contract.
c(hasAll($ui,["api('r4-post-journal'","field('Exchange Rate ke Base','exchange_rate'","field('Foreign Amount','foreign_amount'",'transaction_currency','Foreign Amount wajib']),'Journal frontend sends explicit FX contract fields');
c(strpos($api,"\$action==='r4-post-journal'")!==false&&strpos($api,'r4PostMultiLineJournal($_POST,false)')!==false,'Journal API routes to r4PostMultiLineJournal');
c(hasAll($r4,["['transaction_currency']","['foreign_amount']","['exchange_rate']",'foreign amount × exchange rate','base_currency']),'Journal backend validates currency, foreign amount, rate and company base currency');
c(hasAll(tableBody($schema,'journal_lines'),['transaction_currency','foreign_amount','exchange_rate','base_amount']),'Journal database stores full FX fields');

// Bank reconciliation: field names must agree across UI/runtime/schema.
c(hasAll($ui,["api('r4-open-reconciliation'",'period_start','period_end','opening_balance','closing_balance']),'Reconciliation frontend sends period and statement balances');
c(hasAll($r4,['function r4OpenReconciliation','period_start','period_end','opening_balance','closing_balance','matched_amount','difference_amount']),'Reconciliation backend consumes final session shape');
c(hasAll(tableBody($schema,'bank_reconciliation_sessions'),['period_start','period_end','opening_balance','closing_balance','matched_amount','difference_amount','closed_by','reopened_at']),'Reconciliation fresh schema matches backend session shape');

// Invoice/ARAP: lines and multi-currency must traverse all layers.
c(hasAll($ui,["api('r4-create-invoice'",'invoice_type','party_id','exchange_rate','tax_profile_id','unit_price','lines']),'Invoice frontend sends party, lines, tax and FX fields');
c(hasAll($r4,['function r4CreateInvoice','invoice_type','party_id','exchange_rate','tax_profile_id','unit_price','invoice_lines','payment_allocations']),'Invoice backend consumes line/tax/FX and allocation model');
c(hasAll(tableBody($schema,'invoices'),['invoice_type','party_id','currency'])&&hasAll(tableBody($schema,'invoice_lines'),['tax_profile_id','unit_price','line_total']),'Invoice schema matches runtime master/line contract');

// R5-R7 high-value operations: UI must route and backend functions must exist.
$critical=[
 'r5-post-daily-expense'=>'r5PostDailyExpense',
 'r5-bank-transfer'=>'r5BankTransfer',
 'r5-opening-balance'=>'r5PostOpeningBalance',
 'r6-bulk-import'=>'r6BulkImport',
 'r6-create-payment-batch'=>'r6CreatePaymentBatch',
 'r6-upload-document'=>'r6UploadDocument',
 'r7-issue-advance'=>'r7IssueAdvance',
 'r7-settle-advance'=>'r7SettleAdvance',
 'r7-create-loan'=>'r7CreateLoan',
 'r7-pay-loan'=>'r7PayLoan',
 'r7-post-equity'=>'r7PostEquity',
 'r7-create-budget-scenario'=>'r7CreateBudgetScenario',
 'r7-save-budget-line'=>'r7SaveBudgetScenarioLine',
];
$allRuntime=$r5."\n".$r6."\n".$r7;
foreach($critical as$action=>$fn){c(in_array($action,$uiActions,true)&&in_array($action,$apiActions,true)&&strpos($allRuntime,'function '.$fn)!==false,$action.' frontend/API/runtime wired');}

// Fail-closed and canonical storage contracts.
c(strpos($bootstrap,'if(!$cid){foreach(array_merge($direct')!==false&&strpos($bootstrap,'$s[$k]=[];return $s;}')!==false,'Entity-admin missing scope remains fail-closed');
c(!preg_match('/^ALTER TABLE\b/im',$schema),'Fresh R7 schema is final-shape only (no migration ALTER leftovers)');

echo "Frontend/backend contract result: $pass passed, $fail failed\n";exit($fail?1:0);
