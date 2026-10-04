<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final class FiscalPeriodPolicy
{
    /** @param array<string,string> $statuses map YYYY-MM => open|soft_closed|closed */
    public function __construct(private readonly array $statuses = []) {}

    public function statusForDate(string $date): string
    {
        Validation::date($date);
        return $this->statuses[substr($date, 0, 7)] ?? 'open';
    }

    public function assertPostingAllowed(string $date): void
    {
        $status = $this->statusForDate($date);
        if ($status === 'closed') {
            throw new DomainException('Periode akuntansi ' . substr($date, 0, 7) . ' sudah ditutup.');
        }
    }

    public function assertHardCloseAllowed(string $period, int $pendingApprovals, int $unmatchedBankLines, bool $trialBalanceBalanced): void
    {
        Validation::period($period);
        if ($pendingApprovals > 0) throw new DomainException('Periode tidak dapat ditutup karena masih ada approval pending.');
        if ($unmatchedBankLines > 0) throw new DomainException('Periode tidak dapat ditutup karena masih ada transaksi bank belum direkonsiliasi.');
        if (!$trialBalanceBalanced) throw new DomainException('Periode tidak dapat ditutup karena neraca saldo tidak balance.');
    }
}
