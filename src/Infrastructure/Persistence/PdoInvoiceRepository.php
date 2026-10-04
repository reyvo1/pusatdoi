<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
use Nexa\ARAP\Invoice;
final class PdoInvoiceRepository
{
    public function __construct(private readonly \PDO$pdo){}
    public function findForUpdate(int$id):?Invoice{$st=$this->pdo->prepare("SELECT * FROM invoices WHERE id=? FOR UPDATE");$st->execute([$id]);$r=$st->fetch();return$r?new Invoice((int)$r['id'],(int)$r['company_id'],(string)$r['invoice_type'],(string)$r['invoice_no'],(string)$r['party_name'],(string)$r['issue_date'],(string)$r['due_date'],(int)round((float)$r['amount']),(int)round((float)$r['paid_amount'])):null;}
    public function persistPaymentState(Invoice$i):void{$st=$this->pdo->prepare("UPDATE invoices SET paid_amount=?,status=? WHERE id=?");$st->execute([$i->paid,$i->status,$i->id]);}
}
