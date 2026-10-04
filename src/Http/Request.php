<?php
declare(strict_types=1);
namespace Nexa\Http;
final readonly class Request
{
    public function __construct(public string$method,public string$path,public array$query,public array$post,public array$headers,public string$rawBody){}
    public static function capture():self{$headers=function_exists('getallheaders')?(getallheaders()?:[]):[];return new self(strtoupper($_SERVER['REQUEST_METHOD']??'GET'),parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/',$_GET,$_POST,$headers,(string)file_get_contents('php://input'));}
    public function json():array{$d=json_decode($this->rawBody,true,512,JSON_THROW_ON_ERROR);return is_array($d)?$d:[];}
}
