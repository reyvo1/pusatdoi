<?php
declare(strict_types=1);
namespace Nexa\Consolidation;
use Nexa\Reports\ConsolidatedReportService;
final class ConsolidationService
{
    public function execute(ConsolidationRun $run):array{$run->validate();$report=(new ConsolidatedReportService())->consolidate(array_values($run->entities),$run->eliminations);$run->post();return['run'=>$run,'report'=>$report];}
}
