<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;

final class ReversalService
{
    public function reverse(JournalEntry $original, string $reversalDate, string $reason, int $originalId): JournalEntry
    {
        if ($original->source === 'reversal') throw new DomainException('Jurnal reversal tidak boleh direversal ulang tanpa proses koreksi khusus.');
        $lines = array_map(static fn(JournalLine $line) => $line->reverse(), $original->lines);
        return new JournalEntry(
            $original->companyId,
            $reversalDate,
            trim($reason) !== '' ? 'Reversal: ' . trim($reason) : 'Reversal jurnal',
            $lines,
            'reversal',
            $originalId,
            $original->intercompanyCompanyId,
            ['original_description'=>$original->description]
        );
    }
}
