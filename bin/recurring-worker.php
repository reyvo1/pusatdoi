<?php
declare(strict_types=1);
require __DIR__.'/../lib/bootstrap.php';
try {
    $asOf=$argv[1]??date('Y-m-d');
    $result=r5ProcessRecurringReversals($asOf,true);
    echo json_encode(['ok'=>true]+$result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR,json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE).PHP_EOL);
    exit(1);
}
