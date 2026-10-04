<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=true');
require __DIR__.'/../lib/bootstrap.php';
$orig=is_file(storagePath())?file_get_contents(storagePath()):null;$pass=$fail=0;
function ok6(bool $c,string $m):void{global $pass,$fail;echo ($c?'PASS  ':'FAIL  ').$m."\n";$c?$pass++:$fail++;}
try{
 $_SESSION['user_id']=1;$_SESSION['user_name']='Group Owner';saveStore(demoSeed());
 $csv="code,name,account_type,parent_code,is_cash_bank\n6101,Beban Marketing,expense,,0\n";$job=r6BulkImport(['company_id'=>1,'import_type'=>'coa'],$csv,'coa.csv');ok6(($job['rows_success']??0)===1,'Bulk COA import creates account');
 $s=loadStore();$inv=null;foreach($s['invoices'] as$r)if(($r['type']??'')==='payable'&&(int)$r['company_id']===4){$inv=$r;break;}if(!$inv)throw new RuntimeException('Seed AP invoice unavailable');
 // Keep batch under default approval threshold by paying 5m.
 $batch=r6CreatePaymentBatch(['company_id'=>4,'payment_account_id'=>1,'scheduled_date'=>'2026-09-26','description'=>'AP batch UAT','lines'=>[['invoice_id'=>$inv['id'],'amount'=>5000000,'note'=>'partial']]]);ok6(($batch['status']??'')==='planned','AP payment batch can be planned');
 $posted=r6PostPaymentBatch((int)$batch['id'],true);ok6(($posted['status']??'')==='posted'&&!empty($posted['journal_id']),'AP payment batch posts one ledger journal');
 $tmp=tempnam(sys_get_temp_dir(),'nexa-doc-');file_put_contents($tmp,"%PDF-1.4\nNEXA TEST\n");$doc=r6UploadDocument(['company_id'=>4,'entity_type'=>'payment_batch','entity_id'=>$batch['id'],'description'=>'Bukti UAT'],['error'=>UPLOAD_ERR_OK,'tmp_name'=>$tmp,'name'=>'bukti.pdf']);ok6(!empty($doc['sha256'])&&($doc['entity_type']??'')==='payment_batch','Document evidence stores checksum and relation');
 $fetched=r6DocumentById((int)$doc['id']);ok6(($fetched['sha256']??'')===$doc['sha256'],'Document evidence can be retrieved through access gate');
 @unlink(__DIR__.'/../storage/documents/'.($doc['stored_name']??''));@unlink($tmp);
 $api=file_get_contents(__DIR__.'/../api.php');$js=file_get_contents(__DIR__.'/../assets/r6-ui.js');preg_match_all("/api\('([^']+)'/",$js,$ua);preg_match_all("/action==='([^']+)'/",$api,$aa);ok6(array_diff(array_unique($ua[1]),array_unique($aa[1]))===[],'Every R6 UI action has backend route');
}catch(Throwable$e){echo 'FATAL '.$e::class.': '.$e->getMessage()."\n";$fail++;}
finally{if($orig===null)@unlink(storagePath());else file_put_contents(storagePath(),$orig,LOCK_EX);}
echo "R6 usability result: $pass passed, $fail failed\n";exit($fail?1:0);
