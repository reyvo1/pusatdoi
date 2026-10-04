# PUSH INSTRUCTIONS — R7.3 GitHub UAT Root Fix

Target local repo: `/home/ivo/Desktop/program/pusatdoi`  
Target GitHub: `https://github.com/reyvo1/pusatdoi.git`

Preserve the existing `.git` directory and overlay the content of this package directly at repository root.

Then run:

```bash
cd /home/ivo/Desktop/program/pusatdoi

git diff --check
bash tests/run-all-local.sh

git status --short
git add -A
git commit -m "Fix R7.3 GitHub UAT root causes"
git push origin main

git rev-parse HEAD
git status
```

Expected local evidence before commit:

- Schema/migration contract: 39/39 PASS
- Frontend/backend contract: 26/26 PASS
- Deep audit security/integrity: 18/18 PASS
- Total deterministic assertions: 282/282 PASS
- Architecture: PASS
- Production fail-closed: PASS

After push, both `NEXA Full UAT` and `NEXA MySQL Production Simulation` must run on the same SHA. Do not weaken failing GitHub tests; inspect and repair the first causal defect.
