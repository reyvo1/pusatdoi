<?php
declare(strict_types=1);
namespace Nexa\Consolidation;
use Nexa\Core\DomainException;
final class ConsolidationRun
{
    public array $entities=[];public array $eliminations=[];public array $validation=[];
    public function __construct(public readonly string $period,public readonly string $runNo,public string $status='draft'){if(!preg_match('/^\\d{4}-\\d{2}$/',$period)||trim($runNo)==='')throw new DomainException('Consolidation run tidak valid.');}
    public function addEntity(int $companyId,array $statement):void{if($this->status!=='draft')throw new DomainException('Run tidak dapat diubah.');$this->entities[$companyId]=$statement;}
    public function addElimination(array $entry):void{if($this->status!=='draft')throw new DomainException('Run tidak dapat diubah.');$this->eliminations[]=$entry;}
    public function validate():void{$this->validation=['entity_count'=>count($this->entities),'elimination_count'=>count($this->eliminations),'balanced'=>true];if(!$this->entities)throw new DomainException('Tidak ada entitas untuk dikonsolidasi.');$this->status='validated';}
    public function post():void{if($this->status!=='validated')throw new DomainException('Consolidation run harus validated.');$this->status='posted';}
    public function lock():void{if($this->status!=='posted')throw new DomainException('Consolidation run harus posted.');$this->status='locked';}
}
