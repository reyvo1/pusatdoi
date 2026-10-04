# R7.2.2 GitHub UAT Root Fix

Base repo: `/home/ivo/Desktop/program/pusatdoi`
Expected base commit: `601be22a21c79d125f06a507646b2f2f74ad0c18`

This patch does not weaken UAT. It fixes canonical schema ordering and strengthens migration preservation.

Apply from repo root:

```bash
cd /home/ivo/Desktop/program/pusatdoi
rm -rf /tmp/nexa-r722
mkdir -p /tmp/nexa-r722
unzip -q -o ~/Downloads/NEXA-GROUP-FINANCE-R7.2.2-GITHUB-UAT-ROOT-FIX-PATCH-2026-10-04.zip -d /tmp/nexa-r722
rsync -a /tmp/nexa-r722/ ./
php tests/schema-contract.php
bash tests/run-all-local.sh
git status --short
git add -A
git commit -m "Fix R7.2.2 schema dependency and migration preservation"
git push origin main
```
