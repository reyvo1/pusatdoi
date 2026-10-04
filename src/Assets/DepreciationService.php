<?php
declare(strict_types=1);
namespace Nexa\Assets;

final class DepreciationService
{
    public function monthly(Asset $asset): int
    {
        return (int)round(($asset->cost - $asset->residualValue) / $asset->usefulLifeMonths);
    }

    public function accumulated(Asset $asset, string $asOf): int
    {
        $start = new \DateTimeImmutable($asset->acquisitionDate);
        $end = new \DateTimeImmutable($asOf);
        if ($end < $start) return 0;
        $diff = $start->diff($end);
        $months = min($asset->usefulLifeMonths, ($diff->y*12)+$diff->m+1);
        return min($asset->cost-$asset->residualValue, $this->monthly($asset)*$months);
    }

    public function bookValue(Asset $asset, string $asOf): int
    {
        return max($asset->residualValue, $asset->cost - $this->accumulated($asset, $asOf));
    }

    public function schedule(Asset $asset): array
    {
        $rows=[]; $date=(new \DateTimeImmutable($asset->acquisitionDate))->modify('first day of this month');
        $monthly=$this->monthly($asset); $acc=0;
        for($i=1;$i<=$asset->usefulLifeMonths;$i++){
            $dep = $i===$asset->usefulLifeMonths ? ($asset->cost-$asset->residualValue-$acc) : min($monthly, $asset->cost-$asset->residualValue-$acc);
            $acc += $dep;
            $rows[]=['period'=>$date->format('Y-m'),'depreciation'=>$dep,'accumulated'=>$acc,'book_value'=>$asset->cost-$acc];
            $date=$date->modify('+1 month');
        }
        return $rows;
    }
}
