# R7.2.1 Root-Fix Patch Instructions

This patch must be applied from the repository root corresponding to GitHub commit `66857d6` / R7.1 base. It intentionally excludes `storage/audit.log` because that file is runtime output and must not be committed.

```bash
cd /home/ivo/Desktop/program/pusatdoi

git restore storage/audit.log 2>/dev/null || true
rm -rf /tmp/nexa-r72-patch
mkdir -p /tmp/nexa-r72-patch
unzip -q -o ~/Downloads/NEXA-GROUP-FINANCE-R7.2.1-ROOT-FIX-PATCH-2026-10-04.zip -d /tmp/nexa-r72-patch
rsync -a /tmp/nexa-r72-patch/ ./

# Verify the patch really reached the repo BEFORE testing/committing
git status --short
grep -c '^ALTER TABLE' database/schema_enterprise_r7.sql
php tests/schema-contract.php
php tests/frontend-backend-contract.php
```

Expected: many source files are modified/new; the `ALTER TABLE` count for the canonical fresh schema is `0`; both contract tests pass.
