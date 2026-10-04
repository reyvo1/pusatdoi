<?php
declare(strict_types=1);
namespace Nexa\Http;
use Nexa\Core\DomainException;
final class Router
{
    private array$routes=[];
    public function add(string$method,string$path,callable$handler):void{$this->routes[strtoupper($method).' '.$path]=$handler;}
    public function dispatch(Request$request):mixed{$key=$request->method.' '.$request->path;$handler=$this->routes[$key]??null;if(!$handler)throw new DomainException('Route tidak ditemukan.');return$handler($request);}
}
