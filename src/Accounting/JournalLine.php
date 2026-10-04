<?php
declare(strict_types=1);
namespace Nexa\Accounting;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final readonly class JournalLine
{
    public function __construct(
        public int $accountId,
        public int $debit,
        public int $credit,
        public string $description = '',
        public ?int $intercompanyCompanyId = null,
        public ?int $branchId = null,
        public ?int $departmentId = null,
        public ?string $costCenter = null,
        public ?string $profitCenter = null,
        public ?string $projectCode = null,
        public string $transactionCurrency = 'IDR',
        public ?int $foreignAmountMinor = null,
        public string $exchangeRate = '1',
        public ?int $baseAmount = null,
    ) {
        if ($accountId <= 0) throw new DomainException('Account ID baris jurnal tidak valid.');
        if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
            throw new DomainException('Baris jurnal harus hanya memiliki debit atau kredit positif.');
        }
        Validation::currency($transactionCurrency);
        if (!is_numeric($exchangeRate) || (float)$exchangeRate <= 0) throw new DomainException('Exchange rate harus positif.');
        if ($foreignAmountMinor !== null && $foreignAmountMinor < 0) throw new DomainException('Foreign amount tidak boleh negatif.');
        if ($baseAmount !== null && $baseAmount !== $this->amount()) throw new DomainException('Base amount harus sama dengan nilai ledger debit/kredit.');
    }

    public function amount(): int { return max($this->debit, $this->credit); }
    public function reverse(): self
    {
        return new self(
            $this->accountId,$this->credit,$this->debit,$this->description,$this->intercompanyCompanyId,
            $this->branchId,$this->departmentId,$this->costCenter,$this->profitCenter,$this->projectCode,
            $this->transactionCurrency,$this->foreignAmountMinor,$this->exchangeRate,$this->baseAmount
        );
    }

    public function dimensions(): array
    {
        return array_filter([
            'branch_id'=>$this->branchId,'department_id'=>$this->departmentId,'cost_center'=>$this->costCenter,
            'profit_center'=>$this->profitCenter,'project_code'=>$this->projectCode
        ], static fn($v)=>$v!==null&&$v!=='');
    }
}
