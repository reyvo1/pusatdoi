<?php
declare(strict_types=1);
$pass=$fail=0;
function sc(bool $ok,string $label):void{global $pass,$fail;echo ($ok?'PASS  ':'FAIL  ').$label."\n";$ok?$pass++:$fail++;}
$root=dirname(__DIR__);
$r7=file_get_contents($root.'/database/schema_enterprise_r7.sql');
$v6=file_get_contents($root.'/database/migrations/20260925_v6_enterprise_r3.sql');
$v7=file_get_contents($root.'/database/migrations/20260926_v7_enterprise_r4.sql');
$mysqlWorkflow=file_get_contents($root.'/.github/workflows/mysql-production.yml');
$fullWorkflow=file_get_contents($root.'/.github/workflows/full-uat.yml');
sc(substr_count($r7,'uq_payment_invoice')===1,'Fresh R7 schema defines payment allocation unique exactly once');
sc(substr_count($v7,'uq_payment_invoice')===0,'R4 migration does not recreate R3 payment allocation unique');
$idx=strpos($v6,'ADD KEY idx_budget_company(company_id)');$drop=strpos($v6,'DROP INDEX uq_budget');
sc($idx!==false&&$drop!==false&&$idx<$drop,'R3 migration creates FK-supporting budget index before dropping legacy unique');
sc(strpos($r7,'KEY idx_budget_company(company_id)')!==false,'Fresh R7 schema has explicit budget company index');
sc(strpos($mysqlWorkflow,"run: |\n          php tests/mysql-production.php\n          php tests/r5-mysql-uat.php\n          php tests/r7-mysql-uat.php")!==false,'MySQL workflow runs production test scripts as separate commands');
sc(substr_count($fullWorkflow,'schema_enterprise_r7.sql')>=4,'Full UAT uses R7 fresh schema across MySQL-dependent gates');
echo "Schema contract result: $pass passed, $fail failed\n";
exit($fail?1:0);
