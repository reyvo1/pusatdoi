<?php
declare(strict_types=1);
namespace Nexa\Assets;
use Nexa\Core\DomainException;
final class AssetRegisterService
{
    /** @param list<Asset> $assets */
    public function summary(array $assets,string $asOf):array{$dep=new DepreciationService();$cost=$acc=$book=0;$byCompany=[];foreach($assets as$a){$cost+=$a->cost;$d=$dep->accumulated($a,$asOf);$acc+=$d;$b=$dep->bookValue($a,$asOf);$book+=$b;$byCompany[$a->companyId]=($byCompany[$a->companyId]??0)+$b;}return['cost'=>$cost,'accumulated_depreciation'=>$acc,'book_value'=>$book,'by_company'=>$byCompany];}
    public function disposalGainLoss(Asset $asset,string $date,int $proceeds):int{if($proceeds<0)throw new DomainException('Proceeds tidak boleh negatif.');$book=(new DepreciationService())->bookValue($asset,$date);return$proceeds-$book;}
}
