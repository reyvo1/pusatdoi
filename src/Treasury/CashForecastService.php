<?php
declare(strict_types=1);
namespace Nexa\Treasury;

final class CashForecastService
{
    public function summarize(array $items,string $from,string $to): array
    {
        $in=$out=0;$weightedIn=$weightedOut=0;$rows=[];foreach($items as $r){$d=(string)($r['forecast_date']??'');if($d<$from||$d>$to||($r['status']??'planned')==='cancelled')continue;$a=(float)($r['amount']??0);$p=max(0,min(100,(float)($r['probability_pct']??100)))/100;if(($r['direction']??'outflow')==='inflow'){$in+=$a;$weightedIn+=$a*$p;}else{$out+=$a;$weightedOut+=$a*$p;}$rows[]=$r;}return ['inflow'=>$in,'outflow'=>$out,'net'=>$in-$out,'weighted_inflow'=>$weightedIn,'weighted_outflow'=>$weightedOut,'weighted_net'=>$weightedIn-$weightedOut,'rows'=>$rows];
    }
}
