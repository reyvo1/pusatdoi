<?php
declare(strict_types=1);
namespace Nexa\Approval;
use Nexa\Core\DomainException;
use Nexa\Security\AuthorizationService;
use Nexa\Security\Permission;
use Nexa\Security\Role;
final class ApprovalWorkflowService
{
    public function __construct(private readonly AuthorizationService $auth=new AuthorizationService()){}
    public function decide(ApprovalRequest $request,int $userId,Role|string $role,string $decision,string $at): ApprovalRequest
    {
        $this->auth->assert($role,Permission::ApprovalDecide);if($userId===$request->requestedBy)throw new DomainException('Requester tidak boleh menyetujui permintaannya sendiri.');$request->decide($userId,$decision,$at);return$request;
    }
}
