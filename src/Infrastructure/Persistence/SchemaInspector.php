<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
final class SchemaInspector
{
    public function __construct(private readonly \PDO$pdo){}
    public function tables():array{return array_column($this->pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema=DATABASE() ORDER BY table_name")->fetchAll(),'table_name');}
    public function assertRequired(array$required):array{$existing=array_flip($this->tables());$missing=array_values(array_filter($required,static fn($t)=>!isset($existing[$t])));return['ok'=>!$missing,'missing'=>$missing,'table_count'=>count($existing)];}
}
