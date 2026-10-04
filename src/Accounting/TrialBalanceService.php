<?php
declare(strict_types=1);
namespace Nexa\Accounting;

final class TrialBalanceService
{
    public function __construct(private readonly LedgerService $ledger = new LedgerService()) {}

    /** @param list<array<string,mixed>> $entries @param array<int,Account> $accounts */
    public function build(array $entries, array $accounts, ?int $companyId = null, ?string $period = null): array
    {
        $balances = $this->ledger->balances($entries, $accounts, $companyId, $period);
        $rows = [];
        foreach ($accounts as $id => $account) {
            $b = $balances[$id] ?? ['debit'=>0,'credit'=>0,'balance'=>0];
            if ($b['debit'] === 0 && $b['credit'] === 0) continue;
            $rows[] = [
                'account_id'=>$id,'code'=>$account->code,'name'=>$account->name,'type'=>$account->type->value,
                'debit'=>$b['debit'],'credit'=>$b['credit'],'balance'=>$b['balance']
            ];
        }
        usort($rows, static fn(array $a, array $b) => strcmp($a['code'], $b['code']));
        return $rows;
    }

    public function totals(array $rows): array
    {
        $debit = array_sum(array_column($rows, 'debit'));
        $credit = array_sum(array_column($rows, 'credit'));
        return ['debit'=>$debit,'credit'=>$credit,'difference'=>$debit-$credit,'balanced'=>$debit===$credit];
    }
}
