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


    public function canonicalJson(array $payload): string
    {
        $normalize = function(mixed $value) use (&$normalize): mixed {
            if (!is_array($value)) return $value;
            $isList = array_keys($value) === range(0, count($value)-1);
            if ($isList) return array_map($normalize, $value);
            ksort($value, SORT_STRING);
            foreach ($value as $k => $v) $value[$k] = $normalize($v);
            return $value;
        };
        return json_encode($normalize($payload), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    }

    public function payloadFingerprint(array $payload): string
    {
        return hash('sha256', $this->canonicalJson($payload));
    }

    public function samePayload(array $left,array $right): bool
    {
        return hash_equals($this->payloadFingerprint($left), $this->payloadFingerprint($right));
    }

    /** @param list<string> $existingKeys */
    public function assertNew(string $key,array $existingKeys): void
    {
        if(in_array($key,$existingKeys,true))throw new DomainException('Event integrasi duplikat; idempotency key sudah diproses.');
    }
}
