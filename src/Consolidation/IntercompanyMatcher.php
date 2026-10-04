<?php
declare(strict_types=1);
namespace Nexa\Consolidation;

final class IntercompanyMatcher
{
    /** @param list<IntercompanyPair> $pairs */
    public function match(array $pairs): array
    {
        $result=[]; $used=[];
        foreach ($pairs as $i=>$a) {
            if (isset($used[$i])) continue;
            $match=null;
            foreach ($pairs as $j=>$b) {
                if ($i===$j || isset($used[$j])) continue;
                if ($a->fromCompanyId===$b->toCompanyId && $a->toCompanyId===$b->fromCompanyId && $a->amount===$b->amount && $a->period===$b->period) {
                    $match=$j; break;
                }
            }
            if ($match!==null) { $used[$i]=$used[$match]=true; $result[]=['left'=>$i,'right'=>$match,'status'=>'matched','amount'=>$a->amount]; }
            else { $used[$i]=true; $result[]=['left'=>$i,'right'=>null,'status'=>'unmatched','amount'=>$a->amount]; }
        }
        return $result;
    }
}
