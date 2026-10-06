# Backup dan Pemulihan

## Yang perlu dicadangkan

| Komponen | Cara | Keterangan |
|---|---|---|
| Basis data MySQL | `mysqldump` harian (lihat skrip) | Satu-satunya data aplikasi yang perlu dicadangkan. |
| `APP_KEY` | Brankas kata sandi fakultas, **terpisah dari backup DB** (dua pemegang) | Tanpa `APP_KEY` yang sama, kolom terenkripsi (NIK, NPWP, rekening, usulan sensitif) **tidak dapat dibaca**. |
| `APP_PREVIOUS_KEYS` | Ikut dicatat bila kunci pernah dirotasi | Lihat docs/DEPLOY.md. |
| Berkas pegawai (SK, ijazah, dll.) | **Tidak ada di server** | Berada di Shared Drive Kepegawaian FKIP; backup mengikuti kebijakan Google Workspace/Unsil. Server hanya menyimpan tautan. |
| Redis | Tidak perlu | Hanya cache, sesi, antrean; data hilang tidak merusak data induk. Job antrean yang belum jalan dapat dibuat ulang (pengingat dihitung ulang tiap hari). |

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

Jadwal: `30 2 * * * /srv/docker-apps/sdm/backup.sh`. Salin hasilnya ke lokasi kedua (server lain/penyimpanan kampus).
Keputusan: memakai skrip host, bukan `spatie/laravel-backup`, karena tidak ada berkas pengguna yang dicadangkan dan pola
docker-apps sudah memakai cron host. Pasang spatie/laravel-backup bila nanti diperlukan notifikasi bawaan.

## Pemulihan

1. Siapkan stack baru (docs/DEPLOY.md) dengan `.env` yang memakai **`APP_KEY` lama** (dan `APP_PREVIOUS_KEYS` bila ada).
2. Hentikan `app`, `queue`, `scheduler`; jalankan hanya `mysql`.
3. Pulihkan: `gunzip -c sdm-YYYYMMDD-HHMM.sql.gz | docker compose exec -T mysql sh -c 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE"'`.
4. Jalankan `php artisan migrate --force` (menyusul migrasi yang lebih baru dari backup), lalu start semua layanan.
5. Verifikasi: `/api/health` = ok; buka satu pegawai dan tampilkan NIK (membuktikan `APP_KEY` benar); Horizon aktif; `php artisan schedule:list`.
6. Jalankan `php artisan sdm:hitung-pengingat` untuk menyegarkan pengingat; cache Redis akan terisi ulang sendiri.

**Uji pulihkan sekali sebelum go-live** (butir G-10) dan catat tanggal serta hasilnya.
