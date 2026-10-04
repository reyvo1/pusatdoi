<?php
$root=dirname(__DIR__);
$pass=0;$fail=0;
function a(bool $ok,string $msg):void{global $pass,$fail;if($ok){$pass++;echo "PASS  $msg\n";}else{$fail++;echo "FAIL  $msg\n";}}

// 1. Manifest integrity: every listed file exists and matches SHA-256.
$manifest=$root.'/SOURCE-MANIFEST.txt';
a(is_file($manifest),'SOURCE-MANIFEST exists');
if(is_file($manifest)){
    $checked=0;$bad=[];
    foreach(file($manifest,FILE_IGNORE_NEW_LINES) as $line){
        $line=trim($line); if($line===''||str_starts_with($line,'#'))continue;
        if(!preg_match('/^([a-f0-9]{64})\s+\*?(.+)$/',$line,$m))continue;
        $rel=$m[2];$p=$root.'/'.$rel;$checked++;
        if(!is_file($p)||!hash_equals($m[1],hash_file('sha256',$p)))$bad[]=$m[2];
    }
    a($checked>=150,'Manifest covers full source tree');
    a($bad===[],'Manifest has zero missing/mismatched files'.($bad?' ['.implode(', ',$bad).']':''));
}

// 2. Workflow contract: all pushes to main/master run both production workflows, no paths filter/continue-on-error.
foreach(['full-uat.yml','mysql-production.yml'] as $wf){
    $p=$root.'/.github/workflows/'.$wf;$s=is_file($p)?file_get_contents($p):'';
    a($s!=="",$wf.' exists');
    a(strpos($s,'branches: [ main, master ]')!==false,$wf.' runs on every main/master push');
    a(!preg_match('/^\s*paths:\s*$/m',$s),$wf.' has no push paths filter');
    a(stripos($s,'continue-on-error: true')===false,$wf.' has no continue-on-error bypass');
    a(strpos($s,"\t")===false,$wf.' contains no literal TAB');
    preg_match_all('/(?:run:\s+|php\s+|bash\s+|node\s+)(tests\/[^\s\'\"|&]+|bin\/[^\s\'\"|&]+)/',$s,$mm);
    $missing=[];foreach(array_unique($mm[1]??[]) as $rel){$rel=rtrim($rel,');');if(!is_file($root.'/'.$rel))$missing[]=$rel;}
    a($missing===[],$wf.' references only existing test/bin files'.($missing?' ['.implode(', ',$missing).']':''));
}

// 3. Every browser-authenticated POST API action must verify CSRF before dispatch.
$api=file_get_contents($root.'/api.php');
preg_match_all('/REQUEST_METHOD\\\'\\]===\\\'POST\\\'&&\\$action===\\\'([^\\\']+)\\\'/',$api,$routes);
$missingCsrf=[];
foreach($routes[1]??[] as $action){$needle="\$action==='".$action."'";$pos=strpos($api,$needle);$window=substr($api,$pos,1000);$before=strstr($window,'jsonOut',true);if($before===false)$before=$window;if(strpos($before,'verifyCsrf()')===false)$missingCsrf[]=$action;}
$missingCsrf=array_values(array_unique($missingCsrf));
a(count($routes[1]??[])>=50,'API mutation surface discovered comprehensively');
a($missingCsrf===[],'Every browser POST API route enforces CSRF'.($missingCsrf?' ['.implode(', ',$missingCsrf).']':''));

// 4. Enterprise seed INSERT arity parser for literal VALUES blocks.
function splitTop(string $s):array{$out=[];$start=0;$depth=0;$quote=null;$esc=false;$n=strlen($s);for($i=0;$i<$n;$i++){ $c=$s[$i]; if($quote!==null){if($esc){$esc=false;}elseif($c==='\\'){$esc=true;}elseif($c===$quote){$quote=null;}continue;} if($c==="'"||$c==='"'){$quote=$c;continue;} if($c==='(')$depth++; elseif($c===')')$depth--; elseif($c===','&&$depth===0){$out[]=trim(substr($s,$start,$i-$start));$start=$i+1;}}$tail=trim(substr($s,$start));if($tail!=='')$out[]=$tail;return$out;}
$seed=file_get_contents($root.'/database/seed_enterprise.sql');$arity=[];
if(preg_match_all('/INSERT\s+(?:IGNORE\s+)?INTO\s+`?([A-Za-z0-9_]+)`?\s*\((.*?)\)\s*VALUES\s*(.*?);/is',$seed,$ins,PREG_SET_ORDER)){
    foreach($ins as $m){$cols=splitTop($m[2]);$v=$m[3];$depth=0;$quote=null;$esc=false;$start=null;$tuples=[];$n=strlen($v);for($i=0;$i<$n;$i++){ $c=$v[$i];if($quote!==null){if($esc)$esc=false;elseif($c==='\\')$esc=true;elseif($c===$quote)$quote=null;continue;}if($c==="'"||$c==='"'){$quote=$c;continue;}if($c==='('){if($depth===0)$start=$i+1;$depth++;}elseif($c===')'){$depth--;if($depth===0&&$start!==null){$tuples[]=substr($v,$start,$i-$start);$start=null;}}}foreach($tuples as $i=>$tuple){$vals=splitTop($tuple);if(count($vals)!==count($cols))$arity[]=$m[1].'#'.($i+1).' '.count($cols).'!='.count($vals);}}
}
a($arity===[],'Enterprise seed INSERT column/value arity matches'.($arity?' ['.implode(', ',$arity).']':''));

// 5. Known native-PDO safety contracts that previously escaped local testing.
$boot=file_get_contents($root.'/lib/bootstrap.php');$r4=file_get_contents($root.'/lib/r4_runtime.php');
a(strpos($boot,'PDO::ATTR_EMULATE_PREPARES=>false')!==false,'Production PDO uses native prepares');
a(strpos($r4,"\$params=[\$f['start'],\$f['end'],\$f['start'],\$f['end']]")!==false,'Company metrics binds both duplicated date ranges for native PDO');
a(strpos($boot,"'reversal_of'=>\$id")!==false,'Production reversal return preserves reversal_of audit chain');

// 6. Local runner must not claim production proof.
$runner=file_get_contents($root.'/tests/run-all-local.sh');
a(strpos($runner,'systemic-source-audit.php')!==false,'Local runner includes systemic source audit');
a(strpos($runner,'PRODUCTION NOT YET PROVEN')!==false,'Local runner explicitly refuses production verdict');

echo "Systemic source audit result: $pass passed, $fail failed\n";
exit($fail?1:0);
