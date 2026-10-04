<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
final class PdoIntegrationRepository
{
    public function __construct(private readonly \PDO$pdo){}
    public function insert(array$event,string$eventType):int{$st=$this->pdo->prepare("INSERT INTO integration_events(source,external_ref,idempotency_key,company_id,event_type,occurred_at,status,payload_json) VALUES(?,?,?,?,?,?,?,?)");$st->execute([$event['source'],$event['external_ref'],$event['idempotency_key'],$event['company_id'],$eventType,$event['payload']['occurred_at']??null,$event['status'],json_encode($event['payload'],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)]);return(int)$this->pdo->lastInsertId();}
    public function existingKeys(string$source,int$limit=10000):array{$st=$this->pdo->prepare("SELECT idempotency_key FROM integration_events WHERE source=? ORDER BY id DESC LIMIT {$limit}");$st->execute([$source]);return array_column($st->fetchAll(),'idempotency_key');}
}
