# Backup dan Pemulihan

> **Keputusan 16 Oktober 2026 berlaku setelah Fase B** (PHP 8.5; MariaDB 10.11+ native di host, kolasi `utf8mb4_unicode_ci`, bukan container MySQL 8.4; SQLite tidak dipakai; STANDAR-TEKNIS §1, §2). Kondisi saat ini: basis data masih container `mysql` 8.4 dengan `mysqldump`; setelah migrasi ke MariaDB native, ganti perintah dengan `mariadb-dump`/`mariadb` pada host dan perbarui skrip di bawah.

## Yang perlu dicadangkan

| Komponen | Cara | Keterangan |
|---|---|---|
| Basis data (kini MySQL 8.4 di container; setelah Fase B MariaDB native `db_sdm`) | `mysqldump` harian (kini); `mariadb-dump` di host (setelah Fase B) | Satu-satunya data aplikasi yang perlu dicadangkan. |
| `APP_KEY` | Brankas kata sandi fakultas, **terpisah dari backup DB** (dua pemegang) | Tanpa `APP_KEY` yang sama, kolom terenkripsi (NIK, NPWP, rekening, usulan sensitif) **tidak dapat dibaca**. |
| `APP_PREVIOUS_KEYS` | Ikut dicatat bila kunci pernah dirotasi | Lihat docs/DEPLOY.md. |
| Berkas pegawai (SK, ijazah, dll.) | **Tidak ada di server** | Berada di Shared Drive Kepegawaian FKIP; backup mengikuti kebijakan Google Workspace/Unsil. Server hanya menyimpan tautan. |
| Redis (hingga Fase B; Profil Ringan menghapusnya, lalu antrean/cache/sesi ada di MySQL dan ikut cadangan DB) | Tidak perlu | Hanya cache, sesi, antrean; data hilang tidak merusak data induk. Job antrean yang belum jalan dapat dibuat ulang (pengingat dihitung ulang tiap hari). |

## Skrip backup (cron host docker-apps, 02:30)

```bash
#!/usr/bin/env bash
set -euo pipefail
TUJUAN=/srv/backup/sdm
mkdir -p "$TUJUAN"
STAMP=$(date +%Y%m%d-%H%M)
docker compose -f /srv/docker-apps/sdm/compose.production.yaml exec -T mysql \
  sh -c 'mysqldump --single-transaction --routines --default-character-set=utf8mb4 -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"' \
  | gzip > "$TUJUAN/sdm-$STAMP.sql.gz"
find "$TUJUAN" -name 'sdm-*.sql.gz' -mtime +30 -delete          # retensi 30 hari
[ -s "$TUJUAN/sdm-$STAMP.sql.gz" ] || { echo "Backup SDM kosong" | mail -s "BACKUP GAGAL SDM" "$SUPER_ADMIN_EMAIL"; exit 1; }
```

### Target setelah Fase B (MariaDB native di host)

Berlaku setelah Fase B; kondisi saat ini: skrip di atas (container `mysql`, `mysqldump`). Basis data `db_sdm` berada di MariaDB
host, jadi cadangan dibuat langsung di host dengan `mariadb-dump` (tanpa `docker compose exec ... mysql`); kredensial dari file
opsi (`~/.my.cnf` bagian `[mariadb-dump]`, mode 600), bukan argumen baris perintah:

```bash
mariadb-dump --defaults-extra-file=/srv/docker-apps/sdm/.dump.cnf --single-transaction --routines \
  --default-character-set=utf8mb4 db_sdm | gzip > "$TUJUAN/sdm-$STAMP.sql.gz"
```

Pulihkan: `gunzip -c sdm-YYYYMMDD-HHMM.sql.gz | mariadb --defaults-extra-file=... db_sdm` (basis data `db_sdm` dibuat lebih
dahulu dengan `utf8mb4`/`utf8mb4_unicode_ci`). Kolom `UUID` native ikut terekspor sebagai teks UUID dan terimpor kembali apa adanya.
Langkah 2 dan 3 pemulihan di bawah berubah: tidak ada container `mysql` untuk dijalankan; cukup pastikan MariaDB host aktif.

Jadwal: `30 2 * * * /srv/docker-apps/sdm/backup.sh`. Salin hasilnya ke lokasi kedua (server lain/penyimpanan kampus).
Keputusan: memakai skrip host, bukan `spatie/laravel-backup`, karena tidak ada berkas pengguna yang dicadangkan dan pola
docker-apps sudah memakai cron host. Pasang spatie/laravel-backup bila nanti diperlukan notifikasi bawaan.

## Pemulihan

1. Siapkan stack baru (docs/DEPLOY.md) dengan `.env` yang memakai **`APP_KEY` lama** (dan `APP_PREVIOUS_KEYS` bila ada).
2. Hentikan `app`, `queue`, `scheduler` (setelah Fase B: `app` dan `scheduler`); jalankan hanya `mysql`.
3. Pulihkan: `gunzip -c sdm-YYYYMMDD-HHMM.sql.gz | docker compose exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'`.
4. Jalankan `php artisan migrate --force` (menyusul migrasi yang lebih baru dari backup), lalu start semua layanan.
5. Verifikasi: `/api/health` = ok; buka satu pegawai dan tampilkan NIK (membuktikan `APP_KEY` benar); Horizon aktif (setelah Fase B: `/api/health` memuat detak penjadwal); `php artisan schedule:list`.
6. Jalankan `php artisan sdm:hitung-pengingat` untuk menyegarkan pengingat; cache akan terisi ulang sendiri (Redis hingga Fase B; setelahnya cache di MySQL).

**Uji pulihkan sekali sebelum go-live** (butir G-10) dan catat tanggal serta hasilnya.
