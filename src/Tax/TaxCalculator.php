<?php
declare(strict_types=1);
namespace Nexa\Tax;

final class TaxCalculator
{
    public function calculate(int $grossOrBase, TaxProfile $profile): array
    {
        $rate = $profile->ratePercent / 100;
        if ($profile->inclusive) {
            $base = $rate > 0 ? (int)round($grossOrBase / (1 + $rate)) : $grossOrBase;
            $tax = $grossOrBase - $base;
            return ['base'=>$base,'tax'=>$tax,'gross'=>$grossOrBase,'rate'=>$profile->ratePercent,'inclusive'=>true];
        }
        $tax = (int)round($grossOrBase * $rate);
        return ['base'=>$grossOrBase,'tax'=>$tax,'gross'=>$grossOrBase+$tax,'rate'=>$profile->ratePercent,'inclusive'=>false];
    }
}
