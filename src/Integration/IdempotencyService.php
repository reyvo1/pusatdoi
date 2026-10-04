<?php
declare(strict_types=1);
namespace Nexa\Integration;

use Nexa\Core\DomainException;

final class IdempotencyService
{
    public function key(string $source, string $externalRef): string
    {
        $source=trim($source);$externalRef=trim($externalRef);
        if($source===''||$externalRef==='')throw new DomainException('Source dan external reference wajib diisi.');
        return hash('sha256',strtolower($source).'|'.$externalRef);
    }

    /** @param list<string> $existingKeys */
    public function assertNew(string $key,array $existingKeys): void
    {
        if(in_array($key,$existingKeys,true))throw new DomainException('Event integrasi duplikat; idempotency key sudah diproses.');
    }
}
