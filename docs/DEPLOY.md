# Panduan Deploy Produksi

Stack: `compose.production.yaml` — enam container: `app` (PHP-FPM 8.4), `web` (Nginx), `queue` (Horizon), `scheduler`
(`schedule:work`), `mysql` (8.4), `redis` (7, appendonly). Image dibangun dari `docker/Dockerfile` (target `app` dan `web`).

## 1. Persiapan `.env` produksi

Salin `.env.example` → `.env` di server, lalu isi (jangan commit; simpan salinan di brankas kata sandi):

| Kunci | Nilai |
|---|---|
| `APP_ENV` / `APP_DEBUG` | `production` / `false` |
| `APP_KEY` | `php artisan key:generate --show`; **cadangkan terpisah dari backup DB** (dua pemegang) |
| `APP_URL` | URL HTTPS publik |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS wajib) |
| `CSP_MODE` | `report-only` dahulu; ubah ke `enforce` setelah diuji di staging (CSP mengizinkan skrip/gaya inline karena dibutuhkan Filament/Livewire) |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_ROOT_PASSWORD` | rahasia kuat (host `mysql` diatur compose) |
| `MAIL_*`, `WHATSAPP_ENABLED` dst. | kredensial SMTP kampus; WhatsApp sesuai keputusan |
| `SEED_SUPERADMIN_EMAIL`, `SEED_SUPERADMIN_PASSWORD` | akun super-admin awal (hapus kata sandi dari `.env` setelah dibuat) |
| `TRUSTED_PROXIES` | IP/CIDR reverse proxy kampus (dipisah koma) agar HTTPS & IP klien dikenali; `*` hanya bila jaringan proxy terisolasi |
| `WEB_PORT` | port host untuk Nginx (bawaan 8080); letakkan reverse proxy HTTPS kampus di depannya |

## 2. Build dan jalankan

```bash
docker compose -f compose.production.yaml up -d --build
docker compose -f compose.production.yaml ps          # app: healthy
curl -s http://localhost:8080/api/health              # {"status":"ok",...}
```

Container `app` menunggu DB, menjalankan `php artisan migrate --force` (hanya karena `RUN_MIGRATIONS=true`), lalu
`optimize`, `filament:optimize`, `icons:cache`, `event:cache`. `queue` dan `scheduler` baru start setelah `app` sehat.
`storage:link` tidak diperlukan (tidak ada berkas publik/unggahan).

## 3. Data awal (sekali saja)

```bash
docker compose -f compose.production.yaml exec app php artisan db:seed --class=PeranDanIzinSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=SuperAdminSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=KonfigurasiSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=MasterKepegawaianSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=MasterJabatanSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=MasterPendukungSeeder --force
docker compose -f compose.production.yaml exec app php artisan db:seed --class=MasterProdiSeeder --force
```

Seeder demo dan `BebanUjiSeeder` menolak berjalan di production. Periksa master prodi/kode PDDIKTI dan nilai konfigurasi
bertanda *perlu verifikasi* (docs/05-UJI-PENERIMAAN.md, G-01..G-03) lewat panel **Konfigurasi**. Login pertama super-admin
mewajibkan pemasangan MFA.

## 4. Pemeriksaan pasca-deploy

- `docker compose ... exec app php artisan schedule:list` memuat `sdm:tandai-kedaluwarsa`, `sdm:hitung-pengingat`, `sdm:kirim-pengingat`, `sdm:periksa-tautan`.
- `/horizon` (hanya super-admin) menampilkan lima supervisor; tidak ada job gagal.
- `curl -I https://<domain>/admin/login` memuat `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`.
- Cookie sesi bertanda `Secure; HttpOnly; SameSite=Lax`.

## 5. Pembaruan versi

```bash
git pull && docker compose -f compose.production.yaml up -d --build
```

Migrasi berjalan otomatis di `app`. Untuk rollback: checkout tag sebelumnya, build ulang, dan bila perlu pulihkan DB
(docs/PEMULIHAN.md). Jangan menjalankan `migrate:fresh` di produksi.

## 6. Rotasi `APP_KEY`

`nik_hash` (HMAC memakai `APP_KEY`) dan kolom terenkripsi bergantung pada kunci.

1. Cadangkan DB dan kunci lama.
2. Buat kunci baru; di `.env` set `APP_PREVIOUS_KEYS=<kunci lama>` dan `APP_KEY=<kunci baru>` (koma untuk beberapa kunci lama).
3. Restart: `docker compose ... up -d`. Data lama tetap terbaca lewat kunci sebelumnya.
4. **Wajib:** `docker compose ... exec app php artisan sdm:hitung-ulang-hash` — menghitung ulang `nik_hash` semua pegawai
   (termasuk yang terhapus lunak) dari NIK terdekripsi; tanpa ini pengecekan NIK ganda dan impor memakai hash lama dan gagal.
   Perintah berhenti dengan galat bila ada NIK yang tidak dapat didekripsi.
5. Setelah yakin, kolom terenkripsi akan ditulis ulang dengan kunci baru saat record diubah; simpan kunci lama di
   `APP_PREVIOUS_KEYS` selama masih ada data dengan kunci lama.
6. Token API Sanctum tidak terpengaruh (disimpan sebagai hash SHA-256, bukan dengan `APP_KEY`).

## 7. Catatan keamanan operasional

- Hanya `web` yang membuka port ke host; `mysql` dan `redis` tidak diekspos.
- CORS API tertutup bawaan; isi `CORS_ALLOWED_ORIGINS` hanya bila perlu.
- Pasang HTTPS di reverse proxy kampus dan teruskan `X-Forwarded-Proto`; isi `TRUSTED_PROXIES` di `.env`.
- Hapus `SEED_SUPERADMIN_PASSWORD` dari `.env` setelah akun dibuat.
