<?php
declare(strict_types=1);
namespace Nexa\Reports;
final class KpiService
{
    public function finance(array $pl,array $bs,int $cash,int $receivables,int $payables):array{$revenue=(int)($pl['revenue']??0);$expense=(int)($pl['expense']??0);$profit=$revenue-$expense;$assets=(int)($bs['assets']??0);$liabilities=(int)($bs['liabilities']??0);return['revenue'=>$revenue,'expense'=>$expense,'profit'=>$profit,'margin'=>$revenue?($profit/$revenue*100):0.0,'cash'=>$cash,'receivables'=>$receivables,'payables'=>$payables,'current_net_working_capital'=>$cash+$receivables-$payables,'debt_to_assets'=>$assets?($liabilities/$assets*100):0.0];}
}
