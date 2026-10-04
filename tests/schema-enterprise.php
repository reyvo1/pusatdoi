<?php
declare(strict_types=1);
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../src/autoload.php';

use Nexa\Infrastructure\Persistence\SchemaInspector;

$required = [
    'companies','branches','departments','chart_accounts','users','role_permissions','login_attempts','audit_logs',
    'fiscal_periods','approval_policies','journal_batches','journal_entries','journal_lines','journal_approvals',
    'tax_profiles','tax_transactions','income_categories','bank_accounts','bank_feed','bank_statement_imports',
    'bank_reconciliation_sessions','bank_reconciliation_matches','budgets','fixed_assets','asset_depreciation_schedule',
    'invoices','invoice_lines','invoice_payments','payment_allocations','currency_rates','fx_rates','fx_events',
    'intercompany_eliminations','consolidation_runs','elimination_entries','integration_staging','integration_events',
    'integration_outbox','settlement_batches','parties','onboarding_import_jobs','onboarding_import_errors','ap_payment_batches','ap_payment_batch_lines','document_attachments','employee_advances','employee_advance_settlements','loan_facilities','loan_payments','equity_transactions','budget_scenarios','budget_scenario_lines','invoice_adjustments','bank_import_profiles','period_close_tasks','approval_decisions','fx_revaluation_runs','report_snapshots','document_sequences','asset_events','expense_categories','opening_balance_batches','opening_balance_lines','bank_transfers','recurring_journal_templates','recurring_journal_lines','recurring_journal_runs','cash_forecast_items','year_end_closes'
];

try {
    $host=getenv('NEXA_DB_HOST')?:'127.0.0.1';
    $port=getenv('NEXA_DB_PORT')?:'3306';
    $name=getenv('NEXA_DB_NAME')?:'nexa_group_finance';
    $user=getenv('NEXA_DB_USER')?:'root';
    $pass=getenv('NEXA_DB_PASS')?:'';
    $pdo=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $inspector = new SchemaInspector($pdo);
    $result = $inspector->assertRequired($required);
    if (!$result['ok']) {
        fwrite(STDERR, 'Missing enterprise tables: '.implode(', ', $result['missing']).PHP_EOL);
        exit(1);
    }

    $checks = [
        ['branches','timezone'], ['chart_accounts','company_scope'], ['users','last_login_at'], ['audit_logs','company_id'], ['audit_logs','request_id'], ['budgets','branch_id'], ['budgets','department_id'], ['budgets','branch_scope'], ['budgets','department_scope'], ['currency_rates','source'], ['journal_entries','department_id'], ['journal_entries','batch_id'],
        ['journal_entries','external_ref'], ['income_categories','tax_profile_id'], ['bank_transfers','from_account_id'], ['bank_transfers','to_account_id'], ['recurring_journal_runs','reversal_due_date'], ['recurring_journal_runs','reversal_status'], ['bank_accounts','currency'],
        ['fixed_assets','depreciation_method'], ['invoices','branch_id'], ['invoices','external_ref'],
        ['employee_advances','outstanding_amount'], ['loan_facilities','outstanding_principal'], ['budget_scenarios','scenario_type'], ['journal_approvals','document_type'], ['journal_approvals','required_approvals'], ['journal_lines','transaction_currency'], ['journal_lines','base_amount'], ['integration_events','event_version'], ['integration_events','attempts'], ['integration_outbox','idempotency_key'], ['tax_transactions','filing_period']
    ];
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=? AND column_name=?');
    $missingColumns=[];
    foreach($checks as [$table,$column]){
        $stmt->execute([$table,$column]);
        if((int)$stmt->fetchColumn()!==1){$missingColumns[]="$table.$column";}
    }
    if($missingColumns){
        fwrite(STDERR,'Missing enterprise columns: '.implode(', ',$missingColumns).PHP_EOL);
        exit(1);
    }

    $fkCount=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.referential_constraints WHERE constraint_schema=DATABASE()")->fetchColumn();
    $companyCount=(int)$pdo->query('SELECT COUNT(*) FROM companies')->fetchColumn();
    $accountCount=(int)$pdo->query('SELECT COUNT(*) FROM chart_accounts')->fetchColumn();
    if($fkCount < 20 || $companyCount < 4 || $accountCount < 10){
        fwrite(STDERR,"Enterprise schema integrity too small: fk=$fkCount companies=$companyCount accounts=$accountCount\n");
        exit(1);
    }
    $scopeConstraints=(int)$pdo->query("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND index_name IN ('uq_coa_company_code','uq_budget') AND non_unique=0")->fetchColumn();
    if($scopeConstraints < 4){fwrite(STDERR,"Normalized scope unique indexes missing\n");exit(1);}
    printf("PASS enterprise schema: %d tables, %d foreign keys, %d companies, %d accounts\n",$result['table_count'],$fkCount,$companyCount,$accountCount);
} catch (Throwable $e) {
    fwrite(STDERR,'Schema verification failed: '.$e->getMessage().PHP_EOL);
    exit(1);
}
