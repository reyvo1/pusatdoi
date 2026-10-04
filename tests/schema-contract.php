<?php
declare(strict_types=1);

$pass=$fail=0;
function sc(bool $ok,string $label):void{
    global $pass,$fail;
    echo ($ok?'PASS  ':'FAIL  ').$label."\n";
    $ok?$pass++:$fail++;
}
function createBody(string $sql,string $table):string{
    if(!preg_match('/CREATE TABLE\s+'.preg_quote($table,'/').'\s*\((.*?)\)\s*ENGINE=InnoDB;/si',$sql,$m)) return '';
    return $m[1];
}
function createColumns(string $sql,string $table):array{
    $body=createBody($sql,$table);$out=[];
    foreach(preg_split('/\R/',$body) as $line){
        $line=trim(rtrim($line,','));
        if($line===''||preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|CHECK|FOREIGN)\b/i',$line))continue;
        if(preg_match('/^`?([A-Za-z0-9_]+)`?\s+/',$line,$m))$out[]=$m[1];
    }
    return $out;
}
function duplicateCreateColumns(string $sql):array{
    $dups=[];
    if(!preg_match_all('/CREATE TABLE\s+([A-Za-z0-9_]+)\s*\((.*?)\)\s*ENGINE=InnoDB;/si',$sql,$ms,PREG_SET_ORDER))return $dups;
    foreach($ms as $m){$cols=[];foreach(preg_split('/\R/',$m[2]) as $line){$line=trim(rtrim($line,','));if($line===''||preg_match('/^(PRIMARY|UNIQUE|KEY|INDEX|CONSTRAINT|CHECK|FOREIGN)\b/i',$line))continue;if(preg_match('/^`?([A-Za-z0-9_]+)`?\s+/',$line,$cm))$cols[]=$cm[1];}$c=array_count_values($cols);foreach($c as $col=>$n)if($n>1)$dups[]=$m[1].'.'.$col;}
    return $dups;
}


function forwardForeignKeyDependencies(string $sql):array{
    $tables=[];
    if(!preg_match_all('/CREATE TABLE\s+([A-Za-z0-9_]+)\s*\((.*?)\)\s*ENGINE=InnoDB;/si',$sql,$ms,PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) return [];
    foreach($ms as $i=>$m){$tables[strtolower($m[1][0])]=['order'=>$i,'body'=>$m[2][0]];}
    $bad=[];
    foreach($tables as $table=>$meta){
        if(!preg_match_all('/FOREIGN KEY\s*\([^)]+\)\s+REFERENCES\s+([A-Za-z0-9_]+)/i',$meta['body'],$fm)) continue;
        foreach($fm[1] as $ref){$ref=strtolower($ref);if(isset($tables[$ref])&&$tables[$ref]['order']>$meta['order'])$bad[]=$table.'->'.$ref;}
    }
    return array_values(array_unique($bad));
}

function duplicateCreateNames(string $sql):array{
    $dups=[];$globalConstraints=[];
    if(!preg_match_all('/CREATE TABLE\s+([A-Za-z0-9_]+)\s*\((.*?)\)\s*ENGINE=InnoDB;/si',$sql,$ms,PREG_SET_ORDER))return ['tables'=>[],'indexes'=>[],'constraints'=>[]];
    $tables=[];
    foreach($ms as$m){$table=strtolower($m[1]);$tables[]=$table;$body=$m[2];$names=[];
        preg_match_all('/\b(?:UNIQUE\s+)?(?:KEY|INDEX)\s+([A-Za-z0-9_]+)/i',$body,$im);foreach($im[1] as$n)$names[]=strtolower($n);
        foreach(array_count_values($names) as$n=>$count)if($count>1)$dups[]=$table.'.'.$n;
        preg_match_all('/\bCONSTRAINT\s+([A-Za-z0-9_]+)/i',$body,$cm);foreach($cm[1] as$n)$globalConstraints[]=strtolower($n);
    }
    $tableD=[];foreach(array_count_values($tables) as$n=>$count)if($count>1)$tableD[]=$n;
    $constraintD=[];foreach(array_count_values($globalConstraints) as$n=>$count)if($count>1)$constraintD[]=$n;
    return ['tables'=>$tableD,'indexes'=>$dups,'constraints'=>$constraintD];
}


function sqlTopLevelValueCount(string $row):int{
    $count=1;$depth=0;$quote=false;$len=strlen($row);
    if(trim($row)==='')return 0;
    for($i=0;$i<$len;$i++){
        $ch=$row[$i];
        if($quote){
            if($ch==="'"){
                if($i+1<$len&&$row[$i+1]==="'"){$i++;continue;}
                $quote=false;
            }elseif($ch==='\\'){$i++;}
            continue;
        }
        if($ch==="'"){$quote=true;continue;}
        if($ch==='('){$depth++;continue;}
        if($ch===')'){$depth--;continue;}
        if($ch===','&&$depth===0)$count++;
    }
    return $count;
}
function seedInsertArityMismatches(string $sql):array{
    $bad=[];
    if(!preg_match_all('/INSERT\\s+INTO\\s+([A-Za-z0-9_]+)\\s*\\((.*?)\\)\\s*VALUES\\s*(.*?);/si',$sql,$ms,PREG_SET_ORDER))return $bad;
    foreach($ms as$m){
        $table=$m[1];$columnCount=count(array_filter(array_map('trim',explode(',',$m[2])),fn($v)=>$v!==''));$values=$m[3];
        $rows=[];$depth=0;$quote=false;$start=null;$len=strlen($values);
        for($i=0;$i<$len;$i++){
            $ch=$values[$i];
            if($quote){
                if($ch==="'"){
                    if($i+1<$len&&$values[$i+1]==="'"){$i++;continue;}
                    $quote=false;
                }elseif($ch==='\\'){$i++;}
                continue;
            }
            if($ch==="'"){$quote=true;continue;}
            if($ch==='('){if($depth===0)$start=$i+1;$depth++;continue;}
            if($ch===')'){$depth--;if($depth===0&&$start!==null){$rows[]=substr($values,$start,$i-$start);$start=null;}continue;}
        }
        foreach($rows as$i=>$row){$valueCount=sqlTopLevelValueCount($row);if($valueCount!==$columnCount)$bad[]=$table.'#'.($i+1).':'.$columnCount.'cols/'.$valueCount.'vals';}
    }
    return $bad;
}

$root=dirname(__DIR__);
$r7=file_get_contents($root.'/database/schema_enterprise_r7.sql');
$seed=file_get_contents($root.'/database/seed_enterprise.sql');
$v6=file_get_contents($root.'/database/migrations/20260925_v6_enterprise_r3.sql');
$v7=file_get_contents($root.'/database/migrations/20260926_v7_enterprise_r4.sql');
$inspector=file_get_contents($root.'/src/Infrastructure/Persistence/SchemaInspector.php');
$mysqlWorkflow=file_get_contents($root.'/.github/workflows/mysql-production.yml');
$fullWorkflow=file_get_contents($root.'/.github/workflows/full-uat.yml');

// Fresh schema must be a canonical final schema, never a replay of migration ALTERs.
sc(!preg_match('/^ALTER TABLE\b/im',$r7),'Fresh R7 schema contains no migration ALTER TABLE leftovers');
sc(duplicateCreateColumns($r7)===[],'Fresh R7 CREATE TABLE definitions contain no duplicate columns');
$nameDups=duplicateCreateNames($r7);
sc($nameDups['tables']===[],'Fresh R7 schema contains no duplicate CREATE TABLE names');
sc($nameDups['indexes']===[],'Fresh R7 tables contain no duplicate named indexes');
sc($nameDups['constraints']===[],'Fresh R7 schema contains no duplicate constraint names');
$seedArity=seedInsertArityMismatches($seed);
sc($seedArity===[],'Enterprise seed INSERT column/value arity matches'.($seedArity?' ['.implode(',',$seedArity).']':''));
$forwardFks=forwardForeignKeyDependencies($r7);
sc($forwardFks===[],'Fresh R7 schema has no forward foreign-key dependencies'.($forwardFks?' ['.implode(',',$forwardFks).']':''));
sc(substr_count($r7,'uq_payment_invoice')===1,'Fresh R7 schema defines payment allocation unique exactly once');
sc(substr_count($v7,'uq_payment_invoice')===0,'R4 migration does not recreate R3 payment allocation unique');

$reconRequired=['period','period_start','period_end','opening_balance','closing_balance','matched_amount','difference_amount','closed_at','closed_by','reopened_at','reopened_by'];
$reconCols=createColumns($r7,'bank_reconciliation_sessions');
sc($reconCols!==[],'Fresh schema defines bank_reconciliation_sessions');
foreach($reconRequired as $column)sc(in_array($column,$reconCols,true),'Fresh reconciliation schema has '.$column);
sc(strpos(createBody($r7,'bank_reconciliation_sessions'),'uq_bank_recon_session')!==false,'Fresh reconciliation schema preserves unique period key');

// Upgrade path must add the same runtime columns when starting from R2/R3.
foreach(['period_start','period_end','matched_amount','difference_amount','closed_by','reopened_at','reopened_by'] as $column){
    sc(strpos($v7,'ADD COLUMN '.$column)!==false,'R4 migration adds reconciliation '.$column);
}

$idx=strpos($v6,'ADD KEY idx_budget_company(company_id)');$drop=strpos($v6,'DROP INDEX uq_budget');
sc($idx!==false&&$drop!==false&&$idx<$drop,'R3 migration creates FK-supporting budget index before dropping legacy unique');
sc(strpos($r7,'KEY idx_budget_company(company_id)')!==false,'Fresh R7 schema has explicit budget company index');

// Information-schema column labels differ in case across MySQL/PDO combinations; inspector must be positional.
sc(strpos($inspector,'PDO::FETCH_COLUMN')!==false,'SchemaInspector reads information_schema positionally');
sc(stripos($inspector,'TABLE_NAME AS table_name')!==false,'SchemaInspector aliases TABLE_NAME explicitly');

sc(strpos($mysqlWorkflow,"run: |\n          php tests/mysql-production.php\n          php tests/r5-mysql-uat.php\n          php tests/r7-mysql-uat.php")!==false,'MySQL workflow runs production test scripts as separate commands');
sc(strpos($mysqlWorkflow,'paths:')===false&&strpos($mysqlWorkflow,'branches: [ main, master ]')!==false,'Standalone MySQL workflow runs every main/master push for exact-SHA evidence');
sc(substr_count($fullWorkflow,'schema_enterprise_r7.sql')>=4,'Full UAT uses R7 fresh schema across MySQL-dependent gates');
sc(strpos($fullWorkflow,"\t")===false&&strpos($mysqlWorkflow,"\t")===false,'GitHub workflow YAML contains no literal TAB characters');
sc(strpos($fullWorkflow,'Snapshot exact legacy master data')!==false&&strpos($fullWorkflow,'diff -u /tmp/pre-companies.tsv /tmp/post-companies.tsv')!==false&&strpos($fullWorkflow,'diff -u /tmp/pre-accounts.tsv /tmp/post-legacy-accounts.tsv')!==false,'Migration UAT preserves exact legacy company/account rows');
sc(strpos($fullWorkflow,'test "$required" -eq 5')!==false&&strpos($fullWorkflow,'legacy + 5')!==false,'Migration UAT requires exactly five specified system accounts');

echo "Schema contract result: $pass passed, $fail failed\n";
exit($fail?1:0);
