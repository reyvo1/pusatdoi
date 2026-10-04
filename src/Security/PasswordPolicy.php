<?php
declare(strict_types=1);
namespace Nexa\Security;
use Nexa\Core\DomainException;
final class PasswordPolicy
{
    public function assert(string $password):void{if(strlen($password)<12)throw new DomainException('Password minimal 12 karakter.');if(!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/\\d/',$password)||!preg_match('/[^A-Za-z0-9]/',$password))throw new DomainException('Password harus memiliki huruf besar, kecil, angka, dan simbol.');}
    public function hash(string $password):string{$this->assert($password);return password_hash($password,PASSWORD_DEFAULT);}
}
