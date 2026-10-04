<?php
declare(strict_types=1);
namespace Nexa\Security;

final class RbacService
{
    private array $matrix;
    public function __construct(?\PDO $pdo=null)
    {
        $this->matrix=(new AuthorizationService())->matrixForRuntime();
        if($pdo){
            try{
                $rows=$pdo->query("SELECT role_code,permission_code FROM role_permissions ORDER BY role_code,permission_code")->fetchAll();
                if($rows){$db=[];foreach($rows as $r)$db[(string)$r['role_code']][]=(string)$r['permission_code'];$this->matrix=array_replace($this->matrix,$db);}
            }catch(\Throwable){/* fallback to compiled safe baseline */}
        }
    }
    public function can(string $role,string $permission):bool{return in_array('*',$this->matrix[$role]??[],true)||in_array($permission,$this->matrix[$role]??[],true);}
    public function assert(string $role,string $permission):void{if(!$this->can($role,$permission))throw new \RuntimeException('Akses ditolak untuk permission '.$permission.'.');}
    public function allowedCompanyIds(string $role,?int $assignedCompanyId,array $allIds):array
    {
        if($role==='entity_admin')return $assignedCompanyId&&in_array($assignedCompanyId,$allIds,true)?[$assignedCompanyId]:[];
        return $allIds;
    }
    public function matrix():array{return $this->matrix;}
}
