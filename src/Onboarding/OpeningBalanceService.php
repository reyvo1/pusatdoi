<?php
declare(strict_types=1);
namespace Nexa\Onboarding;

final class OpeningBalanceService
{
    public function normalize(array $input): array
    {
        $company=(int)($input['company_id']??0);$date=(string)($input['opening_date']??'');$year=(int)substr($date,0,4);$lines=$input['lines']??[];if(is_string($lines))$lines=json_decode($lines,true);
        if($company<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||!is_array($lines)||count($lines)<2)throw new \InvalidArgumentException('Data saldo awal tidak valid.');
        $debit=$credit=0;$normalized=[];
        foreach($lines as $r){$d=(int)round((float)($r['debit']??0));$c=(int)round((float)($r['credit']??0));$aid=(int)($r['account_id']??0);if($aid<=0||$d<0||$c<0||($d>0&&$c>0)||($d===0&&$c===0))throw new \InvalidArgumentException('Baris saldo awal tidak valid.');$normalized[]=['account_id'=>$aid,'debit'=>$d,'credit'=>$c,'description'=>trim((string)($r['description']??'Saldo awal'))];$debit+=$d;$credit+=$c;}
        if($debit<=0||$debit!==$credit)throw new \RuntimeException('Saldo awal harus balance.');
        return compact('company','date','year','normalized','debit','credit');
    }
}
