<?php
// Copy to config.local.php on shared hosting. Never commit/share the real file.
return [
    'demo_mode' => false,
    'environment' => 'production',
    'setup_key' => 'CHANGE-THIS-LONG-RANDOM-SETUP-KEY',
    'integration_key' => 'CHANGE-THIS-SEPARATE-LONG-INTEGRATION-KEY',
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'name' => 'cpaneluser_nexa',
        'user' => 'cpaneluser_nexauser',
        'pass' => 'CHANGE-THIS-DATABASE-PASSWORD',
        'charset' => 'utf8mb4',
    ],
];
