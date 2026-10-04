<?php
declare(strict_types=1);
namespace Nexa\Reports;

final class SqlReportService
{
    public function __construct(private readonly \PDO $pdo){}
    private function scope(array $f):array
    {
        $w=["je.status='posted'","je.journal_date BETWEEN ? AND ?"];$p=[$f['start'],$f['end']];
        foreach([['company_id','je.company_id'],['branch_id','COALESCE(jl.branch_id,je.branch_id)'],['department_id','COALESCE(jl.department_id,je.department_id)'],['account_id','jl.account_id']] as [$k,$col])if(!empty($f[$k])){$w[]="$col=?";$p[]=(int)$f[$k];}
        if(!empty($f['currency'])){$w[]='jl.transaction_currency=?';$p[]=(string)$f['currency'];}
        return [implode(' AND ',$w),$p];
    }
    public function trialBalance(array $f):array
    {
        [$w,$p]=$this->scope($f);$sql="SELECT a.id,a.code,a.name,a.account_type type,SUM(jl.debit) debit,SUM(jl.credit) credit FROM journal_entries je JOIN journal_lines jl ON jl.journal_id=je.id JOIN chart_accounts a ON a.id=jl.account_id WHERE $w GROUP BY a.id,a.code,a.name,a.account_type ORDER BY a.code";$st=$this->pdo->prepare($sql);$st->execute($p);return $st->fetchAll();
    }
    public function ledger(array $f,int $limit=100,int $offset=0):array
    {
        [$w,$p]=$this->scope($f);$sql="SELECT je.id,je.journal_no,je.journal_date,je.description,je.company_id,COALESCE(jl.branch_id,je.branch_id) branch_id,COALESCE(jl.department_id,je.department_id) department_id,jl.account_id,a.code account_code,a.name account_name,jl.debit,jl.credit,jl.transaction_currency,jl.foreign_amount,jl.exchange_rate,jl.cost_center,jl.profit_center,jl.project_code FROM journal_entries je JOIN journal_lines jl ON jl.journal_id=je.id JOIN chart_accounts a ON a.id=jl.account_id WHERE $w ORDER BY je.journal_date DESC,je.id DESC,jl.id LIMIT ? OFFSET ?";$p[]=$limit;$p[]=$offset;$st=$this->pdo->prepare($sql);$st->execute($p);return $st->fetchAll();
    }
    public function pnl(array $f):array
    {
        [$w,$p]=$this->scope($f);$sql="SELECT a.account_type type,SUM(CASE WHEN a.account_type='revenue' THEN jl.credit-jl.debit WHEN a.account_type='expense' THEN jl.debit-jl.credit ELSE 0 END) amount FROM journal_entries je JOIN journal_lines jl ON jl.journal_id=je.id JOIN chart_accounts a ON a.id=jl.account_id WHERE $w AND je.source_type<>'year_end_close' AND a.account_type IN('revenue','expense') GROUP BY a.account_type";$st=$this->pdo->prepare($sql);$st->execute($p);$r=['revenue'=>0.0,'expense'=>0.0];foreach($st->fetchAll() as $x)$r[$x['type']]=(float)$x['amount'];$r['profit']=$r['revenue']-$r['expense'];return$r;
    }
}
