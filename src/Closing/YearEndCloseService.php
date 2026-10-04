<?php
declare(strict_types=1);
namespace Nexa\Closing;

final class YearEndCloseService
{
    public function buildClosingLines(array $balances,int $retainedEarningsAccountId): array
    {
        $lines=[];$profit=0;
        foreach($balances as $r){$type=(string)($r['type']??'');$aid=(int)($r['account_id']??$r['id']??0);$bal=(float)($r['balance']??0);if(!$aid||abs($bal)<0.00001)continue;
            if($type==='revenue'){$amount=(int)round(abs($bal));$lines[]=['account_id'=>$aid,'debit'=>$amount,'credit'=>0,'description'=>'Year-end close revenue'];$profit+=$amount;}
            elseif($type==='expense'){$amount=(int)round(abs($bal));$lines[]=['account_id'=>$aid,'debit'=>0,'credit'=>$amount,'description'=>'Year-end close expense'];$profit-=$amount;}
        }
        if($profit>0)$lines[]=['account_id'=>$retainedEarningsAccountId,'debit'=>0,'credit'=>$profit,'description'=>'Transfer laba ke saldo laba'];
        elseif($profit<0)$lines[]=['account_id'=>$retainedEarningsAccountId,'debit'=>abs($profit),'credit'=>0,'description'=>'Transfer rugi ke saldo laba'];
        if(count($lines)<2)throw new \RuntimeException('Tidak ada saldo laba/rugi yang perlu ditutup.');
        return ['lines'=>$lines,'profit'=>$profit];
    }
}
