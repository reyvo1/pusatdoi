<?php
declare(strict_types=1);
namespace Nexa\Reports;

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;

final class CashFlowClassifier
{
    /** @param array<int,Account> $accounts */
    public function classify(array $entry, array $accounts): string
    {
        $counterTypes=[];
        foreach(($entry['lines']??[]) as $line){$a=$accounts[(int)($line['account_id']??0)]??null;if(!$a||$a->isCash)continue;$counterTypes[]=$a->type;}
        foreach($counterTypes as $type)if(in_array($type,[AccountType::Equity,AccountType::Liability],true))return 'financing';
        foreach($counterTypes as $type)if($type===AccountType::Asset)return 'investing';
        return 'operating';
    }
}
