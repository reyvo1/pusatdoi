<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;

final class JournalValidator
{
    /** @param list<JournalLine> $lines */
    public static function isBalanced(array $lines): bool
    {
        if (count($lines) < 2) return false;
        $debit = 0; $credit = 0;
        foreach ($lines as $line) {
            if (!$line instanceof JournalLine) return false;
            $debit += $line->debit;
            $credit += $line->credit;
        }
        return $debit > 0 && $debit === $credit;
    }

    /** @param list<JournalLine> $lines */
    public static function assertBalanced(array $lines): void
    {
        if (!self::isBalanced($lines)) {
            throw new DomainException('Jurnal tidak balance: total debit harus sama dengan total kredit.');
        }
    }

    /** @param array<int,Account> $accounts */
    public static function assertAccountsBelongToCompany(array $accounts, JournalEntry $entry): void
    {
        foreach ($entry->lines as $line) {
            $account = $accounts[$line->accountId] ?? null;
            if (!$account || !$account->active) throw new DomainException('Akun jurnal tidak ditemukan atau tidak aktif.');
            if (!$account->belongsTo($entry->companyId)) throw new DomainException('Akun jurnal bukan milik badan usaha yang dipilih.');
        }
    }
}
