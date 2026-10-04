<?php
declare(strict_types=1);
namespace Nexa\Organization;
use Nexa\Core\DomainException;
final class OrganizationService
{
    /** @param list<Company> $companies @param list<Branch> $branches @param list<Department> $departments */
    public function tree(array $companies,array $branches,array $departments): array
    {
        $out=[];foreach($companies as $company){$node=['company'=>$company,'branches'=>[],'unassigned_departments'=>[]];foreach($branches as $branch){if($branch->companyId!==$company->id)continue;$bn=['branch'=>$branch,'departments'=>[]];foreach($departments as $department)if($department->companyId===$company->id&&$department->branchId===$branch->id)$bn['departments'][]=$department;$node['branches'][]=$bn;}foreach($departments as $department)if($department->companyId===$company->id&&$department->branchId===null)$node['unassigned_departments'][]=$department;$out[]=$node;}return $out;
    }
    /** @param list<Branch> $branches */
    public function assertBranchBelongs(int $companyId,?int $branchId,array $branches): void
    {
        if($branchId===null)return;foreach($branches as $b)if($b->id===$branchId&&$b->companyId===$companyId&&$b->active)return;throw new DomainException('Cabang tidak tersedia untuk badan usaha ini.');
    }
}
