<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;
use Nexa\Security\Role;

final readonly class ApprovalPolicy
{
    public function __construct(public int $threshold = 25_000_000) {}

    public function requiresApproval(int $amount, Role|string $role): bool
    {
        $role = $role instanceof Role ? $role->value : $role;
        if ($amount < $this->threshold) return false;
        return !in_array($role, [Role::GroupOwner->value, Role::GroupFinance->value], true);
    }

    public function assertDecisionRole(Role|string $role): void
    {
        $role = $role instanceof Role ? $role->value : $role;
        if (!in_array($role, [Role::GroupOwner->value, Role::GroupFinance->value], true)) {
            throw new DomainException('Hanya Group Owner/Finance yang dapat memutuskan approval.');
        }
    }
}
