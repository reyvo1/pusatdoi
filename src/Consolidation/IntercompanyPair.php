<?php
declare(strict_types=1);
namespace Nexa\Consolidation;

final readonly class IntercompanyPair
{
    public function __construct(
        public int $fromCompanyId,
        public int $toCompanyId,
        public int $amount,
        public string $reference,
        public string $period,
    ) {}
}
