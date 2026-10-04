<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
use Nexa\Accounting\JournalEntry;

final class PdoJournalRepository
{
    public function __construct(private readonly \PDO $pdo){}
    public function insert(JournalEntry $entry,?int $userId=null,?int $branchId=null,?int $departmentId=null): array
    {
        $branchId=$entry->branchId??$branchId;$departmentId=$entry->departmentId??$departmentId;
        $ownTx=!$this->pdo->inTransaction(); if($ownTx)$this->pdo->beginTransaction();
        try{
            $seq=new PdoDocumentSequence($this->pdo);
            $prefix=$entry->source==='daily_income'?'INC':($entry->source==='reversal'?'REV':'JRN');
            $no=$seq->next($entry->companyId,'journal',$entry->date,$prefix);
            $st=$this->pdo->prepare("INSERT INTO journal_entries(company_id,branch_id,department_id,batch_id,journal_no,journal_date,description,source_type,source_ref,status,posted_at,created_by,reversal_of,external_ref) VALUES(?,?,?,?,?,?,?,?,?,'posted',NOW(),?,?,?)");
            $st->execute([$entry->companyId,$branchId,$departmentId,$entry->batchId,$no,$entry->date,$entry->description,$entry->source,$entry->metadata['source_ref']??null,$userId,$entry->reversalOf,$entry->metadata['external_ref']??null]);
            $id=(int)$this->pdo->lastInsertId();
            $line=$this->pdo->prepare("INSERT INTO journal_lines(journal_id,account_id,branch_id,department_id,description,debit,credit,cost_center,profit_center,project_code,transaction_currency,foreign_amount,exchange_rate,base_amount,intercompany_company_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            foreach($entry->lines as $l){
                $line->execute([$id,$l->accountId,$l->branchId??$branchId,$l->departmentId??$departmentId,$l->description,$l->debit,$l->credit,$l->costCenter,$l->profitCenter,$l->projectCode,$l->transactionCurrency,$l->foreignAmountMinor,$l->exchangeRate,$l->baseAmount??$l->amount(),$l->intercompanyCompanyId]);
            }
            if($ownTx)$this->pdo->commit();
            return ['id'=>$id,'journal_no'=>$no,'status'=>'posted'];
        }catch(\Throwable $e){if($ownTx&&$this->pdo->inTransaction())$this->pdo->rollBack();throw $e;}
    }
    public function markReversed(int $originalId,int $reversalId):void{$st=$this->pdo->prepare("UPDATE journal_entries SET reversed_by=? WHERE id=? AND reversed_by IS NULL");$st->execute([$reversalId,$originalId]);if($st->rowCount()!==1)throw new \RuntimeException('Jurnal sudah direversal atau tidak ditemukan.');}
}
