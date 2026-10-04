<?php
declare(strict_types=1);
namespace Nexa\ARAP;

use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final class Invoice
{
    public function __construct(
        public readonly int $id,
        public readonly int $companyId,
        public readonly string $type,
        public readonly string $number,
        public readonly string $party,
        public readonly string $issueDate,
        public readonly string $dueDate,
        public readonly int $amount,
        public int $paid = 0,
        public string $status = 'open',
    ) {
        if ($id <= 0 || $companyId <= 0) throw new DomainException('Invoice ID/company tidak valid.');
        if (!in_array($type, ['receivable','payable'], true)) throw new DomainException('Tipe invoice harus receivable/payable.');
        if ($amount <= 0 || $paid < 0 || $paid > $amount) throw new DomainException('Nominal invoice tidak valid.');
        Validation::date($issueDate); Validation::date($dueDate);
        if ($dueDate < $issueDate) throw new DomainException('Jatuh tempo tidak boleh sebelum tanggal invoice.');
        $this->refreshStatus();
    }

    public function outstanding(): int { return $this->amount - $this->paid; }

    public function allocate(int $payment): void
    {
        if ($payment <= 0) throw new DomainException('Pembayaran harus lebih dari nol.');
        if ($payment > $this->outstanding()) throw new DomainException('Pembayaran melebihi saldo invoice.');
        $this->paid += $payment;
        $this->refreshStatus();
    }

    private function refreshStatus(): void
    {
        $this->status = $this->paid === 0 ? 'open' : ($this->paid >= $this->amount ? 'paid' : 'partial');
    }
}
