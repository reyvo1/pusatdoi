<?php
declare(strict_types=1);
namespace Nexa\Expense;

final class DailyExpenseService
{
    public function normalize(array $input): array
    {
        $company=(int)($input['company_id']??0);
        $date=(string)($input['date']??date('Y-m-d'));
        $description=trim((string)($input['description']??''));
        $branch=($input['branch_id']??'')!==''?(int)$input['branch_id']:null;
        $department=($input['department_id']??'')!==''?(int)$input['department_id']:null;
        $currency=strtoupper(trim((string)($input['currency']??'IDR')));
        $rate=(float)($input['exchange_rate']??1);
        $lines=$input['expense_lines']??[];
        $payments=$input['payment_lines']??[];
        if(is_string($lines))$lines=json_decode($lines,true);
        if(is_string($payments))$payments=json_decode($payments,true);
        if($company<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||strlen($description)<3)throw new \InvalidArgumentException('Header pengeluaran tidak valid.');
        if(!preg_match('/^[A-Z]{3}$/',$currency)||$rate<=0)throw new \InvalidArgumentException('Currency / exchange rate tidak valid.');
        if(!is_array($lines)||!is_array($payments)||!$lines||!$payments)throw new \InvalidArgumentException('Pengeluaran dan pembayaran wajib diisi.');
        $expenseTotal=0;$paymentTotal=0;$normalized=[];$payNorm=[];
        foreach($lines as $r){$amount=(int)round((float)($r['amount']??0));if($amount<=0)continue;$normalized[]=['category_id'=>(int)($r['category_id']??0),'account_id'=>(int)($r['account_id']??0),'amount'=>$amount,'tax_profile_id'=>($r['tax_profile_id']??'')!==''?(int)$r['tax_profile_id']:null,'tax_mode'=>(string)($r['tax_mode']??'none'),'description'=>trim((string)($r['description']??''))];$expenseTotal+=$amount;}
        foreach($payments as $r){$amount=(int)round((float)($r['amount']??0));if($amount<=0)continue;$payNorm[]=['account_id'=>(int)($r['account_id']??0),'amount'=>$amount,'method'=>trim((string)($r['method']??'bank')),'reference'=>trim((string)($r['reference']??''))];$paymentTotal+=$amount;}
        if(!$normalized||!$payNorm)throw new \RuntimeException('Pengeluaran dan pembayaran wajib memiliki nominal.');
        return compact('company','date','description','branch','department','currency','rate','normalized','payNorm','expenseTotal','paymentTotal');
    }
}
