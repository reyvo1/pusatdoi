<?php
declare(strict_types=1);
require dirname(__DIR__).'/lib/bootstrap.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$key=(string)($argv[1]??getenv('NEXA_WORKER_KEY')??'');
if(strlen((string)($config['worker_key']??''))<24||!hash_equals((string)$config['worker_key'],$key)){fwrite(STDERR,"Worker key invalid.\n");exit(2);}
$_SESSION['user_id']=(int)($config['worker_user_id']??1);$_SESSION['user_name']='NEXA Worker';
try{$a=r4ProcessIntegrationBatch(50);$b=r4DispatchOutbox(50);echo json_encode(['integration'=>$a,'outbox'=>$b],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit($a['failed']?1:0);}catch(Throwable$e){fwrite(STDERR,$e->getMessage()."\n");exit(1);}
