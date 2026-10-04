<?php
declare(strict_types=1);
namespace Nexa\Banking;
use Nexa\Core\DomainException;
final class ReconciliationSession
{
    /** @var array<int,array{journal_id:int,amount:int,type:string,confidence:?float}> */ private array $matches=[];
    public function __construct(public readonly int $companyId,public readonly string $period,public readonly int $openingBalance,public readonly int $statementClosingBalance,public string $status='open')
    {if($companyId<=0||!preg_match('/^\\d{4}-\\d{2}$/',$period))throw new DomainException('Reconciliation session tidak valid.');}
    public function match(int $bankLineId,int $journalId,int $amount,string $type='manual',?float $confidence=null):void{if($this->status!=='open')throw new DomainException('Reconciliation session sudah ditutup.');if(isset($this->matches[$bankLineId]))throw new DomainException('Bank line sudah direkonsiliasi.');if($bankLineId<=0||$journalId<=0||$amount<=0)throw new DomainException('Match rekonsiliasi tidak valid.');$this->matches[$bankLineId]=['journal_id'=>$journalId,'amount'=>$amount,'type'=>$type,'confidence'=>$confidence];}
    public function matches():array{return$this->matches;}
    public function close(int $ledgerClosingBalance,int $unmatchedCount):void{if($unmatchedCount>0)throw new DomainException('Masih ada mutasi bank belum matched.');if($ledgerClosingBalance!==$this->statementClosingBalance)throw new DomainException('Saldo buku dan rekening koran belum sama.');$this->status='closed';}
}
