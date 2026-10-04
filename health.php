<?php
require __DIR__.'/lib/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try{
    if($config['demo_mode']){http_response_code(200);echo json_encode(['ok'=>true,'app'=>'NEXA Group Finance','version'=>$config['version']??'7.0.0-r7-uat','mode'=>'demo']);exit;}
    $pdo=db();
    $pdo->query('SELECT 1')->fetchColumn();
    foreach(['companies','branches','departments','chart_accounts','journal_batches','journal_entries','journal_lines','users','invoices','bank_reconciliation_sessions','consolidation_runs','integration_events','integration_outbox','parties','tax_transactions','asset_events','report_snapshots','document_sequences','expense_categories','opening_balance_batches','opening_balance_lines','bank_transfers','recurring_journal_templates','recurring_journal_runs','cash_forecast_items','year_end_closes','onboarding_import_jobs','onboarding_import_errors','ap_payment_batches','ap_payment_batch_lines','document_attachments','employee_advances','employee_advance_settlements','loan_facilities','loan_payments','equity_transactions','budget_scenarios','budget_scenario_lines'] as $table){$pdo->query('SELECT 1 FROM `'.$table.'` LIMIT 1');}
    require_once __DIR__.'/src/autoload.php';$state=loadStore();$enterprise=(new \Nexa\Application\EnterpriseKernel((int)$config['approval_threshold']))->health($state);http_response_code(200);echo json_encode(['ok'=>true,'app'=>'NEXA Group Finance','version'=>$config['version']??'7.0.0-r7-uat','mode'=>'production','database'=>'reachable','architecture'=>$enterprise['architecture'],'companies'=>$enterprise['companies'],'entries'=>$enterprise['entries']]);
}catch(Throwable $e){http_response_code(503);echo json_encode(['ok'=>false,'app'=>'NEXA Group Finance','version'=>$config['version']??'7.0.0-r7-uat','mode'=>$config['demo_mode']?'demo':'production','database'=>'unavailable']);}
