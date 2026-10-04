<?php
declare(strict_types=1);
namespace Nexa\Organization;
use Nexa\Core\DomainException;
final readonly class Department
{
    public function __construct(public int $id,public int $companyId,public ?int $branchId,public string $code,public string $name,public ?string $costCenter=null,public ?string $profitCenter=null,public bool $active=true)
    {
        if($id<=0||$companyId<=0)throw new DomainException('Department ID/company tidak valid.');
        if(!preg_match('/^[A-Z0-9_-]{2,30}$/',$code)||trim($name)==='')throw new DomainException('Data departemen tidak valid.');
    }
}
