<?php
declare(strict_types=1);
namespace Nexa\ARAP;
use Nexa\Core\DomainException;
final class InvoiceService
{
    /** @param list<InvoiceLine> $lines */
    public function total(array $lines):array{if(!$lines)throw new DomainException('Invoice minimal memiliki satu baris.');$subtotal=$tax=$gross=0;foreach($lines as $l){$subtotal+=$l->subtotal();$tax+=$l->taxAmount;$gross+=$l->total();}return compact('subtotal','tax','gross');}
    /** @param list<Invoice> $invoices */
    public function outstandingByParty(array $invoices):array{$rows=[];foreach($invoices as $i){if($i->outstanding()<=0)continue;$k=$i->party;$rows[$k]=($rows[$k]??0)+$i->outstanding();}arsort($rows);return$rows;}
}
