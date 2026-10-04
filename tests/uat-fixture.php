<?php
putenv('NEXA_DEMO_MODE=false');
require __DIR__.'/../lib/bootstrap.php';
$pdo=db();
$hash=password_hash('ProductionTest!2026', PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users(name,email,password_hash,role,company_id,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),role=VALUES(role),company_id=VALUES(company_id),is_active=1")
    ->execute(['UAT Owner','owner-ci@nexa.local',$hash,'group_owner',null]);
$pdo->prepare("INSERT INTO users(name,email,password_hash,role,company_id,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),role=VALUES(role),company_id=VALUES(company_id),is_active=1")
    ->execute(['UAT Entity','entity-ci@nexa.local',$hash,'entity_admin',1]);
$pdo->prepare("INSERT INTO users(name,email,password_hash,role,company_id,is_active) VALUES(?,?,?,?,?,1) ON DUPLICATE KEY UPDATE password_hash=VALUES(password_hash),role=VALUES(role),company_id=VALUES(company_id),is_active=1")
    ->execute(['UAT Auditor','auditor-ci@nexa.local',$hash,'auditor',null]);
$pdo->exec("INSERT INTO fiscal_periods(fiscal_year,period,status) VALUES(2026,9,'open') ON DUPLICATE KEY UPDATE status='open',closed_at=NULL,closed_by=NULL");
echo "UAT fixture ready\n";
