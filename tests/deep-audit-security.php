<?php
declare(strict_types=1);
require __DIR__.'/../src/autoload.php';
$pass=$fail=0;
function da(bool $ok,string $label):void{global $pass,$fail;echo ($ok?'PASS  ':'FAIL  ').$label."\n";$ok?$pass++:$fail++;}
$root=dirname(__DIR__);
$configSrc=file_get_contents($root.'/config/config.php');
$setup=file_get_contents($root.'/setup.php');
$index=file_get_contents($root.'/index.php');
$api=file_get_contents($root.'/api.php');
$integration=file_get_contents($root.'/integration.php');
$r4=file_get_contents($root.'/lib/r4_runtime.php');
$r6=file_get_contents($root.'/lib/r6_runtime.php');
$schema=file_get_contents($root.'/database/schema_enterprise_r7.sql');
$v7=file_get_contents($root.'/database/migrations/20260926_v7_enterprise_r4.sql');
$httpUat=file_get_contents($root.'/tests/http-full-uat.sh');

// Production must never silently fall back to demo data.
putenv('NEXA_ENV=production');putenv('NEXA_DEMO_MODE');
$cfg=require $root.'/config/config.php';
da(($cfg['environment']??'')==='production' && ($cfg['demo_mode']??true)===false,'Production environment defaults fail-closed to MySQL mode');
putenv('NEXA_ENV=development');putenv('NEXA_DEMO_MODE');
$cfg2=require $root.'/config/config.php';
da(($cfg2['demo_mode']??false)===true,'Development environment may default to demo mode');
putenv('NEXA_ENV');putenv('NEXA_DEMO_MODE');

da(strpos($setup,'strlen($setupKey)<24')!==false && strpos($setup,'hash_equals($setupKey,$key)')!==false,'First-owner setup requires strong configured setup key');
da(strpos($index,'JSON_HEX_TAG')!==false && strpos($index,'JSON_HEX_AMP')!==false && strpos($index,'JSON_HEX_APOS')!==false && strpos($index,'JSON_HEX_QUOT')!==false,'Inline APP_DATA JSON is script-breakout hardened');

da(strpos($api,'catch(PDOException $e)')!==false && strpos($api,'NEXA API database error')!==false,'API hides raw PDO errors from clients and logs server-side');

$idem=new \Nexa\Integration\IdempotencyService();
$a=['source'=>'POS','payload'=>['b'=>2,'a'=>1],'rows'=>[['z'=>2,'a'=>1]]];
$b=['rows'=>[['a'=>1,'z'=>2]],'payload'=>['a'=>1,'b'=>2],'source'=>'POS'];
da($idem->samePayload($a,$b),'Integration payload fingerprint is stable across associative key order');
da($idem->key('POS','REF-1')===$idem->key('pos','REF-1'),'Integration idempotency key normalizes source case');
da(strpos($integration,'ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)')!==false && strpos($integration,'payload_json=VALUES(payload_json)')===false && strpos($integration,'amount=VALUES(amount)')===false,'Integration duplicate path is atomic no-op and never mutates accepted payload');
da(strpos($integration,'Konflik idempotency')!==false && strpos($integration,"'duplicate'=>\$duplicate")!==false,'Integration distinguishes exact retry from conflicting duplicate');

da(strpos($schema,"external_ref VARCHAR(160) NOT NULL")!==false,'Fresh integration event external_ref matches 160-char API contract');
da(strpos($v7,'MODIFY COLUMN external_ref VARCHAR(160) NOT NULL')!==false,'Upgrade migration aligns integration event external_ref to 160');

da(strpos($r6,'CSV maksimal 5 MB')!==false,'Bulk onboarding upload has 5 MB resource limit');
da(strpos($r4,'CSV bank maksimal 5 MB')!==false && strpos($r4,'10.000 baris')!==false,'Bank statement import has file and row resource limits');

// Every mutating API action must have CSRF in its route block.
$lines=preg_split('/\R/',$api);$missing=[];
foreach($lines as $i=>$line){
    if(strpos($line,"\$_SERVER['REQUEST_METHOD']==='POST'")===false || !preg_match('/\\$action===\'([^\']+)\'/',$line,$m))continue;
    $chunk=implode("\n",array_slice($lines,$i,8));
    if(strpos($chunk,'verifyCsrf()')===false)$missing[]=$m[1];
}
da($missing===[],'Every browser-authenticated POST API route enforces CSRF'.($missing?' ['.implode(',',$missing).']':''));
da(strpos($httpUat,'unauth_code=$(curl')!==false && strpos($httpUat,'[ "$unauth_code" = "401" ]')!==false && strpos($httpUat,'|| true')===false,'HTTP UAT unauthorized mutation is a hard 401 gate without bypass');
da(strpos($httpUat,'unauth_before=$(mysql')!==false && strpos($httpUat,'unauth_after=$(mysql')!==false && strpos($httpUat,'[ "$unauth_before" = "$unauth_after" ]')!==false,'HTTP UAT proves unauthorized request cannot mutate company state');

echo "Deep audit security result: $pass passed, $fail failed\n";exit($fail?1:0);
