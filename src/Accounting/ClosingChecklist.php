<?php
declare(strict_types=1);
namespace Nexa\Accounting;
final class ClosingChecklist
{
    public function evaluate(array $input): array
    {
        $checks=[
            'trial_balance_balanced'=>!empty($input['trial_balance_balanced']),
            'pending_approvals'=>(int)($input['pending_approvals']??0)===0,
            'unmatched_bank'=>(int)($input['unmatched_bank']??0)===0,
            'unposted_depreciation'=>(int)($input['unposted_depreciation']??0)===0,
            'integration_failures'=>(int)($input['integration_failures']??0)===0,
            'intercompany_unmatched'=>(int)($input['intercompany_unmatched']??0)===0,
            'arap_cutoff_confirmed'=>!empty($input['arap_cutoff_confirmed']),
        ];
        return['checks'=>$checks,'ready'=>!in_array(false,$checks,true),'failed'=>array_keys(array_filter($checks,static fn(bool $v)=>!$v))];
    }
}
