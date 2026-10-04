<?php
declare(strict_types=1);
namespace Nexa\Audit;

final class TransactionalAuditService
{
    public function __construct(private readonly \PDO $pdo){}
    public function record(string $action,string $entityType,?string $entityId,array $payload=[],?int $userId=null,?int $companyId=null,?string $requestId=null,?string $ip=null):void
    {
        $st=$this->pdo->prepare("INSERT INTO audit_logs(user_id,company_id,action,entity_type,entity_id,payload_json,request_id,ip_address) VALUES(?,?,?,?,?,?,?,?)");
        $st->execute([$userId,$companyId,$action,$entityType,$entityId,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$requestId,$ip]);
        if($st->rowCount()!==1)throw new \RuntimeException('Audit event gagal disimpan.');
    }
}
