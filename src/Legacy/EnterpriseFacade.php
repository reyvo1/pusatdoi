<?php
declare(strict_types=1);
namespace Nexa\Legacy;

use Nexa\Accounting\JournalLine;
use Nexa\Accounting\JournalValidator;
use Nexa\Accounting\TrialBalanceService;
use Nexa\Reports\StatementService;
use Nexa\Tax\TaxCalculator;
use Nexa\Tax\TaxProfile;
use Nexa\FX\FxCalculator;

final class EnterpriseFacade
{
    private StateAdapter $adapter;
    public function __construct(?StateAdapter $adapter=null){$this->adapter=$adapter??new StateAdapter();}

    public function entryBalanced(array $lines): bool
    {
        try{$domain=[];$seq=1;foreach($lines as $l)$domain[]=new JournalLine((int)($l['account_id']??$seq++),(int)round((float)($l['debit']??0)),(int)round((float)($l['credit']??0)),(string)($l['description']??''));return JournalValidator::isBalanced($domain);}catch(\Throwable){return false;}
    }

    public function trialBalance(array $state,?string $period=null,?int $companyId=null): array
    {
        $accounts=$this->adapter->accounts($state);$rows=(new TrialBalanceService())->build($state['entries']??[],$accounts,$companyId,$period);foreach($rows as &$row){$row['id']=$row['account_id'];unset($row['account_id']);}unset($row);return $rows;
    }

    public function balanceSheet(array $state,?string $period=null,?int $companyId=null): array
    {
        $accounts=$this->adapter->accounts($state);$row=(new StatementService())->balanceSheet($state['entries']??[],$accounts,$companyId,$period);$row['net_assets']=$row['assets']-$row['liabilities'];return $row;
    }

    public function tax(int $amount,float $rate,bool $inclusive): array
    {
        return (new TaxCalculator())->calculate($amount,new TaxProfile('INLINE','Inline Tax',$rate,$inclusive));
    }

    public function fx(float $foreign,float $bookRate,float $settlementRate): array
    {
        return (new FxCalculator())->realized($foreign,$bookRate,$settlementRate);
    }
}
