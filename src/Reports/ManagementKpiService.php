<?php
declare(strict_types=1);
namespace Nexa\Reports;

final class ManagementKpiService
{
    public function calculate(float $revenue,float $expense,float $cash,float $receivables,float $payables,float $loanOutstanding=0.0):array
    {
        $profit=$revenue-$expense;$monthlyRevenue=max(0.0,$revenue);$monthlyExpense=max(0.0,$expense);$currentAssets=$cash+$receivables;$currentLiabilities=$payables+$loanOutstanding;
        return [
            'net_margin_pct'=>$revenue>0?$profit/$revenue*100:0.0,
            'current_ratio'=>$currentLiabilities>0?$currentAssets/$currentLiabilities:0.0,
            'cash_ratio'=>$currentLiabilities>0?$cash/$currentLiabilities:0.0,
            'dso_days'=>$monthlyRevenue>0?$receivables/$monthlyRevenue*30:0.0,
            'dpo_days'=>$monthlyExpense>0?$payables/$monthlyExpense*30:0.0,
            'debt_to_monthly_revenue'=>$monthlyRevenue>0?$loanOutstanding/$monthlyRevenue:0.0,
            'working_capital'=>$currentAssets-$currentLiabilities,
        ];
    }
}
