<?php
declare(strict_types=1);
namespace Nexa\Accounting;
use Nexa\Core\DomainException;
use Nexa\Security\Role;
final class PostingPipeline
{
    public function __construct(private readonly ApprovalPolicy $approvalPolicy=new ApprovalPolicy()){}
    /** @param array<int,Account> $accounts */
    public function inspect(JournalEntry $entry,array $accounts,FiscalPeriodPolicy $periods,Role|string $role,bool $internalApprovalOverride=false): array
    {
        $periods->assertPostingAllowed($entry->date);JournalValidator::assertAccountsBelongToCompany($accounts,$entry);
        if($entry->intercompanyCompanyId!==null&&$entry->intercompanyCompanyId===$entry->companyId)throw new DomainException('Counterparty intercompany tidak boleh sama dengan badan usaha asal.');
        $needs=$this->approvalPolicy->requiresApproval($entry->amount(),$role)&&!$internalApprovalOverride;
        return['posting_allowed'=>!$needs,'approval_required'=>$needs,'amount'=>$entry->amount(),'source'=>$entry->source,'line_count'=>count($entry->lines)];
    }
}
