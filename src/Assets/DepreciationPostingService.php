<?php
declare(strict_types=1);
namespace Nexa\Assets;
use Nexa\Accounting\JournalEntry;
use Nexa\Accounting\JournalLine;
final class DepreciationPostingService
{
    public function build(Asset $asset,string $date,int $expenseAccountId,int $accumulatedAccountId):JournalEntry{$amount=(new DepreciationService())->monthly($asset);return new JournalEntry($asset->companyId,$date,'Depresiasi '.$asset->name,[new JournalLine($expenseAccountId,$amount,0,'Beban depresiasi'),new JournalLine($accumulatedAccountId,0,$amount,'Akumulasi depresiasi')],'depreciation',null,null,['asset_id'=>$asset->id]);}
}
