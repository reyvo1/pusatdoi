<?php
declare(strict_types=1);
namespace Nexa\Accounting;
use Nexa\Core\DomainException;
final class JournalBatch
{
    /** @var list<JournalEntry> */ private array $entries=[];
    public function __construct(public readonly int $companyId,public readonly string $batchNo,public readonly string $date,public readonly string $source,public string $status='draft')
    {if($companyId<=0||trim($batchNo)===''||trim($source)==='')throw new DomainException('Journal batch tidak valid.');}
    public function add(JournalEntry $entry): void {if($this->status!=='draft')throw new DomainException('Hanya batch draft yang dapat diubah.');if($entry->companyId!==$this->companyId)throw new DomainException('Entry batch berasal dari badan usaha berbeda.');$this->entries[]=$entry;}
    public function entries(): array{return $this->entries;}
    public function totals():array{$d=$c=0;foreach($this->entries as $e){$d+=$e->totalDebit();$c+=$e->totalCredit();}return['debit'=>$d,'credit'=>$c,'balanced'=>$d===$c];}
    public function submit():void{if(!$this->entries)throw new DomainException('Batch kosong tidak dapat disubmit.');if(!$this->totals()['balanced'])throw new DomainException('Batch tidak balance.');$this->status='pending';}
    public function approve():void{if($this->status!=='pending')throw new DomainException('Batch bukan status pending.');$this->status='approved';}
    public function posted():void{if($this->status!=='approved')throw new DomainException('Batch harus approved sebelum posted.');$this->status='posted';}
}
