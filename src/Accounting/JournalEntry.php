<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final class JournalEntry
{
    /** @param list<JournalLine> $lines */
    public function __construct(
        public readonly int $companyId,
        public readonly string $date,
        public readonly string $description,
        public readonly array $lines,
        public readonly string $source = 'manual',
        public readonly ?int $reversalOf = null,
        public readonly ?int $intercompanyCompanyId = null,
        public readonly array $metadata = [],
        public readonly ?int $branchId = null,
        public readonly ?int $departmentId = null,
        public readonly ?int $batchId = null,
    ) {
        if ($companyId <= 0) throw new DomainException('Badan usaha jurnal tidak valid.');
        Validation::date($date);
        if (trim($description) === '') throw new DomainException('Deskripsi jurnal wajib diisi.');
        if (count($lines) < 2) throw new DomainException('Jurnal minimal memiliki dua baris.');
        JournalValidator::assertBalanced($lines);
        foreach ($lines as $line) {
            if ($line->branchId !== null && $branchId !== null && $line->branchId !== $branchId) {
                throw new DomainException('Dimensi branch baris jurnal tidak konsisten dengan header.');
            }
        }
    }

    public function totalDebit(): int { return array_sum(array_map(static fn(JournalLine $l) => $l->debit, $this->lines)); }
    public function totalCredit(): int { return array_sum(array_map(static fn(JournalLine $l) => $l->credit, $this->lines)); }
    public function amount(): int { return $this->totalDebit(); }

    public function reverse(string $description): self
    {
        return new self(
            $this->companyId,$this->date,$description,
            array_map(static fn(JournalLine $l) => $l->reverse(), $this->lines),
            'reversal',$this->reversalOf,$this->intercompanyCompanyId,
            $this->metadata + ['reversed_source'=>$this->source],$this->branchId,$this->departmentId,$this->batchId
        );
    }
}
