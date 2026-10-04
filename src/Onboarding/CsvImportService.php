<?php
declare(strict_types=1);
namespace Nexa\Onboarding;

use Nexa\Core\DomainException;

final class CsvImportService
{
    /** @return array{headers:list<string>,rows:list<array<string,string>>} */
    public function parse(string $csv): array
    {
        if (trim($csv)==='') throw new DomainException('CSV kosong.');
        $h=fopen('php://temp','r+'); fwrite($h,$csv); rewind($h);
        $raw=fgetcsv($h); if(!$raw) throw new DomainException('Header CSV tidak ditemukan.');
        $headers=array_map(static fn($x)=>strtolower(trim((string)$x)), $raw);
        if(count(array_filter($headers))!==count($headers)) throw new DomainException('Header CSV tidak boleh kosong.');
        if(count(array_unique($headers))!==count($headers)) throw new DomainException('Header CSV duplikat.');
        $rows=[];
        while(($r=fgetcsv($h))!==false){
            if(count($r)===1 && trim((string)$r[0])==='') continue;
            $row=[]; foreach($headers as $i=>$k)$row[$k]=trim((string)($r[$i]??''));
            $rows[]=$row;
            if(count($rows)>5000) throw new DomainException('Import dibatasi maksimal 5.000 baris per file.');
        }
        fclose($h);
        if(!$rows) throw new DomainException('CSV tidak memiliki data.');
        return ['headers'=>$headers,'rows'=>$rows];
    }

    public function assertHeaders(array $headers,array $required): void
    {
        $missing=array_values(array_diff($required,$headers));
        if($missing) throw new DomainException('Kolom wajib belum ada: '.implode(', ',$missing).'.');
    }

    public function normalizeType(string $type): string
    {
        $type=strtolower(trim($type));
        if(!in_array($type,['coa','parties','opening_balance'],true)) throw new DomainException('Jenis import tidak didukung.');
        return $type;
    }
}
