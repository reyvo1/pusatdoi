<?php
declare(strict_types=1);
namespace Nexa\Organization;
use Nexa\Core\DomainException;
use Nexa\Core\Validation;
final readonly class Company
{
    public function __construct(public int $id, public string $code, public string $name, public string $businessType, public string $baseCurrency='IDR', public bool $active=true)
    {
        if($id<=0)throw new DomainException('Company ID tidak valid.');
        if(!preg_match('/^[A-Z0-9_-]{2,20}$/',$code))throw new DomainException('Kode badan usaha tidak valid.');
        Validation::requiredString($name,'Nama badan usaha',3,160);Validation::requiredString($businessType,'Jenis usaha',2,80);Validation::currency($baseCurrency);
    }
}
