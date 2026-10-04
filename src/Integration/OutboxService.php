<?php
declare(strict_types=1);
namespace Nexa\Integration;
final class OutboxService
{
    /** @param list<OutboxMessage> $messages */
    public function dispatch(array $messages,callable $sender,int $maxAttempts=5):array{$sent=$failed=$skipped=0;foreach($messages as$m){if($m->status!=='pending'||$m->attempts>=$maxAttempts){$skipped++;continue;}try{$sender($m);$m->sent();$sent++;}catch(\Throwable$e){$m->failed($e->getMessage());$failed++;}}return compact('sent','failed','skipped');}
}
