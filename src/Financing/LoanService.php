<?php
declare(strict_types=1);
namespace Nexa\Financing;

use Nexa\Core\DomainException;

final class LoanService
{
    public function facility(array $input): array
    {
        $company=(int)($input['company_id']??0);$lender=trim((string)($input['lender_name']??''));$date=(string)($input['start_date']??'');$maturity=(string)($input['maturity_date']??'');$principal=(int)round((float)($input['principal']??0));$rate=(float)($input['annual_interest_rate']??0);$liability=(int)($input['liability_account_id']??0);$cash=(int)($input['cash_account_id']??0);$interest=(int)($input['interest_expense_account_id']??0);
        if($company<=0||strlen($lender)<2||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$maturity)||$maturity<$date||$principal<=0||$rate<0||$liability<=0||$cash<=0||$interest<=0) throw new DomainException('Data fasilitas pinjaman tidak valid.');
        return ['company_id'=>$company,'lender_name'=>$lender,'start_date'=>$date,'maturity_date'=>$maturity,'principal'=>$principal,'annual_interest_rate'=>$rate,'liability_account_id'=>$liability,'cash_account_id'=>$cash,'interest_expense_account_id'=>$interest,'reference_no'=>trim((string)($input['reference_no']??''))?:null];
    }

    public function payment(array $loan,array $input): array
    {
        $principal=(int)round((float)($input['principal_amount']??0));$interest=(int)round((float)($input['interest_amount']??0));$cash=(int)($input['cash_account_id']??($loan['cash_account_id']??0));$date=(string)($input['payment_date']??'');$outstanding=(int)round((float)($loan['outstanding_principal']??$loan['principal']??0));
        if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$principal<0||$interest<0||($principal+$interest)<=0||$cash<=0) throw new DomainException('Data pembayaran pinjaman tidak valid.');
        if($principal>$outstanding) throw new DomainException('Pembayaran pokok melebihi outstanding pinjaman.');
        return ['payment_date'=>$date,'principal_amount'=>$principal,'interest_amount'=>$interest,'cash_account_id'=>$cash,'total_amount'=>$principal+$interest,'reference_no'=>trim((string)($input['reference_no']??''))?:null];
    }
}
