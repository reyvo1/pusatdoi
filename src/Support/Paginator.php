<?php
declare(strict_types=1);
namespace Nexa\Support;
final class Paginator
{
    public function slice(array $rows,int $page=1,int $perPage=50):array{$page=max(1,$page);$perPage=max(1,min(500,$perPage));$total=count($rows);$pages=max(1,(int)ceil($total/$perPage));$page=min($page,$pages);return['items'=>array_slice($rows,($page-1)*$perPage,$perPage),'meta'=>['page'=>$page,'per_page'=>$perPage,'total'=>$total,'pages'=>$pages]];}
}
