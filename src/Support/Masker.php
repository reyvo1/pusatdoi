<?php
declare(strict_types=1);
namespace Nexa\Support;
final class Masker
{
    public function accountNumber(?string $number,int $visible=4):string{$number=trim((string)$number);if($number==='')return'-';$len=strlen($number);if($len<=$visible)return str_repeat('•',max(0,$len-2)).substr($number,-2);return str_repeat('•',max(4,$len-$visible)).substr($number,-$visible);}
    public function email(string $email):string{[$local,$domain]=array_pad(explode('@',$email,2),2,'');if($domain==='')return'***';$show=min(2,strlen($local));return substr($local,0,$show).str_repeat('*',max(2,strlen($local)-$show)).'@'.$domain;}
}
