<?php
declare(strict_types=1);
namespace Nexa\Documents;

use Nexa\Core\DomainException;

final class AttachmentPolicy
{
    private const ALLOWED=[
        'application/pdf'=>'pdf',
        'image/jpeg'=>'jpg',
        'image/png'=>'png',
        'image/webp'=>'webp',
    ];
    public function validate(string $path,string $originalName,int $maxBytes=10485760): array
    {
        if(!is_file($path)) throw new DomainException('File bukti tidak ditemukan.');
        $size=filesize($path); if($size===false||$size<=0) throw new DomainException('File bukti kosong.');
        if($size>$maxBytes) throw new DomainException('File bukti maksimal '.round($maxBytes/1048576).' MB.');
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($path)?:'application/octet-stream';
        if(!isset(self::ALLOWED[$mime])) throw new DomainException('Format bukti harus PDF/JPG/PNG/WEBP.');
        $name=trim(basename($originalName)); if($name==='')$name='document.'.self::ALLOWED[$mime];
        if(strlen($name)>255)$name=substr($name,0,255);
        return ['size'=>(int)$size,'mime'=>$mime,'ext'=>self::ALLOWED[$mime],'sha256'=>hash_file('sha256',$path),'original_name'=>$name];
    }
    public function entityType(string $type): string
    {
        $type=strtolower(trim($type));
        if(!in_array($type,['journal','invoice','expense','asset','payment_batch','other'],true)) throw new DomainException('Jenis dokumen tidak valid.');
        return $type;
    }
}
