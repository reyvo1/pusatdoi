<?php
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../lib/bootstrap.php';
$pdo=db();$owner=(int)$pdo->query("SELECT id FROM users WHERE email='owner-ci@nexa.local'")->fetchColumn();$_SESSION['user_id']=$owner;$_SESSION['user_name']='UAT Owner';
$s=loadStore();$cash=(int)accountIdByCode($s,1,'1101');$rev=(int)accountIdByCode($s,1,'4101');$exp=(int)accountIdByCode($s,1,'5101');
$start=microtime(true);$count=0;
for($c=1;$c<=4;$c++){
  for($m=1;$m<=12;$m++){
    $date=sprintf('2026-%02d-15',$m);
    // Ensure each period open for test.
    $pdo->exec("INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES(2026,$m,'open') ON DUPLICATE KEY UPDATE status='open',closed_at=NULL,closed_by=NULL");
    for($i=0;$i<20;$i++){
      postJournal(['company_id'=>$c,'date'=>$date,'description'=>"SCALE REV C$c M$m #$i",'amount'=>500000,'debit_account_id'=>$cash,'credit_account_id'=>$rev]);
      postJournal(['company_id'=>$c,'date'=>$date,'description'=>"SCALE EXP C$c M$m #$i",'amount'=>200000,'debit_account_id'=>$exp,'credit_account_id'=>$cash]);
      $count+=2;
    }
  }
}
$elapsed=microtime(true)-$start;$store=loadStore();$tb=trialBalance($store,'2026-09');$balanced=abs(array_sum(array_column($tb,'debit'))-array_sum(array_column($tb,'credit')))<0.01;
$entries=(int)$pdo->query("SELECT COUNT(*) FROM journal_entries WHERE status='posted'")->fetchColumn();
echo "Generated $count journal entries in ".round($elapsed,2)."s; posted=$entries\n";
if(!$balanced||$entries<$count){fwrite(STDERR,"FAIL scale/accounting invariant\n");exit(1);}echo "PASS high-volume multi-entity simulation and trial balance\n";
