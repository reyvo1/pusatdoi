<?php
declare(strict_types=1);
namespace Nexa\Reports;

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
use Nexa\Accounting\LedgerService;

final class StatementService
{
    public function __construct(private readonly LedgerService $ledger = new LedgerService()) {}

    /** @param list<array<string,mixed>> $entries @param array<int,Account> $accounts */
    public function profitLoss(array $entries, array $accounts, ?int $companyId = null, ?string $period = null): array
    {
        $balances=$this->ledger->balances($entries,$accounts,$companyId,$period);$revenue=$expense=0;$rows=[];
        foreach($accounts as $id=>$account){$value=$balances[$id]['balance']??0;if($value===0)continue;if($account->type===AccountType::Revenue){$revenue+=$value;$rows[]=['section'=>'revenue','code'=>$account->code,'name'=>$account->name,'amount'=>$value];}elseif($account->type===AccountType::Expense){$expense+=$value;$rows[]=['section'=>'expense','code'=>$account->code,'name'=>$account->name,'amount'=>$value];}}
        return ['revenue'=>$revenue,'expense'=>$expense,'profit'=>$revenue-$expense,'margin'=>$revenue!==0?(($revenue-$expense)/$revenue*100):0.0,'rows'=>$rows];
    }

    /** @param list<array<string,mixed>> $entries @param array<int,Account> $accounts */
    public function balanceSheet(array $entries, array $accounts, ?int $companyId = null, ?string $period = null): array
    {
        $balances=$this->ledger->balances($entries,$accounts,$companyId,$period);$assets=$liabilities=$equity=0;$rows=[];
        foreach($accounts as $id=>$account){$value=$balances[$id]['balance']??0;if($value===0)continue;if($account->type===AccountType::Asset)$assets+=$value;elseif($account->type===AccountType::Liability)$liabilities+=$value;elseif($account->type===AccountType::Equity)$equity+=$value;$rows[]=['type'=>$account->type->value,'code'=>$account->code,'name'=>$account->name,'amount'=>$value];}
        if($period){$pl=$this->profitLoss($entries,$accounts,$companyId,$period);$currentProfit=$pl['profit'];}
        else{$lastClose=[];foreach($entries as$entry){if(($entry['status']??'')!=='posted'||($entry['source']??$entry['source_type']??'')!=='year_end_close')continue;$cid=(int)($entry['company_id']??0);if($companyId&&$cid!==$companyId)continue;$d=(string)($entry['date']??$entry['journal_date']??'');if($d!==''&&$d>($lastClose[$cid]??''))$lastClose[$cid]=$d;}$currentProfit=0;foreach($entries as$entry){if(($entry['status']??'')!=='posted')continue;$cid=(int)($entry['company_id']??0);if($companyId&&$cid!==$companyId)continue;$src=(string)($entry['source']??$entry['source_type']??'');if($src==='year_end_close')continue;$d=(string)($entry['date']??$entry['journal_date']??'');if(isset($lastClose[$cid])&&$d<=$lastClose[$cid])continue;foreach(($entry['lines']??[]) as$line){$id=(int)($line['account_id']??0);$acc=$accounts[$id]??null;if(!$acc)continue;$debit=(int)round((float)($line['debit']??0));$credit=(int)round((float)($line['credit']??0));if($acc->type===AccountType::Revenue)$currentProfit+=$credit-$debit;elseif($acc->type===AccountType::Expense)$currentProfit-=$debit-$credit;}}}
        $equityWithProfit=$equity+$currentProfit;
        return ['assets'=>$assets,'liabilities'=>$liabilities,'equity'=>$equityWithProfit,'retained_current_profit'=>$currentProfit,'difference'=>$assets-($liabilities+$equityWithProfit),'rows'=>$rows];
    }

    /** @param list<array<string,mixed>> $entries @param array<int,Account> $accounts */
    public function cashPosition(array $entries,array $accounts,?int $companyId=null,?string $period=null): int
    {
        $balances=$this->ledger->balances($entries,$accounts,$companyId,$period);$cash=0;foreach($accounts as $id=>$account)if($account->isCash)$cash+=$balances[$id]['balance']??0;return $cash;
    }
}
