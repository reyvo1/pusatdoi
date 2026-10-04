<?php
declare(strict_types=1);
namespace Nexa\Budget;

final class BudgetVarianceService
{
    public function calculate(int $budget, int $actual, string $accountType = 'expense'): array
    {
        $variance = $actual - $budget;
        $favorable = $accountType === 'revenue' ? $actual >= $budget : $actual <= $budget;
        $percent = $budget !== 0 ? ($variance / $budget) * 100 : 0.0;
        return ['budget'=>$budget,'actual'=>$actual,'variance'=>$variance,'variance_percent'=>$percent,'favorable'=>$favorable];
    }

    /** @param list<array{budget:int,actual:int,account_type?:string}> $rows */
    public function portfolio(array $rows): array
    {
        $totalBudget=$totalActual=0; $favorable=0; $unfavorable=0;
        foreach($rows as $row){$r=$this->calculate((int)$row['budget'],(int)$row['actual'],(string)($row['account_type']??'expense'));$totalBudget+=$r['budget'];$totalActual+=$r['actual'];$r['favorable']?$favorable++:$unfavorable++;}
        return ['budget'=>$totalBudget,'actual'=>$totalActual,'variance'=>$totalActual-$totalBudget,'favorable_count'=>$favorable,'unfavorable_count'=>$unfavorable];
    }
}
