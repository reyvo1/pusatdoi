<?php
declare(strict_types=1);
namespace Nexa\Party;

use Nexa\Core\DomainException;
final class PartyService
{
    public function validate(array $input):array
    {
        $company=(int)($input['company_id']??0);$type=(string)($input['party_type']??'customer');$code=strtoupper(trim((string)($input['code']??'')));$name=trim((string)($input['name']??''));$currency=strtoupper(trim((string)($input['currency']??'IDR')));
        if($company<=0||!in_array($type,['customer','vendor','both'],true)||!preg_match('/^[A-Z0-9_-]{2,50}$/',$code)||strlen($name)<2||!preg_match('/^[A-Z]{3}$/',$currency))throw new DomainException('Master customer/vendor tidak valid.');
        return ['company_id'=>$company,'party_type'=>$type,'code'=>$code,'name'=>$name,'tax_id'=>trim((string)($input['tax_id']??''))?:null,'email'=>trim((string)($input['email']??''))?:null,'phone'=>trim((string)($input['phone']??''))?:null,'address_text'=>trim((string)($input['address_text']??''))?:null,'currency'=>$currency,'ar_account_id'=>($input['ar_account_id']??'')!==''?(int)$input['ar_account_id']:null,'ap_account_id'=>($input['ap_account_id']??'')!==''?(int)$input['ap_account_id']:null];
    }
}
