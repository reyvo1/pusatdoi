<?php
declare(strict_types=1);
require __DIR__.'/../src/autoload.php';

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
use Nexa\Accounting\ApprovalPolicy;
use Nexa\Accounting\ClosingChecklist;
use Nexa\Accounting\DocumentSequence;
use Nexa\Accounting\FiscalPeriodPolicy;
use Nexa\Accounting\JournalBatch;
use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\Accounting\PostingPipeline;
use Nexa\Approval\ApprovalRequest;
use Nexa\Approval\ApprovalWorkflowService;
use Nexa\ARAP\InvoiceLine;
use Nexa\ARAP\InvoiceService;
use Nexa\Assets\Asset;
use Nexa\Assets\AssetRegisterService;
use Nexa\Assets\DepreciationPostingService;
use Nexa\Banking\BankStatementLine;
use Nexa\Banking\MatchScorer;
use Nexa\Banking\ReconciliationSession;
use Nexa\Budget\BudgetVarianceService;
use Nexa\Consolidation\ConsolidationRun;
use Nexa\Consolidation\ConsolidationService;
use Nexa\FX\FxRateTable;
use Nexa\Integration\OutboxMessage;
use Nexa\Integration\OutboxService;
use Nexa\Organization\Branch;
use Nexa\Organization\Company;
use Nexa\Organization\Department;
use Nexa\Organization\OrganizationService;
use Nexa\Reports\KpiService;
use Nexa\Reports\TrendService;
use Nexa\Security\LoginThrottle;
use Nexa\Security\PasswordPolicy;
use Nexa\Security\Role;
use Nexa\Support\Masker;
use Nexa\Support\Paginator;
use Nexa\Tax\TaxTransactionService;

$pass=0;$fail=0;
function a(string $name,bool $ok):void{global $pass,$fail;echo($ok?'PASS':'FAIL')."  {$name}\n";$ok?$pass++:$fail++;}
function ax(callable $fn,string $needle=''):bool{try{$fn();return false;}catch(Throwable $e){return $needle===''||str_contains($e->getMessage(),$needle);}}

$companies=[new Company(1,'HTL','Hotel Nusantara','Hotel'),new Company(2,'RTL','Retail Sejahtera','Retail')];
$branches=[new Branch(1,1,'MDO','Hotel Manado'),new Branch(2,2,'MDO','Retail Manado')];
$departments=[new Department(1,1,1,'ROOM','Rooms','CC-R','PC-R'),new Department(2,1,1,'FNB','F&B','CC-F','PC-F')];
$tree=(new OrganizationService())->tree($companies,$branches,$departments);
a('Organization tree keeps company/branch/department hierarchy',count($tree)===2&&count($tree[0]['branches'][0]['departments'])===2);
a('Cross-company branch rejected',ax(fn()=>(new OrganizationService())->assertBranchBelongs(1,2,$branches),'Cabang'));
$seq=(new DocumentSequence())->format('JRN','2026-09-25',42);a('Document sequence deterministic',$seq==='JRN-202609-000042');

$entry1=new JournalEntry(1,'2026-09-25','Revenue',[new JournalLine(1,1000000,0),new JournalLine(6,0,1000000)]);
$entry2=new JournalEntry(1,'2026-09-25','Expense',[new JournalLine(7,250000,0),new JournalLine(1,0,250000)]);
$batch=new JournalBatch(1,'BAT-001','2026-09-25','manual');$batch->add($entry1);$batch->add($entry2);$batch->submit();$batch->approve();$batch->posted();a('Journal batch state machine posts balanced batch',$batch->status==='posted'&&$batch->totals()['balanced']);
a('Posted batch cannot mutate',ax(fn()=>$batch->add($entry1),'draft'));

$accounts=[
1=>new Account(1,'1101','Cash',AccountType::Asset,null,true),6=>new Account(6,'4101','Revenue',AccountType::Revenue),7=>new Account(7,'5101','Expense',AccountType::Expense)
];
$pipe=new PostingPipeline(new ApprovalPolicy(25000000));$inspect=$pipe->inspect($entry1,$accounts,new FiscalPeriodPolicy(['2026-09'=>'open']),Role::EntityAdmin);a('Posting pipeline allows small balanced journal',$inspect['posting_allowed']&&!$inspect['approval_required']);
$large=new JournalEntry(1,'2026-09-25','Large',[new JournalLine(1,30000000,0),new JournalLine(6,0,30000000)]);$inspect=$pipe->inspect($large,$accounts,new FiscalPeriodPolicy(['2026-09'=>'open']),Role::EntityAdmin);a('Posting pipeline routes large journal to approval',$inspect['approval_required']&&!$inspect['posting_allowed']);
$check=(new ClosingChecklist())->evaluate(['trial_balance_balanced'=>true,'pending_approvals'=>0,'unmatched_bank'=>0,'unposted_depreciation'=>0,'integration_failures'=>0,'intercompany_unmatched'=>0,'arap_cutoff_confirmed'=>true]);a('Closing checklist ready only when all gates clear',$check['ready']===true);

$request=new ApprovalRequest(1,1,'journal',50000000,99,2);$workflow=new ApprovalWorkflowService();$workflow->decide($request,1,Role::GroupOwner,'approved','2026-09-25 10:00:00');a('Multi-approver request remains pending after first approval',$request->status==='pending');$workflow->decide($request,2,Role::GroupFinance,'approved','2026-09-25 10:01:00');a('Multi-approver request approves after threshold',$request->status==='approved');
a('Requester cannot self-approve',ax(function(){ $r=new ApprovalRequest(2,1,'journal',100,1,1);(new ApprovalWorkflowService())->decide($r,1,Role::GroupOwner,'approved','now');},'Requester'));

$lines=[new InvoiceLine(6,'Room',2,500000,55000),new InvoiceLine(6,'Laundry',1,100000,11000)];$total=(new InvoiceService())->total($lines);a('Invoice lines aggregate subtotal/tax/gross',$total['subtotal']===1100000&&$total['tax']===66000&&$total['gross']===1166000);

$session=new ReconciliationSession(1,'2026-09',0,1000000);$session->match(1,1,1000000,'auto',98.5);$session->close(1000000,0);a('Bank reconciliation session closes when balances match',$session->status==='closed');
a('Reconciliation close rejects unmatched',ax(function(){ $s=new ReconciliationSession(1,'2026-09',0,100);$s->close(100,1);},'belum matched'));
$score=(new MatchScorer())->score(new BankStatementLine(1,1,'2026-09-25','OTA SETTLEMENT 123',1000000,'in'),'2026-09-25','OTA SETTLEMENT 123',1000000);a('Bank match scorer rewards exact match',$score>=90);

$fx=new FxRateTable();$fx->set('2026-09-25','USD','IDR',16850);a('FX rate table stores/retrieves exact rate',$fx->get('2026-09-25','USD','IDR')===16850.0);a('Missing FX rate rejected',ax(fn()=>$fx->get('2026-09-24','USD','IDR'),'belum tersedia'));
$asset=new Asset(1,1,'Vehicle','2026-01-01',120000000,60,20000000);$reg=(new AssetRegisterService())->summary([$asset],'2026-09-25');a('Asset register returns book value',$reg['book_value']>0&&$reg['book_value']<=$reg['cost']);$depJournal=(new DepreciationPostingService())->build($asset,'2026-09-30',7,1);a('Depreciation posting produces balanced journal',$depJournal->totalDebit()===$depJournal->totalCredit());

$run=new ConsolidationRun('2026-09','CON-001');$run->addEntity(1,['company_id'=>1,'revenue'=>1000,'expense'=>400,'assets'=>3000,'liabilities'=>1000,'equity'=>1400]);$run->addEntity(2,['company_id'=>2,'revenue'=>500,'expense'=>200,'assets'=>1500,'liabilities'=>500,'equity'=>800]);$run->addElimination(['revenue'=>-100,'expense'=>-100,'assets'=>-200,'liabilities'=>-200,'equity'=>0]);$con=(new ConsolidationService())->execute($run);a('Consolidation run validates/posts',$run->status==='posted'&&$con['report']['revenue']===1400);$run->lock();a('Consolidation run can lock after posting',$run->status==='locked');

$msg1=new OutboxMessage('journal.posted','journal','1',['id'=>1]);$msg2=new OutboxMessage('invoice.paid','invoice','2',['id'=>2]);$out=(new OutboxService())->dispatch([$msg1,$msg2],function(OutboxMessage $m){if($m->aggregateId==='2')throw new RuntimeException('network');});a('Outbox tracks sent/failed independently',$out['sent']===1&&$out['failed']===1&&$msg1->status==='sent'&&$msg2->status==='failed');$msg2->retry();a('Failed outbox can retry',$msg2->status==='pending');

$tax=(new TaxTransactionService())->summary([['direction'=>'output','tax_amount'=>110000],['direction'=>'input','tax_amount'=>40000],['direction'=>'withholding','tax_amount'=>10000]]);a('Tax transaction summary calculates net payable',$tax['net_payable']===60000);
$portfolio=(new BudgetVarianceService())->portfolio([['budget'=>100,'actual'=>90,'account_type'=>'expense'],['budget'=>200,'actual'=>250,'account_type'=>'revenue']]);a('Budget portfolio aggregates favorable rows',$portfolio['budget']===300&&$portfolio['actual']===340&&$portfolio['favorable_count']===2);

$kpi=(new KpiService())->finance(['revenue'=>1000,'expense'=>600],['assets'=>5000,'liabilities'=>2000],700,500,300);a('Finance KPIs calculate working capital/margin',$kpi['profit']===400&&$kpi['current_net_working_capital']===900&&abs($kpi['margin']-40)<0.001);
$trend=(new TrendService())->monthly([['status'=>'posted','company_id'=>1,'date'=>'2026-09-01','lines'=>[['account_id'=>1,'debit'=>1000,'credit'=>0],['account_id'=>6,'debit'=>0,'credit'=>1000]]]],$accounts,'2026-09',2,1);a('Trend service preserves requested month count',count($trend)===2&&$trend[1]['revenue']===1000);

$policy=new PasswordPolicy();a('Strong password accepted',(function()use($policy){$policy->assert('StrongPassword!2026');return true;})());a('Weak password rejected',ax(fn()=>$policy->assert('password'),'12 karakter'));
$throttle=new LoginThrottle();a('Login throttle blocks repeated failures',$throttle->isBlocked([100,110,120,130,140],150,100,5));a('Login retry after positive',$throttle->retryAfter([100,110,120,130,140],150,100,5)>0);
$mask=new Masker();a('Account masker preserves only tail',str_ends_with($mask->accountNumber('1234567890'),'7890')&&!str_contains($mask->accountNumber('1234567890'),'123456'));
$page=(new Paginator())->slice(range(1,101),2,50);a('Paginator returns stable second page',count($page['items'])===50&&$page['items'][0]===51&&$page['meta']['pages']===3);

echo "\nEnterprise advanced result: {$pass} passed, {$fail} failed\n";exit($fail?1:0);
