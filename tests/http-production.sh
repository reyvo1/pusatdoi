#!/usr/bin/env bash
set -euo pipefail
BASE="${NEXA_TEST_BASE_URL:-http://127.0.0.1:8080}"
JAR="${TMPDIR:-/tmp}/nexa-ci-cookie.txt"
rm -f "$JAR"
html=$(curl --fail --silent -c "$JAR" "$BASE/login.php")
csrf=$(printf '%s' "$html" | sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p' | head -1)
test -n "$csrf"
headers=$(mktemp)
curl --silent --show-error -D "$headers" -o /dev/null -b "$JAR" -c "$JAR" \
  -X POST "$BASE/login.php" \
  --data-urlencode "csrf=$csrf" \
  --data-urlencode "email=owner-ci@nexa.local" \
  --data-urlencode "password=ProductionTest!2026"
grep -qi '^Location: index.php' "$headers"
html=$(curl --fail --silent -b "$JAR" "$BASE/index.php?page=dashboard")
csrf=$(printf '%s' "$html" | sed -n 's/.*name="csrf" value="\([^"]*\)".*/\1/p' | head -1)
test -n "$csrf"
resp=$(curl --fail --silent -b "$JAR" -c "$JAR" -X POST "$BASE/api.php?action=post-journal" \
  --data-urlencode "csrf=$csrf" --data-urlencode "company_id=1" --data-urlencode "date=2026-09-25" \
  --data-urlencode "description=CI HTTP journal" --data-urlencode "amount=1500000" \
  --data-urlencode "debit_account_id=1" --data-urlencode "credit_account_id=6")
printf '%s' "$resp" | grep -q '"ok":true'
printf '%s' "$resp" | grep -q '"message":"Jurnal berhasil diposting dan balance.'
echo "PASS production HTTP login + CSRF + journal mutation"
