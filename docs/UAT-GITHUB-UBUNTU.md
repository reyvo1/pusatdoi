# NEXA Enterprise R3 Full UAT — Ubuntu + GitHub

Local repo: `~/Desktop/program/keuangan sentral`
GitHub repo: `https://github.com/reyvo1/doipusat`

## Jangan push baseline lama lagi
Gunakan hanya paket Enterprise R3 RC terbaru. Setelah diekstrak ke repo lokal:

```bash
cd ~/Desktop/program/'keuangan sentral'
bash tests/run-all-local.sh
```

Jika local gates PASS:

```bash
git add -A
git commit -m "NEXA Enterprise R3 full UAT candidate"
git push -u origin main
```

Lalu buka GitHub → `reyvo1/doipusat` → Actions → **NEXA Full UAT**.

## Gate
1. Core / Static Regression
2. MySQL / Accounting UAT
3. R2 → R3 Migration UAT
4. HTTP / Security / Reports UAT
5. Browser E2E / Visual UAT
6. Multi-Entity / High-Volume UAT
7. Production Candidate UAT Verdict

Semua harus hijau. Browser gate mengunggah screenshot sebagai artifact; HTTP gate mengunggah server log. Jangan mengubah workflow untuk mengabaikan failure.
