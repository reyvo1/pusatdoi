<?php
declare(strict_types=1);
namespace Nexa\Banking;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final readonly class BankStatementLine
{
    public function __construct(
        public int $id,
        public int $companyId,
        public string $date,
        public string $description,
        public int $amount,
        public string $direction,
        public ?string $externalRef = null,
    ) {
        if ($id <= 0 || $companyId <= 0 || $amount <= 0) throw new DomainException('Bank statement line tidak valid.');
        Validation::date($date);
        if (!in_array($direction, ['in','out'], true)) throw new DomainException('Direction bank harus in/out.');
    }
}
