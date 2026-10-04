<?php
declare(strict_types=1);
namespace Nexa\Organization;
use Nexa\Core\DomainException;
final readonly class Branch
{
    public function __construct(public int $id,public int $companyId,public string $code,public string $name,public string $timezone='Asia/Makassar',public bool $active=true)
    {
        if($id<=0||$companyId<=0)throw new DomainException('Branch ID/company tidak valid.');
        if(!preg_match('/^[A-Z0-9_-]{2,30}$/',$code))throw new DomainException('Kode cabang tidak valid.');
        if(trim($name)==='')throw new DomainException('Nama cabang wajib diisi.');
        try{new \DateTimeZone($timezone);}catch(\Throwable){throw new DomainException('Timezone cabang tidak valid.');}
    }
}
