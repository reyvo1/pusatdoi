<?php
declare(strict_types=1);
namespace Nexa\Legacy;

use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
use Nexa\ARAP\Invoice;
use Nexa\Banking\BankStatementLine;

final class StateAdapter
{
    /** @return array<int,Account> */
    public function accounts(array $state): array
    {
        $out=[];foreach(($state['accounts']??[]) as $row){$id=(int)$row['id'];$out[$id]=new Account($id,(string)$row['code'],(string)$row['name'],AccountType::from((string)$row['type']),isset($row['company_id'])?(int)$row['company_id']:null,!empty($row['cash']),!isset($row['active'])||!empty($row['active']));}return $out;
    }

    public function journalEntry(array $row): JournalEntry
    {
        $lines=[];foreach(($row['lines']??[]) as $line)$lines[]=new JournalLine((int)$line['account_id'],(int)round((float)($line['debit']??0)),(int)round((float)($line['credit']??0)),(string)($line['description']??''),isset($line['intercompany_company_id'])?(int)$line['intercompany_company_id']:null);
        return new JournalEntry((int)$row['company_id'],(string)$row['date'],(string)$row['description'],$lines,(string)($row['source']??'manual'),isset($row['reversal_of'])?(int)$row['reversal_of']:null,isset($row['intercompany_company_id'])?(int)$row['intercompany_company_id']:null,['legacy_id'=>$row['id']??null]);
    }

    /** @return list<Invoice> */
    public function invoices(array $state): array
    {
        $out=[];foreach(($state['invoices']??[]) as $r)$out[]=new Invoice((int)$r['id'],(int)$r['company_id'],(string)$r['type'],(string)$r['number'],(string)$r['party'],(string)$r['issue_date'],(string)$r['due_date'],(int)round((float)$r['amount']),(int)round((float)$r['paid']));return $out;
    }

    /** @return list<BankStatementLine> */
    public function bankLines(array $state): array
    {
        $out=[];foreach(($state['bank_feed']??[]) as $r)$out[]=new BankStatementLine((int)$r['id'],(int)$r['company_id'],(string)$r['date'],(string)$r['description'],(int)round((float)$r['amount']),(string)$r['direction'],isset($r['external_ref'])?(string)$r['external_ref']:null);return $out;
    }
}
