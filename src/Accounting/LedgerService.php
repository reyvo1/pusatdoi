<?php
declare(strict_types=1);
namespace Nexa\Accounting;

final class LedgerService
{
    /**
     * @param list<array<string,mixed>> $legacyEntries
     * @param array<int,Account> $accounts
     * @return array<int,array{debit:int,credit:int,balance:int}>
     */
    public function balances(array $legacyEntries, array $accounts, ?int $companyId = null, ?string $period = null): array
    {
        $rows = [];
        foreach ($accounts as $id => $account) {
            $rows[$id] = ['debit' => 0, 'credit' => 0, 'balance' => 0];
        }
        foreach ($legacyEntries as $entry) {
            if (($entry['status'] ?? '') !== 'posted') continue;
            if ($companyId && (int)($entry['company_id'] ?? 0) !== $companyId) continue;
            if ($period && !str_starts_with((string)($entry['date'] ?? ''), $period)) continue;
            foreach (($entry['lines'] ?? []) as $line) {
                $id = (int)($line['account_id'] ?? 0);
                if (!isset($rows[$id], $accounts[$id])) continue;
                $debit = (int)round((float)($line['debit'] ?? 0));
                $credit = (int)round((float)($line['credit'] ?? 0));
                $rows[$id]['debit'] += $debit;
                $rows[$id]['credit'] += $credit;
            }
        }
        foreach ($rows as $id => &$row) {
            $normal = $accounts[$id]->type->normalSide();
            $row['balance'] = $normal === 'debit' ? $row['debit'] - $row['credit'] : $row['credit'] - $row['debit'];
        }
        unset($row);
        return $rows;
    }
}
