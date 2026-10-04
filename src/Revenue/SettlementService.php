<?php
declare(strict_types=1);
namespace Nexa\Revenue;

use Nexa\Core\DomainException;

final class SettlementService
{
    /** @param list<array{method:string,gross:int,fee:int,net:int,reference?:string}> $lines */
    public function summarize(array $lines): array
    {
        $gross = $fee = $net = 0; $methods = [];
        foreach ($lines as $line) {
            $g=(int)($line['gross']??0); $f=(int)($line['fee']??0); $n=(int)($line['net']??0);
            if ($g < 0 || $f < 0 || $n < 0 || $g - $f !== $n) throw new DomainException('Settlement tidak valid: gross - fee harus sama dengan net.');
            $gross += $g; $fee += $f; $net += $n;
            $method = trim((string)($line['method']??'unknown')) ?: 'unknown';
            $methods[$method] = ($methods[$method] ?? 0) + $n;
        }
        return compact('gross','fee','net','methods');
    }
}
