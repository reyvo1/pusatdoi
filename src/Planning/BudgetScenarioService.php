<?php
declare(strict_types=1);
namespace Nexa\Planning;

use Nexa\Core\DomainException;

final class BudgetScenarioService
{
    public function scenario(array $input): array
    {
        $company=(int)($input['company_id']??0);$year=(int)($input['fiscal_year']??0);$code=strtoupper(trim((string)($input['code']??'')));$name=trim((string)($input['name']??''));$type=(string)($input['scenario_type']??'forecast');
        if($company<=0||$year<2000||$year>2200||!preg_match('/^[A-Z0-9._-]{2,30}$/',$code)||strlen($name)<2||!in_array($type,['budget','forecast','best_case','base_case','worst_case'],true)) throw new DomainException('Budget scenario tidak valid.');
        return ['company_id'=>$company,'fiscal_year'=>$year,'code'=>$code,'name'=>$name,'scenario_type'=>$type,'status'=>'draft'];
    }

    public function line(array $input): array
    {
        $scenario=(int)($input['scenario_id']??0);$account=(int)($input['account_id']??0);$period=(int)($input['period']??0);$amount=(int)round((float)($input['amount']??0));$branch=($input['branch_id']??'')!==''?(int)$input['branch_id']:null;$dept=($input['department_id']??'')!==''?(int)$input['department_id']:null;
        if($scenario<=0||$account<=0||$period<1||$period>12||$amount<0) throw new DomainException('Budget scenario line tidak valid.');
        return ['scenario_id'=>$scenario,'account_id'=>$account,'period'=>$period,'amount'=>$amount,'branch_id'=>$branch,'department_id'=>$dept];
    }

    public function compare(array $linesA,array $linesB): array
    {
        $sum=static fn(array $rows):int=>array_sum(array_map(static fn(array $r):int=>(int)($r['amount']??0),$rows));$a=$sum($linesA);$b=$sum($linesB);return ['scenario_a'=>$a,'scenario_b'=>$b,'variance'=>$b-$a,'variance_percent'=>$a?($b-$a)/$a*100:0.0];
    }
}
