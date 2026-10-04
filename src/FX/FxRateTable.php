<?php
declare(strict_types=1);
namespace Nexa\FX;
use Nexa\Core\DomainException;
final class FxRateTable
{
    /** @var array<string,float> */ private array $rates=[];
    public function set(string $date,string $base,string $quote,float $rate):void{if($rate<=0)throw new DomainException('FX rate harus positif.');$this->rates[$this->key($date,$base,$quote)]=$rate;}
    public function get(string $date,string $base,string $quote):float{$k=$this->key($date,$base,$quote);if(!isset($this->rates[$k]))throw new DomainException('FX rate belum tersedia.');return$this->rates[$k];}
    private function key(string $date,string $base,string $quote):string{return$date.'|'.strtoupper($base).'|'.strtoupper($quote);}
}
