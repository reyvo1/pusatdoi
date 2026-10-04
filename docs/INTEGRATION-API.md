# Integration Staging API

`POST /integration.php`

Authentication: header `X-Nexa-Key`, nilainya berasal dari `integration_key`/`NEXA_INTEGRATION_KEY`.

Payload maksimum 1 MB. Required fields:
- `source`: `A-Z`, angka, `_ . : -`, maksimum 80 karakter.
- `company_id`: ID badan usaha aktif.
- `external_ref`: referensi unik source, maksimum 160 karakter.
- `amount`: >= 0.

Idempotency key adalah kombinasi `(source, external_ref)`. Endpoint menerima data ke `integration_staging` dengan status `received`; endpoint tidak langsung memposting ledger.

Response sukses: HTTP 202.

Connector production sebaiknya menambahkan mapping layer per source sebelum staging berubah menjadi `validated`, lalu posting ledger hanya dilakukan setelah business-rule validation berhasil.
