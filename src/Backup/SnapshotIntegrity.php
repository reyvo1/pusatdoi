<?php
declare(strict_types=1);
namespace Nexa\Backup;

use Nexa\Core\DomainException;

final class SnapshotIntegrity
{
    public function wrap(array $payload): array
    {
        $canonical=$this->canonicalJson($payload);return ['algorithm'=>'sha256','checksum'=>hash('sha256',$canonical),'payload'=>$payload];
    }

    public function verify(array $wrapped): array
    {
        if(($wrapped['algorithm']??'')!=='sha256'||!isset($wrapped['checksum'],$wrapped['payload'])||!is_array($wrapped['payload']))throw new DomainException('Format backup tidak valid.');
        $actual=hash('sha256',$this->canonicalJson($wrapped['payload']));if(!hash_equals((string)$wrapped['checksum'],$actual))throw new DomainException('Checksum backup tidak cocok.');return $wrapped['payload'];
    }

    private function canonicalJson(array $value): string
    {
        $normalize=function($v)use(&$normalize){if(!is_array($v))return $v;if(array_is_list($v))return array_map($normalize,$v);ksort($v);foreach($v as $k=>$x)$v[$k]=$normalize($x);return $v;};
        return json_encode($normalize($value),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRESERVE_ZERO_FRACTION|JSON_THROW_ON_ERROR);
    }
}
