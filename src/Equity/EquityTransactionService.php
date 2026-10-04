<?php
declare(strict_types=1);
namespace Nexa\Equity;

use Nexa\Core\DomainException;

final class EquityTransactionService
{
    public function normalize(array $input): array
    {
        $type=(string)($input['transaction_type']??'');$allowed=['capital_contribution','dividend_payment','owner_draw'];$company=(int)($input['company_id']??0);$date=(string)($input['transaction_date']??'');$amount=(int)round((float)($input['amount']??0));$cash=(int)($input['cash_account_id']??0);$equity=(int)($input['equity_account_id']??0);
        if(!in_array($type,$allowed,true)||$company<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$amount<=0||$cash<=0||$equity<=0) throw new DomainException('Transaksi modal/dividen tidak valid.');
        return ['transaction_type'=>$type,'company_id'=>$company,'transaction_date'=>$date,'amount'=>$amount,'cash_account_id'=>$cash,'equity_account_id'=>$equity,'reference_no'=>trim((string)($input['reference_no']??''))?:null,'description'=>trim((string)($input['description']??''))?:str_replace('_',' ',ucwords($type,'_'))];
    }
}
