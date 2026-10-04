<?php
require __DIR__.'/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');echo json_encode(['ok'=>false,'message'=>'Gunakan POST JSON.']);exit;}
try{
    if($config['demo_mode'])throw new RuntimeException('Integration ingestion dinonaktifkan pada Demo Mode.');
    $configured=(string)($config['integration_key']??'');
    if(strlen($configured)<24)throw new RuntimeException('Integration key server belum dikonfigurasi.');
    $provided=(string)($_SERVER['HTTP_X_NEXA_KEY']??'');
    if($provided===''||!hash_equals($configured,$provided)){http_response_code(401);echo json_encode(['ok'=>false,'message'=>'Integration key tidak valid.']);exit;}
    $declared=(int)($_SERVER['CONTENT_LENGTH']??0);if($declared>1024*1024)throw new InvalidArgumentException('Payload integration melebihi 1 MB.');$raw=(string)file_get_contents('php://input');if(strlen($raw)>1024*1024)throw new InvalidArgumentException('Payload integration melebihi 1 MB.');$in=json_decode($raw,true,64,JSON_THROW_ON_ERROR);
    $source=strtoupper(trim((string)($in['source']??'')));$company=(int)($in['company_id']??0);$ref=trim((string)($in['external_ref']??''));$amount=(float)($in['amount']??0);$eventType=strtolower(trim((string)($in['event_type']??'journal')));$eventVersion=trim((string)($in['event_version']??'1'));$occurredAt=trim((string)($in['occurred_at']??''))?:null;
    if(!preg_match('/^[A-Z0-9_.:-]{2,80}$/',$source)||$company<=0||textLength($ref)<2||textLength($ref)>160||$amount<0||!preg_match('/^[a-z0-9_.:-]{2,80}$/',$eventType))throw new InvalidArgumentException('Payload integration tidak valid.');
    $pdo=db();$q=$pdo->prepare('SELECT id FROM companies WHERE id=? AND is_active=1');$q->execute([$company]);if(!$q->fetchColumn())throw new InvalidArgumentException('Badan usaha integration tidak ditemukan.');
    $payload=json_encode($in,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $pdo->beginTransaction();try{$st=$pdo->prepare("INSERT INTO integration_staging(source,company_id,external_ref,status,amount,payload_json) VALUES(?,?,?,'received',?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),payload_json=VALUES(payload_json),amount=VALUES(amount)");$st->execute([$source,$company,$ref,$amount,$payload]);$id=(int)$pdo->lastInsertId();$key=hash('sha256',$source.'|'.$company.'|'.$ref.'|'.$eventType);$ev=$pdo->prepare("INSERT INTO integration_events(source,external_ref,idempotency_key,company_id,event_type,event_version,occurred_at,status,payload_json) VALUES(?,?,?,?,?,?,?,'received',?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id),payload_json=VALUES(payload_json)");$ev->execute([$source,$ref,$key,$company,$eventType,$eventVersion,$occurredAt,$payload]);$eventId=(int)$pdo->lastInsertId();(new \Nexa\Audit\TransactionalAuditService($pdo))->record('integration.received','integration_event',(string)$eventId,['source'=>$source,'external_ref'=>$ref],null,$company);$pdo->commit();}catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}r4FileAudit('integration.received',['id'=>$id,'event_id'=>$eventId,'source'=>$source,'company_id'=>$company,'external_ref'=>$ref]);
    http_response_code(202);echo json_encode(['ok'=>true,'id'=>$id,'event_id'=>$eventId,'status'=>'received','idempotent'=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(JsonException $e){http_response_code(400);echo json_encode(['ok'=>false,'message'=>'JSON tidak valid.']);}
catch(InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
catch(Throwable $e){error_log('NEXA integration: '.$e->getMessage());http_response_code(503);echo json_encode(['ok'=>false,'message'=>'Integration service tidak tersedia.']);}
