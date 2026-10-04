<?php
declare(strict_types=1);
namespace Nexa\Security;

use Nexa\Core\DomainException;

final class AuthorizationService
{
    /** @var array<string, list<string>> */
    private array $matrix;

    public function __construct(?array $matrix = null)
    {
        $all = array_map(static fn(Permission $p) => $p->value, Permission::cases());
        $finance = array_values(array_filter($all, static fn(string $p) => !in_array($p, ['company.manage', 'user.manage', 'backup.manage', 'onboarding.manage'], true)));
        $entity = [
            'dashboard.view','company.view','journal.view','journal.post','income.manage','expense.manage','treasury.manage','recurring.manage','forecast.manage','arap.manage',
            'bank.manage','bank.import','bank.reconcile','budget.manage','asset.manage','tax.manage','fx.manage',
            'import.manage','document.manage','document.view','payment_batch.manage','advance.manage','financing.manage','equity.manage','planning.manage','notification.view',
            'report.view','report.export','integration.manage'
        ];
        $auditor = ['dashboard.view','company.view','journal.view','report.view','report.export','audit.view','document.view','notification.view'];
        $viewer = ['dashboard.view','company.view','journal.view','report.view','notification.view'];
        $this->matrix = $matrix ?? [
            Role::GroupOwner->value => $all,
            Role::GroupFinance->value => $finance,
            Role::EntityAdmin->value => $entity,
            Role::Auditor->value => $auditor,
            Role::Viewer->value => $viewer,
        ];
    }

    public function matrixForRuntime(): array { return $this->matrix; }

    public function can(Role|string $role, Permission|string $permission): bool
    {
        $role = $role instanceof Role ? $role->value : $role;
        $permission = $permission instanceof Permission ? $permission->value : $permission;
        return in_array($permission, $this->matrix[$role] ?? [], true);
    }

    public function assert(Role|string $role, Permission|string $permission): void
    {
        if (!$this->can($role, $permission)) {
            $permission = $permission instanceof Permission ? $permission->value : $permission;
            throw new DomainException("Akses ditolak untuk {$permission}.");
        }
    }

    public function scopeCompanyIds(Role|string $role, ?int $assignedCompanyId, array $allCompanyIds): array
    {
        $role = $role instanceof Role ? $role->value : $role;
        if ($role === Role::EntityAdmin->value) {
            if (!$assignedCompanyId) {
                return [];
            }
            return in_array($assignedCompanyId, $allCompanyIds, true) ? [$assignedCompanyId] : [];
        }
        return $allCompanyIds;
    }
}
