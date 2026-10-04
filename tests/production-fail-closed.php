<?php
putenv('NEXA_DEMO_MODE=false');
putenv('NEXA_DB_HOST=127.0.0.1');
putenv('NEXA_DB_PORT=65534');
require __DIR__.'/../lib/bootstrap.php';
try{loadStore();fwrite(STDERR,"FAIL production mode unexpectedly loaded data\n");exit(1);}catch(Throwable $e){echo "PASS production mode fails closed: ".$e->getMessage()."\n";exit(0);}
