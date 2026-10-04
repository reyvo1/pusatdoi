<?php
declare(strict_types=1);
namespace Nexa\Notifications;

final class NotificationService
{
    public function generate(array $store,string $today): array
    {
        $rows=[];$id=1;
        foreach($store['approvals']??[] as $r) if(($r['status']??'')==='pending') $rows[]=$this->row($id++,'approval','warning','Approval menunggu keputusan',(string)($r['description']??'Transaksi'),(int)($r['company_id']??0),'?page=approvals');
        foreach($store['invoices']??[] as $r){$open=(float)($r['amount']??0)-(float)($r['paid']??$r['paid_amount']??0);if($open>0&&($r['due_date']??'')<$today&&in_array((string)($r['status']??''),['open','partial'],true))$rows[]=$this->row($id++,'invoice','danger','Invoice lewat jatuh tempo',(string)($r['number']??$r['invoice_no']??'Invoice').' · '.number_format($open,0,',','.'),(int)($r['company_id']??0),'?page=arap');}
        foreach($store['bank_feed']??[] as $r) if(($r['status']??'')==='unmatched') $rows[]=$this->row($id++,'bank','info','Mutasi bank belum direkonsiliasi',(string)($r['description']??'Bank feed'),(int)($r['company_id']??0),'?page=reconciliation');
        foreach($store['employee_advances']??[] as $r) if(in_array((string)($r['status']??''),['open','partial'],true)&&!empty($r['due_date'])&&$r['due_date']<$today) $rows[]=$this->row($id++,'advance','danger','Uang muka belum dipertanggungjawabkan',(string)($r['employee_name']??'Karyawan').' · '.number_format((float)($r['outstanding_amount']??0),0,',','.'),(int)($r['company_id']??0),'?page=advances');
        foreach($store['loan_facilities']??[] as $r) if(($r['status']??'')==='active'&&!empty($r['maturity_date'])&&$r['maturity_date']<=date('Y-m-d',strtotime($today.' +30 days'))) $rows[]=$this->row($id++,'loan','warning','Pinjaman mendekati jatuh tempo',(string)($r['lender_name']??'Lender').' · '.number_format((float)($r['outstanding_principal']??0),0,',','.'),(int)($r['company_id']??0),'?page=financing');
        return $rows;
    }

    private function row(int $id,string $type,string $severity,string $title,string $message,int $company,string $url):array{return compact('id','type','severity','title','message')+['company_id'=>$company,'url'=>$url];}
}
