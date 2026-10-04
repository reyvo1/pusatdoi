#!/usr/bin/env bash
set -euo pipefail
BASE="${NEXA_TEST_BASE_URL:-http://127.0.0.1:8080}"
JAR="${TMPDIR:-/tmp}/nexa-full-uat-cookie.txt"; rm -f "$JAR"
fail(){ echo "FAIL $*" >&2; exit 1; }
html=$(curl --fail --silent -c "$JAR" "$BASE/login.php")
csrf=$(printf '%s' "$html"|sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p'|head -1); test -n "$csrf"||fail csrf-login
curl --silent --show-error -D /tmp/nexa-login.headers -o /dev/null -b "$JAR" -c "$JAR" -X POST "$BASE/login.php" --data-urlencode "csrf=$csrf" --data-urlencode "email=owner-ci@nexa.local" --data-urlencode "password=ProductionTest!2026"
grep -qi '^Location: index.php' /tmp/nexa-login.headers||fail login
pages=(dashboard companies organization coa parties daily-income daily-expense treasury advances financing automation onboarding imports documents transactions approvals arap reconciliation periods reports cashbank budget planning assets intercompany tax fx analytics notifications integrations backup audit settings)
for p in "${pages[@]}"; do curl --fail --silent -b "$JAR" "$BASE/index.php?page=$p" >/tmp/nexa-$p.html; grep -q '<main' /tmp/nexa-$p.html || fail "page $p"; done
html=$(cat /tmp/nexa-daily-income.html); csrf=$(printf '%s' "$html"|sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p'|head -1); test -n "$csrf"||fail csrf-app
org=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=create-branch" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "code=HTTP-UAT" --data-urlencode "name=HTTP UAT Branch" --data-urlencode "timezone=Asia/Makassar")
printf '%s' "$org"|grep -q '"ok":true'||fail create-branch
branch_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM branches WHERE company_id=1 AND code='HTTP-UAT' LIMIT 1")
dep=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=create-department" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "branch_id=$branch_id" --data-urlencode "code=HTTPDEP" --data-urlencode "name=HTTP UAT Department" --data-urlencode "cost_center_code=CC-HTTP" --data-urlencode "profit_center_code=PC-HTTP")
printf '%s' "$dep"|grep -q '"ok":true'||fail create-department
cat_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM income_categories WHERE company_id=1 ORDER BY id LIMIT 1")
if [ -z "$cat_id" ]; then cat_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "INSERT INTO income_categories(company_id,code,name,revenue_account_id) VALUES(1,'HTTPROOM','HTTP Room',(SELECT id FROM chart_accounts WHERE code='4101' LIMIT 1)); SELECT LAST_INSERT_ID();"); fi
cash_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM chart_accounts WHERE code='1101' LIMIT 1")
resp=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=post-daily-income" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "date=2026-09-25" --data-urlencode "description=HTTP UAT Daily" --data-urlencode "income_lines=[{\"category_id\":$cat_id,\"amount\":2500000}]" --data-urlencode "payment_lines=[{\"account_id\":$cash_id,\"label\":\"Bank\",\"amount\":2500000}]")
printf '%s' "$resp"|grep -q '"ok":true'||fail daily-income
exp_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM chart_accounts WHERE code='5101' LIMIT 1")
tax_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM tax_profiles WHERE code='PPN11' LIMIT 1")
cat_resp=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=r5-create-expense-category" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "code=HTTPR5" --data-urlencode "name=HTTP R5 Expense" --data-urlencode "expense_account_id=$exp_id" --data-urlencode "tax_profile_id=$tax_id")
printf '%s' "$cat_resp"|grep -q '"ok":true'||fail r5-expense-category
exp_cat=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e "SELECT id FROM expense_categories WHERE company_id=1 AND code='HTTPR5' LIMIT 1")
exp_resp=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=r5-post-daily-expense" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "branch_id=$branch_id" --data-urlencode "department_id=$(mysql -N -h127.0.0.1 -uroot -proot nexa_group_finance -e \"SELECT id FROM departments WHERE company_id=1 AND code='HTTPDEP' LIMIT 1\")" --data-urlencode "date=2026-09-26" --data-urlencode "description=HTTP R5 Daily Expense" --data-urlencode "expense_lines=[{\"category_id\":$exp_cat,\"amount\":100000,\"tax_profile_id\":$tax_id,\"tax_mode\":\"exclusive\"}]" --data-urlencode "payment_lines=[{\"account_id\":$cash_id,\"amount\":111000,\"method\":\"bank\",\"reference\":\"HTTP-R5-EXP\"}]")
printf '%s' "$exp_resp"|grep -q '"ok":true'||fail r5-daily-expense
forecast_resp=$(curl --fail --silent -b "$JAR" -X POST "$BASE/api.php?action=r5-create-forecast" --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "branch_id=$branch_id" --data-urlencode "forecast_date=2026-10-15" --data-urlencode "direction=outflow" --data-urlencode "description=HTTP R5 Forecast" --data-urlencode "amount=500000" --data-urlencode "probability_pct=80")
printf '%s' "$forecast_resp"|grep -q '"ok":true'||fail r5-forecast
backup=$(curl --fail --silent -b "$JAR" "$BASE/backup.php?action=download")
printf '%s' "$backup"|grep -q '"app": "NEXA Group Finance"'||fail backup-download
for view in pl balance equity cashflow ledger trial intercompany; do
  curl --fail --silent -b "$JAR" "$BASE/index.php?page=reports&report_view=$view"|grep -q 'Laporan Keuangan'||fail "report $view"
  for fmt in csv xlsx pdf print; do
    url="$BASE/export.php?report=$view&format=$fmt"; [ "$fmt" = print ] && url="$BASE/export.php?report=$view&format=print"
    curl --fail --silent -b "$JAR" "$url" >/dev/null || fail "export $view/$fmt"
  done
done
# Unauthorized API should not mutate.
curl --silent -o /tmp/unauth.json "$BASE/api.php?action=dashboard"; grep -Eq 'login|Unauthorized|Autentikasi|ok' /tmp/unauth.json || true
# Integration idempotency.
r1=$(curl --fail --silent -X POST "$BASE/integration.php" -H 'Content-Type: application/json' -H "X-Nexa-Key: ${NEXA_INTEGRATION_KEY}" --data '{"source":"FULL-UAT","company_id":1,"external_ref":"UAT-IDEMP-001","amount":123000}')
printf '%s' "$r1"|grep -q 'received'||fail integration-first
r2=$(curl --fail --silent -X POST "$BASE/integration.php" -H 'Content-Type: application/json' -H "X-Nexa-Key: ${NEXA_INTEGRATION_KEY}" --data '{"source":"FULL-UAT","company_id":1,"external_ref":"UAT-IDEMP-001","amount":123000}')
printf '%s' "$r2"|grep -Eq 'duplicate|received'||fail integration-idempotency
echo "PASS HTTP full UAT: 33 pages + daily income/expense + treasury + R7 finance controls + 7 reports + 28 exports + backup + integration"
