<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;

final class PdoDocumentSequence
{
    public function __construct(private readonly \PDO $pdo){}
    public function next(int $companyId,string $type,string $date,string $prefix): string
    {
        $period=substr($date,0,7);
        $st=$this->pdo->prepare("INSERT INTO document_sequences(company_id,document_type,period_key,last_number) VALUES(?,?,?,1) ON DUPLICATE KEY UPDATE last_number=LAST_INSERT_ID(last_number+1)");
        $st->execute([$companyId,$type,$period]);
        $n=(int)$this->pdo->lastInsertId();
        if($n===0){$q=$this->pdo->prepare("SELECT last_number FROM document_sequences WHERE company_id=? AND document_type=? AND period_key=?");$q->execute([$companyId,$type,$period]);$n=(int)$q->fetchColumn();}
        return sprintf('%s-%s-%06d',$prefix,str_replace('-','',$period),$n);
    }
}
