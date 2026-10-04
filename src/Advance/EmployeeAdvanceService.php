<?php
declare(strict_types=1);
namespace Nexa\Advance;

use Nexa\Core\DomainException;

final class EmployeeAdvanceService
{
    public function issue(array $input): array
    {
        $company=(int)($input['company_id']??0);$employee=trim((string)($input['employee_name']??''));$date=(string)($input['advance_date']??'');$amount=(int)round((float)($input['amount']??0));
        $advance=(int)($input['advance_account_id']??0);$cash=(int)($input['cash_account_id']??0);$due=(string)($input['due_date']??'');$desc=trim((string)($input['description']??'Uang muka karyawan'));
        if($company<=0||strlen($employee)<2||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)||$amount<=0||$advance<=0||$cash<=0) throw new DomainException('Data uang muka tidak valid.');
        if($due!==''&&(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$due)||$due<$date)) throw new DomainException('Jatuh tempo uang muka tidak valid.');
        return ['company_id'=>$company,'employee_name'=>$employee,'advance_date'=>$date,'due_date'=>$due?:null,'amount'=>$amount,'advance_account_id'=>$advance,'cash_account_id'=>$cash,'description'=>$desc?:'Uang muka karyawan'];
    }

    public function settlement(array $advance,array $input): array
    {
        $outstanding=(int)round((float)($advance['outstanding_amount']??$advance['amount']??0));$expense=(int)round((float)($input['expense_amount']??0));$refund=(int)round((float)($input['refund_amount']??0));$reimburse=(int)round((float)($input['reimbursement_amount']??0));$expenseAccount=(int)($input['expense_account_id']??0);$cash=(int)($input['cash_account_id']??0);$date=(string)($input['settlement_date']??'');
        if($outstanding<=0||$expense<0||$refund<0||$reimburse<0||$expenseAccount<=0||$cash<=0||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) throw new DomainException('Data pertanggungjawaban uang muka tidak valid.');
        if($expense+$refund!==$outstanding+$reimburse) throw new DomainException('Pertanggungjawaban tidak balance: expense + refund harus sama dengan advance + reimbursement.');
        if($expense===0&&$refund===0) throw new DomainException('Pertanggungjawaban tidak boleh kosong.');
        return ['settlement_date'=>$date,'expense_amount'=>$expense,'refund_amount'=>$refund,'reimbursement_amount'=>$reimburse,'expense_account_id'=>$expenseAccount,'cash_account_id'=>$cash,'description'=>trim((string)($input['description']??'Pertanggungjawaban uang muka'))?:'Pertanggungjawaban uang muka'];
    }
}
