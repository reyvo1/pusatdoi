#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
echo '== PHP syntax =='
find . -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/nexa-r3-php-lint.log
echo 'PASS all PHP files'
echo '== JavaScript syntax =='
node --check assets/app.js
node --check assets/r4-ui.js
node --check assets/r5-ui.js
node --check assets/r6-ui.js
node --check assets/r7-ui.js
node --check tests/browser-uat.mjs
echo 'PASS JavaScript syntax'
echo '== Schema / migration contract =='
php tests/schema-contract.php
echo '== Frontend / backend contract =='
php tests/frontend-backend-contract.php
echo '== Legacy regression =='
php tests/run.php
echo '== Enterprise domain =='
php tests/enterprise-domain.php
echo '== Enterprise advanced =='
php tests/enterprise-advanced.php
echo '== R4 workflow / UI-API contract =='
php tests/r4-workflows.php
echo '== R5 finance operations =='
php tests/r5-operations.php
echo '== R6 usability == '
php tests/r6-usability.php
echo '== R7 group finance controls =='
php tests/r7-group-controls.php
echo '== Architecture =='
php tests/architecture.php
echo '== Production fail-closed =='
php tests/production-fail-closed.php
echo 'LOCAL GATES PASS — MySQL/browser production gates run in GitHub Actions.'
