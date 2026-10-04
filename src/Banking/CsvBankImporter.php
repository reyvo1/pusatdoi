<?php
declare(strict_types=1);
namespace Nexa\Banking;

use Nexa\Core\DomainException;

final class CsvBankImporter
{
    /** @return list<array{date:string,description:string,amount:int,direction:string,external_ref:string}> */
    public function parse(string $csv): array
    {
        $fh = fopen('php://temp', 'r+');
        if (!$fh) throw new DomainException('Tidak dapat membuka parser CSV.');
        fwrite($fh, $csv); rewind($fh);
        $header = fgetcsv($fh);
        if (!$header) throw new DomainException('CSV kosong.');
        $map = array_flip(array_map(static fn($v) => strtolower(trim((string)$v)), $header));
        foreach (['date','description','amount','direction'] as $required) {
            if (!array_key_exists($required, $map)) throw new DomainException('CSV wajib memiliki kolom date, description, amount, direction.');
        }
        $rows=[];
        while (($line=fgetcsv($fh)) !== false) {
            $date=trim((string)($line[$map['date']]??''));
            $description=trim((string)($line[$map['description']]??''));
            $rawAmount=str_replace(['.',',',' '],['','',''],(string)($line[$map['amount']]??''));
            $amount=(int)$rawAmount;
            $direction=strtolower(trim((string)($line[$map['direction']]??'')));
            if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/',$date) || $description==='' || $amount<=0 || !in_array($direction,['in','out'],true)) continue;
            $rows[]=['date'=>$date,'description'=>$description,'amount'=>$amount,'direction'=>$direction,
                'external_ref'=>'CSV-'.substr(hash('sha256',"{$date}|{$description}|{$amount}|{$direction}"),0,24)];
        }
        fclose($fh);
        if (!$rows) throw new DomainException('Tidak ada baris bank valid yang dapat diimpor.');
        return $rows;
    }
}
