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
    $idem=new \Nexa\Integration\IdempotencyService();
    $payload=$idem->canonicalJson($in);
    $key=$idem->key($source,$ref);
    $duplicate=false;$id=0;$eventId=0;
    $pdo->beginTransaction();
    try{
        // Atomic idempotency claim: duplicate path updates only LAST_INSERT_ID(id), never accepted payload.
        $ev=$pdo->prepare("INSERT INTO integration_events(source,external_ref,idempotency_key,company_id,event_type,event_version,occurred_at,status,payload_json) VALUES(?,?,?,?,?,?,?,'received',?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $ev->execute([$source,$ref,$key,$company,$eventType,$eventVersion,$occurredAt,$payload]);
        $inserted=$ev->rowCount()===1;
        $eventId=(int)$pdo->lastInsertId();
        $existing=$pdo->prepare("SELECT id,company_id,event_type,event_version,payload_json,status FROM integration_events WHERE id=? FOR UPDATE");
        $existing->execute([$eventId]);
        $row=$existing->fetch();
        if(!$row)throw new RuntimeException('Event integration tidak dapat dibaca kembali.');
        $stored=json_decode((string)$row['payload_json'],true,64,JSON_THROW_ON_ERROR);
        if((int)$row['company_id']!==$company||(string)$row['event_type']!==$eventType||(string)$row['event_version']!==$eventVersion||!$idem->samePayload($stored,$in)){
            throw new \Nexa\Core\DomainException('Konflik idempotency: source/external_ref sudah pernah diterima dengan payload berbeda.',409);
        }

        $st=$pdo->prepare("INSERT INTO integration_staging(source,company_id,external_ref,status,amount,payload_json) VALUES(?,?,?,'received',?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
        $st->execute([$source,$company,$ref,$amount,$payload]);
        $id=(int)$pdo->lastInsertId();
        $stage=$pdo->prepare("SELECT company_id,amount,payload_json FROM integration_staging WHERE id=? FOR UPDATE");
        $stage->execute([$id]);
        $stageRow=$stage->fetch();
        if(!$stageRow)throw new RuntimeException('Staging integration tidak dapat dibaca kembali.');
        $stagePayload=json_decode((string)$stageRow['payload_json'],true,64,JSON_THROW_ON_ERROR);
        if((int)$stageRow['company_id']!==$company||abs((float)$stageRow['amount']-$amount)>0.005||!$idem->samePayload($stagePayload,$in)){
            throw new \Nexa\Core\DomainException('Konflik idempotency: staging source/external_ref sudah pernah diterima dengan payload berbeda.',409);
        }

        $duplicate=!$inserted;
        if($inserted)(new \Nexa\Audit\TransactionalAuditService($pdo))->record('integration.received','integration_event',(string)$eventId,['source'=>$source,'external_ref'=>$ref],null,$company);
        $pdo->commit();
    }catch(Throwable$e){if($pdo->inTransaction())$pdo->rollBack();throw$e;}
    if(!$duplicate)r4FileAudit('integration.received',['id'=>$id,'event_id'=>$eventId,'source'=>$source,'company_id'=>$company,'external_ref'=>$ref]);
    http_response_code(202);echo json_encode(['ok'=>true,'id'=>$id,'event_id'=>$eventId,'status'=>'received','idempotent'=>true,'duplicate'=>$duplicate],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(JsonException $e){http_response_code(400);echo json_encode(['ok'=>false,'message'=>'JSON tidak valid.']);}
catch(\Nexa\Core\DomainException $e){$status=$e->getCode()===409?409:422;http_response_code($status);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
catch(InvalidArgumentException $e){http_response_code(422);echo json_encode(['ok'=>false,'message'=>$e->getMessage()]);}
catch(Throwable $e){error_log('NEXA integration: '.$e->getMessage());http_response_code(503);echo json_encode(['ok'=>false,'message'=>'Integration service tidak tersedia.']);}
