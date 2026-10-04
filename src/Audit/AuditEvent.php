<?php
declare(strict_types=1);
namespace Nexa\Audit;

final readonly class AuditEvent
{
    public function __construct(
        public string $action,
        public ?int $userId,
        public ?int $companyId,
        public array $payload,
        public string $occurredAt,
        public ?string $requestId = null,
        public ?string $ipHash = null,
    ) {}

    public function toArray(): array
    {
        return ['action'=>$this->action,'user_id'=>$this->userId,'company_id'=>$this->companyId,'payload'=>$this->payload,'occurred_at'=>$this->occurredAt,'request_id'=>$this->requestId,'ip_hash'=>$this->ipHash];
    }
}
