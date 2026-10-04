<?php
declare(strict_types=1);
namespace Nexa\ARAP;

final class AgingService
{
    /** @param list<Invoice> $invoices */
    public function bucket(array $invoices, string $asOf): array
    {
        $asOfDate = new \DateTimeImmutable($asOf);
        $buckets = ['current'=>0,'1_30'=>0,'31_60'=>0,'61_90'=>0,'over_90'=>0,'total'=>0];
        foreach ($invoices as $invoice) {
            $out = $invoice->outstanding();
            if ($out <= 0) continue;
            $due = new \DateTimeImmutable($invoice->dueDate);
            $days = $due >= $asOfDate ? 0 : (int)$due->diff($asOfDate)->format('%a');
            $key = $days === 0 ? 'current' : ($days <= 30 ? '1_30' : ($days <= 60 ? '31_60' : ($days <= 90 ? '61_90' : 'over_90')));
            $buckets[$key] += $out; $buckets['total'] += $out;
        }
        return $buckets;
    }
}
