<?php
declare(strict_types=1);
namespace Nexa\Banking;

use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\Account;
use Nexa\Core\DomainException;

final class ReconciliationService
{
    /** @param array<int,Account> $accounts */
    public function assertMatch(BankStatementLine $bank, JournalEntry $journal, array $accounts): void
    {
        if ($bank->companyId !== $journal->companyId) throw new DomainException('Transaksi bank dan jurnal berasal dari badan usaha berbeda.');
        $cashDelta = 0;
        foreach ($journal->lines as $line) {
            $account = $accounts[$line->accountId] ?? null;
            if ($account?->isCash) $cashDelta += $line->debit - $line->credit;
        }
        $expected = $bank->direction === 'in' ? $bank->amount : -$bank->amount;
        if ($cashDelta !== $expected) throw new DomainException('Nominal/direction transaksi bank tidak cocok dengan jurnal.');
    }

    /** @param list<BankStatementLine> $bankLines @param list<array{id:int,entry:JournalEntry}> $journals @param array<int,Account> $accounts */
    public function suggest(array $bankLines, array $journals, array $accounts, int $dateToleranceDays = 3): array
    {
        $suggestions = [];
        foreach ($bankLines as $bank) {
            $best = null; $bestScore = -1;
            foreach ($journals as $row) {
                $entry = $row['entry'];
                if ($entry->companyId !== $bank->companyId) continue;
                try { $this->assertMatch($bank, $entry, $accounts); } catch (\Throwable) { continue; }
                $days = abs((new \DateTimeImmutable($bank->date))->diff(new \DateTimeImmutable($entry->date))->days);
                if ($days > $dateToleranceDays) continue;
                $score = 100 - ($days * 10);
                if (stripos($entry->description, substr($bank->description, 0, min(12, strlen($bank->description)))) !== false) $score += 10;
                if ($score > $bestScore) { $bestScore = $score; $best = (int)$row['id']; }
            }
            if ($best !== null) $suggestions[] = ['bank_id'=>$bank->id,'journal_id'=>$best,'score'=>$bestScore];
        }
        return $suggestions;
    }
}
