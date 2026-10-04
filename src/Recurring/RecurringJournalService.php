<?php
declare(strict_types=1);
namespace Nexa\Recurring;

final class RecurringJournalService
{
    public function nextDate(string $date,string $frequency): string
    {
        $d=new \DateTimeImmutable($date);return match($frequency){'weekly'=>$d->modify('+7 days')->format('Y-m-d'),'monthly'=>$d->modify('+1 month')->format('Y-m-d'),'quarterly'=>$d->modify('+3 months')->format('Y-m-d'),'yearly'=>$d->modify('+1 year')->format('Y-m-d'),default=>throw new \InvalidArgumentException('Frekuensi recurring tidak didukung.')};
    }
    public function validateTemplate(array $input): array
    {
        $company=(int)($input['company_id']??0);$name=trim((string)($input['name']??''));$frequency=(string)($input['frequency']??'monthly');$next=(string)($input['next_run_date']??'');$description=trim((string)($input['description']??$name));$lines=$input['lines']??[];if(is_string($lines))$lines=json_decode($lines,true);
        if($company<=0||strlen($name)<3||!in_array($frequency,['weekly','monthly','quarterly','yearly'],true)||!preg_match('/^\d{4}-\d{2}-\d{2}$/',$next)||!is_array($lines)||count($lines)<2)throw new \InvalidArgumentException('Template recurring tidak valid.');
        $dr=$cr=0;foreach($lines as $r){$d=(int)round((float)($r['debit']??0));$c=(int)round((float)($r['credit']??0));if((int)($r['account_id']??0)<=0||$d<0||$c<0||($d>0&&$c>0)||($d===0&&$c===0))throw new \InvalidArgumentException('Baris recurring tidak valid.');$dr+=$d;$cr+=$c;}if($dr<=0||$dr!==$cr)throw new \RuntimeException('Template recurring harus balance.');
        return compact('company','name','frequency','next','description','lines','dr');
    }
}
