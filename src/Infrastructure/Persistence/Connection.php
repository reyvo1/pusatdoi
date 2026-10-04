<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
final class Connection
{
    public function __construct(public readonly \PDO $pdo){}
    public function transaction(callable $callback):mixed{$this->pdo->beginTransaction();try{$result=$callback($this->pdo);$this->pdo->commit();return$result;}catch(\Throwable$e){if($this->pdo->inTransaction())$this->pdo->rollBack();throw$e;}}
}
