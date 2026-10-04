<?php
declare(strict_types=1);
namespace Nexa\Tax;
use Nexa\Core\DomainException;
final class TaxTransactionService
{
    public function summary(array $rows):array{$input=$output=$withholding=0;foreach($rows as$r){$amount=(int)round((float)($r['tax_amount']??0));match($r['direction']??''){ 'input'=>$input+=$amount,'output'=>$output+=$amount,'withholding'=>$withholding+=$amount,default=>null};}return['input'=>$input,'output'=>$output,'withholding'=>$withholding,'net_payable'=>$output-$input-$withholding];}
    public function assertProfileMapping(TaxProfile $profile,?int $inputAccountId,?int $outputAccountId):void{if($profile->ratePercent<=0)return;if($inputAccountId===null&&$outputAccountId===null)throw new DomainException('Tax profile aktif harus memiliki mapping akun pajak.');}
}
