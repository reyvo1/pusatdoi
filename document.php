<?php
require __DIR__.'/lib/bootstrap.php';
requireLogin(false);
try{
    $id=(int)($_GET['id']??0); if($id<=0)throw new InvalidArgumentException('Dokumen tidak valid.');
    $d=r6DocumentById($id);$path=__DIR__.'/storage/documents/'.basename((string)$d['stored_name']);
    if(!is_file($path))throw new RuntimeException('File dokumen tidak tersedia pada storage.');
    if(!hash_equals((string)$d['sha256'],hash_file('sha256',$path)))throw new RuntimeException('Checksum dokumen tidak cocok.');
    header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');
    header('Content-Type: '.($d['mime_type']??'application/octet-stream'));
    header('Content-Length: '.filesize($path));
    $name=preg_replace('/[^A-Za-z0-9._ -]/','_',basename((string)($d['original_name']??'document')));
    header('Content-Disposition: attachment; filename="'.str_replace('"','',$name).'"');
    readfile($path);exit;
}catch(Throwable $e){http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo 'Dokumen tidak dapat diunduh: '.$e->getMessage();}
