<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;

final readonly class Account
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public AccountType $type,
        public ?int $companyId = null,
        public bool $isCash = false,
        public bool $active = true,
    ) {
        if ($id <= 0 || trim($code) === '' || trim($name) === '') {
            throw new DomainException('Akun tidak valid.');
        }
    }

    public function belongsTo(int $companyId): bool
    {
        return $this->companyId === null || $this->companyId === 0 || $this->companyId === $companyId;
    }
}
