# Catatan Kinerja

Pengukuran memakai `BebanUjiSeeder` (1.000 pegawai, masing-masing 1 riwayat pendidikan, 1 sertifikasi, 5 dokumen, BKD untuk
2 semester → 5.000 dokumen) dan tes `tests/Feature/Kinerja/UkurKinerjaTest.php`.

```bash
APP_UKUR_KINERJA=1 ./vendor/bin/pest tests/Feature/Kinerja/UkurKinerjaTest.php
# atau, untuk mengisi basis data lokal: php artisan db:seed --class=BebanUjiSeeder
```

## Hasil (6 Okt 2026, SQLite in-memory di Docker, satu proses)

| Halaman / proses | Waktu |
|---|---|
| Daftar pegawai (50 baris, `loadTable`) | 0,18 dtk |
| View pegawai | 0,03 dtk |
| Statistik dasbor (dingin → cache) | < 0,01 dtk → ≈ 0 |
| Halaman dasbor | 0,01 dtk |
| Ekspor profil dosen prodi (satu prodi) | 0,33 dtk |

Target PRD (daftar pegawai ≤ 2 dtk untuk 1.000 pegawai) **terpenuhi** dengan margin besar.

**Catatan:** angka ini diukur pada SQLite tanpa latensi jaringan dan tanpa render browser; waktu nyata di produksi (MySQL 8.4,
Redis, PHP-FPM) lebih besar. Ulangi pengukuran di staging dengan `BebanUjiSeeder` sebelum go-live dan catat hasilnya di sini.
Hanya SQLite yang diuji dalam sesi ini; MySQL belum diukur.

## Batas jumlah kueri (diuji otomatis di `JumlahKueriTest`)

| Halaman | Batas |
|---|---|
| Daftar pegawai 50 baris | ≤ 15 kueri |
| `GET /api/v1/dosen` 50 item | ≤ 10 kueri |
| Dasbor dari cache | ≤ 5 kueri |

`Model::preventLazyLoading()` aktif di luar produksi sehingga N+1 gagal saat pengembangan/tes.

## Indeks tambahan (migrasi `2026_10_06_270000_tambah_indeks_kinerja`)

`riwayat_pendidikan(pegawai_id, is_pendidikan_tertinggi)`, `sertifikasi(pegawai_id, jenis_sertifikasi_id)`,
`bkd(semester_id, kesimpulan)`, `dokumen_pegawai(pegawai_id, jenis_dokumen_id)`,
`riwayat_jabatan_struktural(pegawai_id, tmt_selesai)`, `riwayat_pangkat(pegawai_id, is_terkini)`.

## Optimasi saat build/deploy

`composer produksi` = `optimize` + `filament:optimize` + `icons:cache` + `event:cache`. CI menjalankannya agar closure di
config/route yang menggagalkan cache terdeteksi lebih awal.
