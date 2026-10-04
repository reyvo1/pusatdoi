<?php
require __DIR__.'/lib/bootstrap.php';
require_once __DIR__.'/lib/ReportExport.php';
requireLogin(false);$__s=loadStore();$__u=currentUser($__s);if(!$__u||!roleCan((string)$__u['role'],'report.export')){http_response_code(403);exit('Akses export ditolak.');}
$companyId=max(0,(int)($_GET['company_id']??0));
$data=financialReportData($companyId?:null);
$format=$_GET['format']??'csv';
$report=$_GET['report']??'ledger';
$allowed=['transactions','ledger','summary','pl','balance','equity','cashflow','trial','intercompany'];
if(!in_array($report,$allowed,true))$report='ledger';
$rows=[];$headers=[];
switch($report){
    case 'transactions': case 'ledger':
        $headers=['Tanggal','No Jurnal','Badan Usaha','Deskripsi','Akun','Nominal','Status'];
        foreach($data['transactions'] as $t)$rows[]=[$t['date'],$t['no'],$t['company'],$t['desc'],$t['account'],$t['amount'],$t['status']];
        break;
    case 'summary': case 'pl':
        $headers=['Badan Usaha','Pendapatan','Beban','Laba Bersih','Kas & Bank','Margin %'];
        foreach($data['companies'] as $c)$rows[]=[$c['name'],$c['revenue'],$c['expense'],$c['profit'],$c['cash'],$c['margin']];
        if(!$companyId)$rows[]=['TOTAL GROUP',$data['metrics']['revenue'],$data['metrics']['expense'],$data['metrics']['profit'],$data['metrics']['cash'],$data['metrics']['margin']];
        break;
    case 'balance':
        $headers=['Komponen','Nilai'];$b=$data['balance_sheet'];
        $rows=[['Aset',$b['assets']],['Liabilitas',$b['liabilities']],['Ekuitas + laba ditahan',$b['equity']],['Net assets',$b['net_assets']],['Selisih persamaan',$b['difference']]];
        break;
    case 'equity':
        $headers=['Komponen','Nilai'];$e=$data['equity_statement'];
        $rows=[['Ekuitas awal',$e['opening_equity']],['Perubahan modal langsung',$e['direct_equity_movement']],['Laba/rugi periode',$e['current_profit']],['Penyesuaian konsolidasi/lainnya',$e['other_adjustments']],['Ekuitas akhir',$e['closing_equity']]];
        break;
    case 'cashflow':
        $headers=['Periode','Operating','Investing','Financing','Net Cash Flow'];
        foreach($data['cashflow'] as $r){$op=$r['operating']*1000000;$inv=$r['investing']*1000000;$fin=$r['financing']*1000000;$rows[]=[$r['m'],$op,$inv,$fin,$op+$inv+$fin];}
        break;
    case 'trial':
        $headers=['Kode','Akun','Tipe','Debit','Kredit','Saldo'];
        foreach($data['trial_balance'] as $r)$rows[]=[$r['code'],$r['name'],$r['type'],$r['debit'],$r['credit'],$r['debit']-$r['credit']];
        break;
    case 'intercompany':
        $headers=['Tanggal','Dari','Ke','Deskripsi','Nominal','Status'];
        foreach($data['intercompany'] as $r)$rows[]=[$r['date'],$r['from'],$r['to'],$r['description'],$r['amount'],$r['status']];
        break;
}
$filename='nexa-'.$report.'-'.date('Ymd-His');
if(in_array($format,['xls','xlsx'],true)){
    $tmp=tempnam(sys_get_temp_dir(),'nexa-xlsx-');if($tmp===false)throw new RuntimeException('Gagal membuat temporary XLSX.');
    try{nexaWriteXlsx($tmp,$headers,$rows,'NEXA '.$report);header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');header('Content-Disposition: attachment; filename="'.$filename.'.xlsx"');header('Content-Length: '.filesize($tmp));readfile($tmp);}finally{@unlink($tmp);}exit;
}
if($format==='pdf'){
    $pdf=nexaBuildPdf($headers,$rows,'NEXA Group Finance · '.strtoupper($report));header('Content-Type: application/pdf');header('Content-Disposition: attachment; filename="'.$filename.'.pdf"');header('Content-Length: '.strlen($pdf));echo $pdf;exit;
}
if($format==='print'){?><!doctype html><html><head><meta charset="utf-8"><title>NEXA Report</title><style>body{font:12px Arial;color:#172033;padding:30px}h1{font-size:22px}table{width:100%;border-collapse:collapse}th,td{border-bottom:1px solid #ddd;padding:8px;text-align:left}th{background:#f3f5f8}.note{color:#667085}@media print{button{display:none}}</style></head><body><button onclick="print()">Cetak</button><h1>NEXA Group Finance</h1><p class="note">Laporan <?=e($report)?> · <?=e(date('d-m-Y H:i'))?></p><table><tr><?php foreach($headers as $h):?><th><?=e($h)?></th><?php endforeach?></tr><?php foreach($rows as $r):?><tr><?php foreach($r as $v):?><td><?=e((string)$v)?></td><?php endforeach?></tr><?php endforeach?></table></body></html><?php exit;}
header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename="'.$filename.'.csv"');echo "\xEF\xBB\xBF";$o=fopen('php://output','w');fputcsv($o,$headers);foreach($rows as $r)fputcsv($o,$r);fclose($o);
