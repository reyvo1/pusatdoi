<?php
declare(strict_types=1);
namespace Nexa\Approval;
use Nexa\Core\DomainException;
final class ApprovalRequest
{
    /** @var list<array{user_id:int,decision:string,at:string}> */ public array $decisions=[];
    public function __construct(public readonly int $id,public readonly int $companyId,public readonly string $documentType,public readonly int $amount,public readonly int $requestedBy,public readonly int $requiredApprovals=1,public string $status='pending')
    {if($id<=0||$companyId<=0||$amount<0||$requestedBy<=0||$requiredApprovals<=0)throw new DomainException('Approval request tidak valid.');}
    public function decide(int $userId,string $decision,string $at):void{if($this->status!=='pending')throw new DomainException('Approval sudah selesai.');if(!in_array($decision,['approved','rejected'],true))throw new DomainException('Decision tidak valid.');foreach($this->decisions as $d)if($d['user_id']===$userId)throw new DomainException('User sudah memberikan keputusan.');$this->decisions[]=['user_id'=>$userId,'decision'=>$decision,'at'=>$at];if($decision==='rejected'){$this->status='rejected';return;}$approved=count(array_filter($this->decisions,static fn($d)=>$d['decision']==='approved'));if($approved>=$this->requiredApprovals)$this->status='approved';}
}
