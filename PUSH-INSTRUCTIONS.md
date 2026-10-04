# PUSH INSTRUCTIONS

Target local repo: `/home/ivo/Desktop/program/pusatdoi`
Target GitHub: `https://github.com/reyvo1/pusatdoi.git`

Preserve the existing `.git` directory, replace application source with the content of this full-source package, then:

```bash
cd /home/ivo/Desktop/program/pusatdoi
bash tests/run-all-local.sh
git status --short
git add -A
git commit -m "NEXA R7.3 deep audit + strict unauthorized UAT gate"
git push origin main
git rev-parse HEAD
git status
```

Do not weaken failing GitHub tests. Fix the first causal source defect.
