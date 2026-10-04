<?php
declare(strict_types=1);
namespace Nexa\Treasury;

final class BankTransferService
{
    public function validate(array $input): array
    {
        $company=(int)($input['company_id']??0);$from=(int)($input['from_account_id']??0);$to=(int)($input['to_account_id']??0);$date=(string)($input['date']??date('Y-m-d'));$amount=(int)round((float)($input['amount']??0));$fee=(int)round((float)($input['fee_amount']??0));$feeAccount=(int)($input['fee_account_id']??0);$reference=trim((string)($input['reference']??''));$description=trim((string)($input['description']??'Transfer antar rekening'));
        if($company<=0||$from<=0||$to<=0||$from===$to||$amount<=0||$fee<0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new \InvalidArgumentException('Data transfer bank tidak valid.');
        if($fee>0&&$feeAccount<=0)throw new \InvalidArgumentException('Akun biaya bank wajib untuk transfer yang memiliki fee.');
        return compact('company','from','to','date','amount','fee','feeAccount','reference','description');
    }
}
