<?php
$environment = strtolower(trim((string)(getenv('NEXA_ENV') ?: 'development')));
$demoEnv = getenv('NEXA_DEMO_MODE');
$demoMode = $demoEnv === false || $demoEnv === '' ? ($environment !== 'production') : filter_var($demoEnv, FILTER_VALIDATE_BOOL);
$base = [
    'app_name' => getenv('NEXA_APP_NAME') ?: 'NEXA Group Finance',
    'version' => '7.0.3-r7.3-deep-audit',
    'environment' => $environment,
    'timezone' => getenv('NEXA_TIMEZONE') ?: 'Asia/Makassar',
    'base_currency' => getenv('NEXA_CURRENCY') ?: 'IDR',
    'demo_mode' => $demoMode,
    'approval_threshold' => (float)(getenv('NEXA_APPROVAL_THRESHOLD') ?: 25000000),
    'setup_key' => getenv('NEXA_SETUP_KEY') ?: '',
    'integration_key' => getenv('NEXA_INTEGRATION_KEY') ?: '',
    'worker_key' => getenv('NEXA_WORKER_KEY') ?: '',
    'outbox_webhook' => getenv('NEXA_OUTBOX_WEBHOOK') ?: '',
    'worker_user_id' => (int)(getenv('NEXA_WORKER_USER_ID') ?: 1),
    'db' => [
        'host' => getenv('NEXA_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('NEXA_DB_PORT') ?: '3306',
        'name' => getenv('NEXA_DB_NAME') ?: 'nexa_group_finance',
        'user' => getenv('NEXA_DB_USER') ?: 'root',
        'pass' => getenv('NEXA_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
$local = __DIR__.'/config.local.php';
if (is_file($local)) {
    $override = require $local;
    if (is_array($override)) $base = array_replace_recursive($base, $override);
}
return $base;
