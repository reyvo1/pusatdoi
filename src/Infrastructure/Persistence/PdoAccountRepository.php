<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
use Nexa\Accounting\Account;
use Nexa\Accounting\AccountType;
final class PdoAccountRepository
{
    public function __construct(private readonly \PDO $pdo){}
    /** @return array<int,Account> */
    public function all(?int$companyId=null):array{$sql="SELECT id,company_id,code,name,account_type,is_cash_bank,is_active FROM chart_accounts WHERE is_active=1";$args=[];if($companyId){$sql.=" AND (company_id IS NULL OR company_id=?)";$args[]=$companyId;}$sql.=" ORDER BY code";$st=$this->pdo->prepare($sql);$st->execute($args);$out=[];foreach($st->fetchAll()as$r)$out[(int)$r['id']]=new Account((int)$r['id'],(string)$r['code'],(string)$r['name'],AccountType::from((string)$r['account_type']),$r['company_id']!==null?(int)$r['company_id']:null,(bool)$r['is_cash_bank'],(bool)$r['is_active']);return$out;}
}
