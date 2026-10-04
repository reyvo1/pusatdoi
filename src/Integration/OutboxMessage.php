<?php
declare(strict_types=1);
namespace Nexa\Integration;
use Nexa\Core\DomainException;
final class OutboxMessage
{
    public int $attempts=0;public string $status='pending';public ?string $lastError=null;
    public function __construct(public readonly string $eventType,public readonly string $aggregateType,public readonly string $aggregateId,public readonly array $payload)
    {if(trim($eventType)===''||trim($aggregateType)===''||trim($aggregateId)==='')throw new DomainException('Outbox message tidak valid.');}
    public function sent():void{$this->status='sent';$this->lastError=null;}
    public function failed(string $error):void{$this->attempts++;$this->status='failed';$this->lastError=$error;}
    public function retry():void{if($this->status!=='failed')throw new DomainException('Hanya message failed yang dapat diretry.');$this->status='pending';}
}
