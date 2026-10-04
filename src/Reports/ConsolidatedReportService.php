<?php
declare(strict_types=1);
namespace Nexa\Reports;

final class ConsolidatedReportService
{
    /** @param list<array{company_id:int,revenue:int,expense:int,assets:int,liabilities:int,equity:int}> $entities */
    public function consolidate(array $entities, array $eliminations = []): array
    {
        $out=['revenue'=>0,'expense'=>0,'assets'=>0,'liabilities'=>0,'equity'=>0];
        foreach($entities as $e)foreach(array_keys($out) as $k)$out[$k]+=(int)($e[$k]??0);
        foreach($eliminations as $e){foreach(array_keys($out) as $k)$out[$k]+=(int)($e[$k]??0);}
        $out['profit']=$out['revenue']-$out['expense'];$out['net_assets']=$out['assets']-$out['liabilities'];$out['balance_difference']=$out['assets']-($out['liabilities']+$out['equity']+$out['profit']);
        return $out;
    }
}
