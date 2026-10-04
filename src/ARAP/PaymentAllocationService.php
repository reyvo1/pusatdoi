<?php
declare(strict_types=1);
namespace Nexa\ARAP;

use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\Core\DomainException;

final class PaymentAllocationService
{
    public function allocate(Invoice $invoice, int $amount, int $cashAccountId, int $receivableAccountId, int $payableAccountId, string $date, string $reference): JournalEntry
    {
        if ($amount <= 0) throw new DomainException('Nominal pembayaran harus positif.');
        if ($amount > $invoice->outstanding()) throw new DomainException('Nominal pembayaran melebihi outstanding invoice.');
        $invoice->allocate($amount);
        if ($invoice->type === 'receivable') {
            $lines = [new JournalLine($cashAccountId, $amount, 0, 'Penerimaan pembayaran'), new JournalLine($receivableAccountId, 0, $amount, 'Pelunasan piutang')];
        } else {
            $lines = [new JournalLine($payableAccountId, $amount, 0, 'Pelunasan hutang'), new JournalLine($cashAccountId, 0, $amount, 'Pembayaran kas/bank')];
        }
        return new JournalEntry($invoice->companyId, $date, "Payment {$invoice->number} {$reference}", $lines, 'invoice_payment', null, null, [
            'invoice_id'=>$invoice->id,'invoice_number'=>$invoice->number,'reference'=>$reference
        ]);
    }
}
