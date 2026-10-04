<?php
declare(strict_types=1);
namespace Nexa\Tax;

use Nexa\Core\DomainException;

final readonly class TaxProfile
{
    public function __construct(
        public string $code,
        public string $name,
        public float $ratePercent,
        public bool $inclusive = false,
    ) {
        if ($ratePercent < 0 || $ratePercent > 100) throw new DomainException('Tarif pajak tidak valid.');
    }
}
