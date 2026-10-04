<?php
declare(strict_types=1);
namespace Nexa\Reports;
use Nexa\Accounting\Account;
final class TrendService
{
    public function __construct(private readonly StatementService $statements=new StatementService()){}
    /** @param list<array<string,mixed>> $entries @param array<int,Account> $accounts */
    public function monthly(array $entries,array $accounts,string $endPeriod,int $months=12,?int $companyId=null):array{$end=\DateTimeImmutable::createFromFormat('!Y-m',$endPeriod);$rows=[];for($i=$months-1;$i>=0;$i--){$period=$end->modify("-$i month")->format('Y-m');$pl=$this->statements->profitLoss($entries,$accounts,$companyId,$period);$rows[]=['period'=>$period,'revenue'=>$pl['revenue'],'expense'=>$pl['expense'],'profit'=>$pl['profit'],'margin'=>$pl['margin']];}return$rows;}
}
