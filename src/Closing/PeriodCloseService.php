<?php
declare(strict_types=1);
namespace Nexa\Closing;

final class PeriodCloseService
{
    public const TASKS=[
        'pending_approvals'=>'Tidak ada approval tertunda',
        'bank_reconciliation'=>'Semua rekening bank direkonsiliasi',
        'tax_review'=>'Transaksi pajak periode telah ditinjau',
        'depreciation'=>'Depresiasi aset periode telah diposting',
        'arap_review'=>'AR/AP overdue telah ditinjau',
        'intercompany'=>'Intercompany mismatch telah diselesaikan',
        'trial_balance'=>'Neraca saldo balance',
    ];
    public function __construct(private readonly \PDO $pdo){}
    public function evaluate(int $companyId,int $year,int $month):array
    {
        $period=sprintf('%04d-%02d',$year,$month);$start=$period.'-01';$end=date('Y-m-t',strtotime($start));
        $checks=[];
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM journal_approvals WHERE company_id=? AND status='pending' AND journal_date BETWEEN ? AND ?");$q->execute([$companyId,$start,$end]);$checks['pending_approvals']=(int)$q->fetchColumn()===0;
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM bank_reconciliation_sessions WHERE company_id=? AND period_end BETWEEN ? AND ? AND status='closed'");$q->execute([$companyId,$start,$end]);$closed=(int)$q->fetchColumn();
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM bank_accounts WHERE company_id=? AND is_active=1");$q->execute([$companyId]);$bankCount=(int)$q->fetchColumn();$checks['bank_reconciliation']=$bankCount===0||$closed>=$bankCount;
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM tax_transactions WHERE company_id=? AND transaction_date BETWEEN ? AND ? AND status='open'");$q->execute([$companyId,$start,$end]);$checks['tax_review']=(int)$q->fetchColumn()===0;
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM asset_depreciation_schedule ads JOIN fixed_assets fa ON fa.id=ads.asset_id WHERE fa.company_id=? AND ads.period=? AND ads.status='planned'");$q->execute([$companyId,$period]);$checks['depreciation']=(int)$q->fetchColumn()===0;
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM invoices WHERE company_id=? AND status IN('open','partial') AND due_date<?");$q->execute([$companyId,$end]);$checks['arap_review']=(int)$q->fetchColumn()===0;
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM journal_entries WHERE company_id=? AND status='posted' AND journal_date BETWEEN ? AND ? AND source_type='intercompany' AND reversed_by IS NULL");$q->execute([$companyId,$start,$end]);$inter=(int)$q->fetchColumn();
        $q=$this->pdo->prepare("SELECT COUNT(*) FROM intercompany_eliminations WHERE period_key=? AND status='posted' AND (from_company_id=? OR to_company_id=?)");$q->execute([$period,$companyId,$companyId]);$elim=(int)$q->fetchColumn();$checks['intercompany']=$inter===0||$elim>0;
        $q=$this->pdo->prepare("SELECT COALESCE(SUM(jl.debit-jl.credit),0) FROM journal_entries je JOIN journal_lines jl ON jl.journal_id=je.id WHERE je.company_id=? AND je.status='posted' AND je.journal_date BETWEEN ? AND ?");$q->execute([$companyId,$start,$end]);$checks['trial_balance']=abs((float)$q->fetchColumn())<0.005;
        return ['company_id'=>$companyId,'period'=>$period,'checks'=>$checks,'ready'=>!in_array(false,$checks,true)];
    }
    public function persistTasks(array $evaluation,?int $userId=null):void
    {
        [$y,$m]=array_map('intval',explode('-',$evaluation['period']));
        $st=$this->pdo->prepare("INSERT INTO period_close_tasks(company_id,fiscal_year,period,task_code,task_name,status,detail_text,completed_by,completed_at) VALUES(?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE status=VALUES(status),detail_text=VALUES(detail_text),completed_by=VALUES(completed_by),completed_at=VALUES(completed_at)");
        foreach($evaluation['checks'] as $code=>$ok)$st->execute([$evaluation['company_id'],$y,$m,$code,self::TASKS[$code]??$code,$ok?'passed':'pending',$ok?'PASS':'Perlu diselesaikan',$ok?$userId:null,$ok?date('Y-m-d H:i:s'):null]);
    }
}
