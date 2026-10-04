<?php
declare(strict_types=1);
namespace Nexa\Assets;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final readonly class Asset
{
    public function __construct(
        public int $id,
        public int $companyId,
        public string $name,
        public string $acquisitionDate,
        public int $cost,
        public int $usefulLifeMonths,
        public int $residualValue = 0,
        public string $method = 'straight_line',
    ) {
        if ($id <= 0 || $companyId <= 0 || $cost < 0 || $usefulLifeMonths <= 0 || $residualValue < 0 || $residualValue > $cost) throw new DomainException('Data aset tidak valid.');
        Validation::date($acquisitionDate);
        if ($method !== 'straight_line') throw new DomainException('Metode depresiasi belum didukung.');
    }
}
