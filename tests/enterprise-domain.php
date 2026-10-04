<?php
declare(strict_types=1);
require __DIR__.'/../src/autoload.php';

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
use Nexa\Accounting\ApprovalPolicy;
use Nexa\Accounting\FiscalPeriodPolicy;
use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\Accounting\JournalValidator;
use Nexa\Accounting\ReversalService;
use Nexa\Accounting\TrialBalanceService;
use Nexa\ARAP\AgingService;
use Nexa\ARAP\Invoice;
use Nexa\ARAP\PaymentAllocationService;
use Nexa\Assets\Asset;
use Nexa\Assets\DepreciationService;
use Nexa\Banking\BankStatementLine;
use Nexa\Banking\CsvBankImporter;
use Nexa\Banking\ReconciliationService;
use Nexa\Backup\SnapshotIntegrity;
use Nexa\Budget\BudgetVarianceService;
use Nexa\Consolidation\IntercompanyMatcher;
use Nexa\Consolidation\IntercompanyPair;
use Nexa\FX\FxCalculator;
use Nexa\Integration\InboundStagingService;
use Nexa\Revenue\DailyIncomePostingService;
use Nexa\Revenue\SettlementService;
use Nexa\Security\AuthorizationService;
use Nexa\Security\Permission;
use Nexa\Security\Role;
use Nexa\Tax\TaxCalculator;
use Nexa\Tax\TaxProfile;

$pass=0;$fail=0;
function tt(string $name,bool $ok):void{global $pass,$fail;echo($ok?'PASS':'FAIL')."  {$name}\n";$ok?$pass++:$fail++;}
function throws(callable $fn,string $contains=''):bool{try{$fn();return false;}catch(Throwable $e){return $contains===''||str_contains($e->getMessage(),$contains);}}

$accounts=[
  1=>new Account(1,'1101','Kas & Bank',AccountType::Asset,null,true),
  2=>new Account(2,'1102','Piutang',AccountType::Asset),
  4=>new Account(4,'2101','Hutang',AccountType::Liability),
  5=>new Account(5,'3101','Modal',AccountType::Equity),
  6=>new Account(6,'4101','Pendapatan',AccountType::Revenue),
  7=>new Account(7,'5101','Beban',AccountType::Expense),
  9=>new Account(9,'1301','Piutang Antar',AccountType::Asset),
 10=>new Account(10,'2301','Hutang Antar',AccountType::Liability),
];
$j=new JournalEntry(1,'2026-09-25','Penjualan',[new JournalLine(1,1_000_000,0),new JournalLine(6,0,1_000_000)]);
tt('Journal balanced',JournalValidator::isBalanced($j->lines));
tt('Journal unbalanced rejected',throws(fn()=>new JournalEntry(1,'2026-09-25','Bad',[new JournalLine(1,100,0),new JournalLine(6,0,90)]),'balance'));
tt('Journal same-side line rejected',throws(fn()=>new JournalLine(1,100,100),'debit atau kredit'));
$period=new FiscalPeriodPolicy(['2026-08'=>'closed','2026-09'=>'open']);
tt('Closed period blocks posting',throws(fn()=>$period->assertPostingAllowed('2026-08-22'),'ditutup'));
tt('Open period allows posting',(function()use($period){$period->assertPostingAllowed('2026-09-22');return true;})());
tt('Close blocks pending approval',throws(fn()=>$period->assertHardCloseAllowed('2026-09',1,0,true),'approval'));
tt('Close blocks unmatched bank',throws(fn()=>$period->assertHardCloseAllowed('2026-09',0,1,true),'bank'));
tt('Close blocks unbalanced TB',throws(fn()=>$period->assertHardCloseAllowed('2026-09',0,0,false),'balance'));
$approval=new ApprovalPolicy(25_000_000);
tt('Large entity transaction requires approval',$approval->requiresApproval(30_000_000,Role::EntityAdmin));
tt('Owner can post large without separate threshold approval',!$approval->requiresApproval(30_000_000,Role::GroupOwner));
tt('Auditor cannot approve',throws(fn()=>$approval->assertDecisionRole(Role::Auditor),'Owner/Finance'));
$auth=new AuthorizationService();
tt('Owner can manage users',$auth->can(Role::GroupOwner,Permission::UserManage));
tt('Auditor cannot post journal',!$auth->can(Role::Auditor,Permission::JournalPost));
tt('Entity scope restricted',$auth->scopeCompanyIds(Role::EntityAdmin,2,[1,2,3])===[2]);
$daily=(new DailyIncomePostingService())->build(1,'2026-09-25','Pendapatan harian',[[ 'id'=>1,'company_id'=>1,'name'=>'Kamar','revenue_account_id'=>6,'active'=>1]],$accounts,[['category_id'=>1,'amount'=>8_000_000]],[['account_id'=>1,'amount'=>8_000_000,'label'=>'Bank']]);
tt('Daily income builds balanced journal',JournalValidator::isBalanced($daily->lines)&&$daily->source==='daily_income');
tt('Daily income rejects mismatch',throws(fn()=>(new DailyIncomePostingService())->build(1,'2026-09-25','Pendapatan harian',[[ 'id'=>1,'company_id'=>1,'name'=>'Kamar','revenue_account_id'=>6,'active'=>1]],$accounts,[['category_id'=>1,'amount'=>8_000_000]],[['account_id'=>1,'amount'=>7_000_000]]),'sama'));
$rev=(new ReversalService())->reverse($j,'2026-09-26','Koreksi',77);tt('Reversal swaps debit/credit',$rev->lines[0]->credit===1_000_000&&$rev->lines[1]->debit===1_000_000&&$rev->reversalOf===77);
$invoice=new Invoice(1,1,'receivable','AR-001','OTA','2026-09-01','2026-09-30',10_000_000,2_000_000);$pay=(new PaymentAllocationService())->allocate($invoice,3_000_000,1,2,4,'2026-09-25','PAY-1');tt('AR allocation reduces outstanding',$invoice->outstanding()===5_000_000&&$pay->lines[0]->debit===3_000_000);
tt('AR overpayment rejected',throws(fn()=>(new PaymentAllocationService())->allocate($invoice,6_000_000,1,2,4,'2026-09-25','PAY-2'),'melebihi'));
$aging=(new AgingService())->bucket([new Invoice(2,1,'receivable','AR-002','Tenant','2026-07-01','2026-07-31',2_000_000)],'2026-09-25');tt('Aging buckets overdue invoice',$aging['31_60']===2_000_000||$aging['61_90']===2_000_000);
$bank=new BankStatementLine(1,1,'2026-09-25','SETTLEMENT',1_000_000,'in');$rec=new ReconciliationService();tt('Bank match validates company/amount',(function()use($rec,$bank,$j,$accounts){$rec->assertMatch($bank,$j,$accounts);return true;})());
$badBank=new BankStatementLine(2,2,'2026-09-25','SETTLEMENT',1_000_000,'in');tt('Bank mismatch company rejected',throws(fn()=>$rec->assertMatch($badBank,$j,$accounts),'berbeda'));
$csv=(new CsvBankImporter())->parse("date,description,amount,direction\n2026-09-25,SETTLEMENT,1000000,in\n");tt('Bank CSV parser creates idempotent ref',count($csv)===1&&str_starts_with($csv[0]['external_ref'],'CSV-'));
$matches=(new IntercompanyMatcher())->match([new IntercompanyPair(1,2,5_000_000,'IC-A','2026-09'),new IntercompanyPair(2,1,5_000_000,'IC-B','2026-09')]);tt('Intercompany counterpart matched',$matches[0]['status']==='matched');
$tax=(new TaxCalculator())->calculate(111_000,new TaxProfile('PPN','PPN',11,true));tt('Inclusive tax exact',$tax['base']===100_000&&$tax['tax']===11_000);
$fx=(new FxCalculator())->realized(1000,16800,16900);tt('FX gain loss exact',$fx['gain_loss']===100_000);
$asset=new Asset(1,1,'Mesin','2026-01-01',12_000_000,12,0);$dep=new DepreciationService();tt('Straight-line depreciation monthly',$dep->monthly($asset)===1_000_000);tt('Depreciation book value bounded',$dep->bookValue($asset,'2026-06-30')>=0);
$budget=(new BudgetVarianceService())->calculate(10_000_000,9_000_000,'expense');tt('Expense under budget favorable',$budget['favorable']===true&&$budget['variance']===-1_000_000);
$settle=(new SettlementService())->summarize([['method'=>'QRIS','gross'=>10_000_000,'fee'=>70_000,'net'=>9_930_000]]);tt('Settlement gross-fee-net invariant',$settle['net']===9_930_000);
$staging=(new InboundStagingService())->stage('tamasya','TX-1',1,2_500_000,['kind'=>'revenue']);tt('Integration idempotency key generated',strlen($staging['idempotency_key'])===64&&$staging['status']==='received');
tt('Integration duplicate blocked',throws(fn()=>(new InboundStagingService())->stage('tamasya','TX-1',1,2_500_000,[],[$staging['idempotency_key']]),'duplikat'));
$wrap=(new SnapshotIntegrity())->wrap(['hello'=>'world','rows'=>[1,2,3]]);tt('Backup checksum verifies',(new SnapshotIntegrity())->verify($wrap)['hello']==='world');$wrap['payload']['hello']='tampered';tt('Backup tamper rejected',throws(fn()=>(new SnapshotIntegrity())->verify($wrap),'Checksum'));
$legacyEntries=[['status'=>'posted','company_id'=>1,'date'=>'2026-09-25','lines'=>[['account_id'=>1,'debit'=>1_000_000,'credit'=>0],['account_id'=>6,'debit'=>0,'credit'=>1_000_000]]]];$tb=(new TrialBalanceService())->build($legacyEntries,$accounts,1,'2026-09');$tot=(new TrialBalanceService())->totals($tb);tt('Trial balance totals equal',$tot['balanced']===true&&$tot['debit']===1_000_000);

echo "\nEnterprise domain result: {$pass} passed, {$fail} failed\n";exit($fail?1:0);
