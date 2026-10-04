<?php
declare(strict_types=1);
namespace Nexa\Integration;

use Nexa\Core\DomainException;

final class InboundStagingService
{
    public function __construct(private readonly IdempotencyService $idempotency = new IdempotencyService()) {}

    public function stage(string $source,string $externalRef,int $companyId,int $amount,array $payload,array $existingKeys=[]): array
    {
        if($companyId<=0||$amount<0)throw new DomainException('Company/amount event integrasi tidak valid.');
        $key=$this->idempotency->key($source,$externalRef);$this->idempotency->assertNew($key,$existingKeys);
        return ['source'=>$source,'external_ref'=>$externalRef,'company_id'=>$companyId,'amount'=>$amount,'payload'=>$payload,'idempotency_key'=>$key,'status'=>'received','received_at'=>date(DATE_ATOM)];
    }

    public function validate(array $event, callable $mappingValidator): array
    {
        try{$mappingValidator($event);$event['status']='validated';$event['validated_at']=date(DATE_ATOM);return $event;}catch(\Throwable $e){$event['status']='failed';$event['error']=$e->getMessage();return $event;}
    }
}
