<?php
declare(strict_types=1);
namespace Nexa\Security;
final class LoginThrottle
{
    /** @param list<int> $failedEpochs */
    public function isBlocked(array $failedEpochs,int $now,int $windowSeconds=900,int $maxFailures=5):bool{$recent=array_filter($failedEpochs,static fn(int$t)=>$t>=$now-$windowSeconds);return count($recent)>=$maxFailures;}
    /** @param list<int> $failedEpochs */
    public function retryAfter(array $failedEpochs,int $now,int $windowSeconds=900,int $maxFailures=5):int{sort($failedEpochs);$recent=array_values(array_filter($failedEpochs,static fn(int$t)=>$t>=$now-$windowSeconds));if(count($recent)<$maxFailures)return 0;return max(1,$recent[0]+$windowSeconds-$now);}
}
