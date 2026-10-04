<?php
require __DIR__.'/lib/bootstrap.php';
requireLogin(false);
$action=$_GET['action']??'download';
try{
  $s=loadStore();
  if($action==='download'){
    requireAbility('backup.create',$s);
    $payload=['app'=>'NEXA Group Finance','schema_version'=>4,'created_at'=>date('c'),'data'=>$s];
    $json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $sha=hash('sha256',$json);
    $payload['sha256']=$sha;
    $json=json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    writeAudit('backup.downloaded',['sha256'=>$sha]);
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="nexa-backup-'.date('Ymd-His').'.json"');
    header('X-Content-Type-Options: nosniff');
    echo $json; exit;
  }
  if($action==='restore'&&$_SERVER['REQUEST_METHOD']==='POST'){
    verifyCsrf();
    $u=currentUser($s);if(($u['role']??'')!=='group_owner')throw new RuntimeException('Restore hanya dapat dilakukan Group Owner.');
    global $config;if(!$config['demo_mode'])throw new RuntimeException('Restore JSON hanya tersedia pada Demo Mode. Gunakan backup database untuk produksi.');
    if((string)($_POST['confirm']??'')!=='RESTORE')throw new RuntimeException('Konfirmasi RESTORE tidak valid.');
    if(empty($_FILES['backup']['tmp_name'])||!is_uploaded_file($_FILES['backup']['tmp_name']))throw new RuntimeException('File backup tidak ditemukan.');
    if((int)($_FILES['backup']['size']??0)>20*1024*1024)throw new RuntimeException('Backup terlalu besar. Maksimum 20 MB untuk restore JSON.');$raw=(string)file_get_contents($_FILES['backup']['tmp_name']);try{$obj=json_decode($raw,true,64,JSON_THROW_ON_ERROR);}catch(JsonException $e){throw new RuntimeException('JSON backup tidak valid.');}
    if(!is_array($obj)||($obj['app']??'')!=='NEXA Group Finance'||!is_array($obj['data']??null))throw new RuntimeException('Format backup tidak valid.');$expected=(string)($obj['sha256']??'');if(!preg_match('/^[a-f0-9]{64}$/',$expected))throw new RuntimeException('Checksum backup tidak tersedia.');unset($obj['sha256']);$canonical=json_encode($obj,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if(!hash_equals($expected,hash('sha256',$canonical)))throw new RuntimeException('Checksum backup tidak cocok; file mungkin berubah atau rusak.');
    $data=$obj['data'];foreach(['companies','accounts','entries','users'] as $k)if(!isset($data[$k])||!is_array($data[$k]))throw new RuntimeException('Backup tidak lengkap: '.$k);
    foreach($data['entries'] as $e)if(($e['status']??'')==='posted'&&!entryBalanced($e['lines']??[]))throw new RuntimeException('Backup ditolak: ditemukan jurnal posted tidak balance.');
    saveStore($data);writeAudit('backup.restored',['source_name'=>basename((string)$_FILES['backup']['name'])]);
    header('Location: index.php?page=backup&restored=1');exit;
  }
  http_response_code(404);echo 'Not found';
}catch(Throwable $e){http_response_code(422);header('Content-Type:text/plain; charset=utf-8');echo $e->getMessage();}
