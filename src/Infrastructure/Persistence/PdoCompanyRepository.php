<?php
declare(strict_types=1);
namespace Nexa\Infrastructure\Persistence;
use Nexa\Organization\Company;
final class PdoCompanyRepository
{
    public function __construct(private readonly \PDO $pdo){}
    /** @return list<Company> */
    public function active():array{$rows=$this->pdo->query("SELECT id,code,name,business_type,base_currency,is_active FROM companies WHERE is_active=1 ORDER BY code")->fetchAll();return array_map(static fn($r)=>new Company((int)$r['id'],(string)$r['code'],(string)$r['name'],(string)$r['business_type'],(string)$r['base_currency'],(bool)$r['is_active']),$rows);}
    public function find(int$id):?Company{$st=$this->pdo->prepare("SELECT id,code,name,business_type,base_currency,is_active FROM companies WHERE id=?");$st->execute([$id]);$r=$st->fetch();return$r?new Company((int)$r['id'],(string)$r['code'],(string)$r['name'],(string)$r['business_type'],(string)$r['base_currency'],(bool)$r['is_active']):null;}
}
