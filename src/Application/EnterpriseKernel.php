<?php
declare(strict_types=1);
namespace Nexa\Application;

use Nexa\Accounting\ApprovalPolicy;
use Nexa\Accounting\FiscalPeriodPolicy;
use Nexa\Accounting\TrialBalanceService;
use Nexa\ARAP\AgingService;
use Nexa\Assets\DepreciationService;
use Nexa\Banking\CsvBankImporter;
use Nexa\Banking\ReconciliationService;
use Nexa\Budget\BudgetVarianceService;
use Nexa\Consolidation\EliminationService;
use Nexa\Consolidation\IntercompanyMatcher;
use Nexa\FX\FxCalculator;
use Nexa\Integration\InboundStagingService;
use Nexa\Legacy\StateAdapter;
use Nexa\Reports\StatementService;
use Nexa\Revenue\DailyIncomePostingService;
use Nexa\Revenue\SettlementService;
use Nexa\Security\AuthorizationService;
use Nexa\Tax\TaxCalculator;

final class EnterpriseKernel
{
    public readonly StateAdapter $adapter;
    public readonly AuthorizationService $authorization;
    public readonly ApprovalPolicy $approval;
    public readonly TrialBalanceService $trialBalance;
    public readonly StatementService $statements;
    public readonly DailyIncomePostingService $dailyIncome;
    public readonly ReconciliationService $reconciliation;
    public readonly CsvBankImporter $bankImporter;
    public readonly AgingService $aging;
    public readonly IntercompanyMatcher $intercompanyMatcher;
    public readonly EliminationService $elimination;
    public readonly TaxCalculator $tax;
    public readonly FxCalculator $fx;
    public readonly DepreciationService $depreciation;
    public readonly BudgetVarianceService $budget;
    public readonly InboundStagingService $integration;
    public readonly SettlementService $settlement;

    public function __construct(int $approvalThreshold = 25_000_000)
    {
        $this->adapter=new StateAdapter();$this->authorization=new AuthorizationService();$this->approval=new ApprovalPolicy($approvalThreshold);
        $this->trialBalance=new TrialBalanceService();$this->statements=new StatementService();$this->dailyIncome=new DailyIncomePostingService();
        $this->reconciliation=new ReconciliationService();$this->bankImporter=new CsvBankImporter();$this->aging=new AgingService();
        $this->intercompanyMatcher=new IntercompanyMatcher();$this->elimination=new EliminationService();$this->tax=new TaxCalculator();$this->fx=new FxCalculator();
        $this->depreciation=new DepreciationService();$this->budget=new BudgetVarianceService();$this->integration=new InboundStagingService();$this->settlement=new SettlementService();
    }

    public function fiscalPeriods(array $statuses=[]): FiscalPeriodPolicy { return new FiscalPeriodPolicy($statuses); }

    public function health(array $state): array
    {
        $accounts=$this->adapter->accounts($state);$trial=$this->trialBalance->build($state['entries']??[],$accounts);$totals=$this->trialBalance->totals($trial);$bs=$this->statements->balanceSheet($state['entries']??[],$accounts);
        return ['architecture'=>'enterprise-r3','trial_balance'=>$totals,'balance_sheet_difference'=>$bs['difference'],'companies'=>count($state['companies']??[]),'accounts'=>count($accounts),'entries'=>count($state['entries']??[])];
    }
}
