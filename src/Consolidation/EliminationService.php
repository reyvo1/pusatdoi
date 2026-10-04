<?php
declare(strict_types=1);
namespace Nexa\Consolidation;

use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final class EliminationService
{
    public function create(int $groupCompanyId, string $period, int $receivableAccountId, int $payableAccountId, int $amount, string $description): JournalEntry
    {
        Validation::period($period);
        if ($amount <= 0) throw new DomainException('Nominal eliminasi harus positif.');
        return new JournalEntry(
            $groupCompanyId,
            $period . '-28',
            $description,
            [new JournalLine($payableAccountId, $amount, 0, 'Eliminate intercompany payable'), new JournalLine($receivableAccountId, 0, $amount, 'Eliminate intercompany receivable')],
            'consolidation_elimination',
            null,
            null,
            ['period'=>$period,'non_posting_entity_books'=>true]
        );
    }
}
