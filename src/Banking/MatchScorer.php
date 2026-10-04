<?php
declare(strict_types=1);
namespace Nexa\Banking;
final class MatchScorer
{
    public function score(BankStatementLine $bank,string $journalDate,string $journalDescription,int $cashDelta):float
    {
        $expected=$bank->direction==='in'?$bank->amount:-$bank->amount;if($cashDelta!==$expected)return 0.0;$score=70.0;$days=abs((new \DateTimeImmutable($bank->date))->diff(new \DateTimeImmutable($journalDate))->days);$score-=min(30,$days*8);$bankText=strtolower(trim($bank->description));$journalText=strtolower(trim($journalDescription));if($bankText===$journalText)$score+=20;$a=$this->tokens($bank->description);$b=$this->tokens($journalDescription);if($a&&$b){$intersection=count(array_intersect($a,$b));$score+=min(20,$intersection*5);}return max(0,min(100,$score));
    }
    private function tokens(string $text):array{$t=preg_split('/[^a-z0-9]+/i',strtolower($text),-1,PREG_SPLIT_NO_EMPTY)?:[];return array_values(array_unique(array_filter($t,static fn($x)=>strlen($x)>=3)));}
}
