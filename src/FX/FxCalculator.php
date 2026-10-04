<?php
declare(strict_types=1);
namespace Nexa\FX;

use Nexa\Core\DomainException;

final class FxCalculator
{
    public function realized(int|float $foreignAmount, float $bookRate, float $settlementRate): array
    {
        if ($foreignAmount <= 0 || $bookRate <= 0 || $settlementRate <= 0) throw new DomainException('Foreign amount dan rate harus positif.');
        $book=(int)round($foreignAmount*$bookRate);
        $settlement=(int)round($foreignAmount*$settlementRate);
        return ['book_base'=>$book,'settlement_base'=>$settlement,'gain_loss'=>$settlement-$book];
    }

    public function convert(int|float $foreignAmount, float $rate): int
    {
        if ($rate <= 0) throw new DomainException('Rate harus positif.');
        return (int)round($foreignAmount*$rate);
    }
}
