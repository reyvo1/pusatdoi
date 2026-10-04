<?php
declare(strict_types=1);
namespace Nexa\ARAP;
use Nexa\Core\DomainException;
final readonly class InvoiceLine
{
    public function __construct(public int $accountId,public string $description,public float $quantity,public int $unitPrice,public int $taxAmount=0)
    {if($accountId<=0||trim($description)===''||$quantity<=0||$unitPrice<0||$taxAmount<0)throw new DomainException('Invoice line tidak valid.');}
    public function subtotal():int{return(int)round($this->quantity*$this->unitPrice);}
    public function total():int{return$this->subtotal()+$this->taxAmount;}
}
