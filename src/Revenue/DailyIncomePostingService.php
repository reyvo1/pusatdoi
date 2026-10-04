<?php
declare(strict_types=1);
namespace Nexa\Revenue;

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\Core\DomainException;
use Nexa\Core\Validation;

final class DailyIncomePostingService
{
    /**
     * @param array<int,array{id:int,company_id:int,name:string,revenue_account_id:int,active?:int|bool}> $categories
     * @param array<int,Account> $accounts
     * @param list<array{category_id:int,amount:int|float}> $incomeLines
     * @param list<array{account_id:int,amount:int|float,label?:string}> $paymentLines
     */
    public function build(
        int $companyId,
        string $date,
        string $description,
        array $categories,
        array $accounts,
        array $incomeLines,
        array $paymentLines
    ): JournalEntry {
        if ($companyId <= 0) throw new DomainException('Badan usaha wajib dipilih.');
        Validation::date($date);
        $description = Validation::requiredString($description, 'Keterangan', 3, 255);
        $categoryMap = [];
        foreach ($categories as $category) {
            if ((int)$category['company_id'] === $companyId && !empty($category['active'])) {
                $categoryMap[(int)$category['id']] = $category;
            }
        }
        $lines = []; $incomeTotal = 0; $paymentTotal = 0;
        foreach ($incomeLines as $row) {
            $amount = (int)round((float)($row['amount'] ?? 0));
            if ($amount <= 0) continue;
            $category = $categoryMap[(int)($row['category_id'] ?? 0)] ?? null;
            if (!$category) throw new DomainException('Kategori pendapatan tidak tersedia untuk badan usaha ini.');
            $accountId = (int)$category['revenue_account_id'];
            $account = $accounts[$accountId] ?? null;
            if (!$account || $account->type !== AccountType::Revenue || !$account->active || !$account->belongsTo($companyId)) {
                throw new DomainException('Kategori pendapatan harus dipetakan ke akun revenue aktif yang sesuai badan usaha.');
            }
            $lines[] = new JournalLine($accountId, 0, $amount, (string)$category['name']);
            $incomeTotal += $amount;
        }
        foreach ($paymentLines as $row) {
            $amount = (int)round((float)($row['amount'] ?? 0));
            if ($amount <= 0) continue;
            $accountId = (int)($row['account_id'] ?? 0);
            $account = $accounts[$accountId] ?? null;
            if (!$account || !$account->isCash || !$account->active || !$account->belongsTo($companyId)) {
                throw new DomainException('Pembayaran harus dipetakan ke akun kas/bank aktif yang sesuai badan usaha.');
            }
            $label = trim((string)($row['label'] ?? 'Kas / Bank')) ?: 'Kas / Bank';
            $lines[] = new JournalLine($accountId, $amount, 0, $label);
            $paymentTotal += $amount;
        }
        if ($incomeTotal <= 0 || $paymentTotal <= 0) throw new DomainException('Minimal satu pendapatan dan satu pembayaran wajib bernilai positif.');
        if ($incomeTotal !== $paymentTotal) throw new DomainException('Total pendapatan harus sama dengan total pembayaran.');
        return new JournalEntry($companyId, $date, $description, $lines, 'daily_income', null, null, [
            'income_total'=>$incomeTotal,'payment_total'=>$paymentTotal
        ]);
    }
}
