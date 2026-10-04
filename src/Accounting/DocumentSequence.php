<?php
declare(strict_types=1);
namespace Nexa\Accounting;
use Nexa\Core\DomainException;
final class DocumentSequence
{
    public function format(string $prefix,string $date,int $number,int $width=6): string
    {
        if(!preg_match('/^[A-Z0-9_-]{2,12}$/',$prefix)||$number<=0||$width<3||$width>12)throw new DomainException('Parameter nomor dokumen tidak valid.');
        $d=new \DateTimeImmutable($date);return $prefix.'-'.$d->format('Ym').'-'.str_pad((string)$number,$width,'0',STR_PAD_LEFT);
    }
}
