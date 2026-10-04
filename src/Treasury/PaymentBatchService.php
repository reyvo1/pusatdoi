<?php
declare(strict_types=1);
namespace Nexa\Treasury;

use Nexa\Core\DomainException;

final class PaymentBatchService
{
    /** @return array{total:int,lines:list<array{invoice_id:int,amount:int,note:string}>} */
    public function normalizeLines(array $rows): array
    {
        if(!$rows) throw new DomainException('Payment batch minimal satu invoice.');
        $seen=[];$out=[];$total=0;
        foreach($rows as $i=>$r){
            $invoice=(int)($r['invoice_id']??0);$amount=(int)round((float)($r['amount']??0));$note=trim((string)($r['note']??''));
            if($invoice<=0||$amount<=0) throw new DomainException('Baris payment #'.($i+1).' tidak valid.');
            if(isset($seen[$invoice])) throw new DomainException('Invoice duplikat dalam payment batch.');
            $seen[$invoice]=1;$out[]=['invoice_id'=>$invoice,'amount'=>$amount,'note'=>$note];$total+=$amount;
        }
        return ['total'=>$total,'lines'=>$out];
    }

    public function nextBatchNo(int $company,string $date,int $sequence): string
    {
        return 'APB-'.date('Ym',strtotime($date)).'-'.str_pad((string)$company,2,'0',STR_PAD_LEFT).'-'.str_pad((string)$sequence,4,'0',STR_PAD_LEFT);
    }
}
